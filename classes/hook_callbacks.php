<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_efscorm;

/**
 * Hook callbacks: direct entry into SCORM activities and the full-screen player.
 *
 * Nothing of mod_scorm is modified: the activity page (view.php) is sent to the
 * core player exactly as "Skip content structure page: Always" does, and the
 * core player page (player.php) gets the theme's 'popup' layout, the body
 * classes the plugin stylesheet (styles.css) keys on and the player module
 * (amd/src/player.js), so the content fills the window. It is still the
 * activity page, so time-tracking plugins (local_efreport, local_eftimeclock)
 * keep measuring it.
 *
 * Activities set to open in a new window are, with the "same tab" setting,
 * sent to the core player in its "popup" display mode but in the current tab:
 * the content the activity would show in the new window, measured like any
 * other page and without pop-up blockers in the way. The real new window the
 * core opens (when the student is not sent in directly) is left untouched.
 *
 * @package    local_efscorm
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /** @var string URL parameter that marks the new-window player opened by this plugin in the current tab. */
    public const TAB_PARAM = 'efscormtab';

    /** @var bool Set while redirecting: redirect() may render a page (debug notices), which runs the hook again. */
    private static bool $redirecting = false;

    /**
     * Before the headers: direct entry on view.php, full-screen layout on player.php.
     *
     * @param \core\hook\output\before_http_headers $hook The hook.
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        global $PAGE;

        $off = self::$redirecting || empty(get_config('local_efscorm', 'enabled'));
        if ($off || !$PAGE->cm || $PAGE->cm->modname !== 'scorm') {
            return;
        }
        if ($PAGE->pagetype === 'mod-scorm-view') {
            self::direct_entry();
            return;
        }
        if ($PAGE->pagetype !== 'mod-scorm-player') {
            return;
        }
        $scorm = self::scorm();
        if (!$scorm || self::is_real_new_window($scorm)) {
            return;
        }
        // The player in the current tab, whatever its display mode: the theme's minimal layout
        // (no navbar, blocks or footer) instead of 'incourse' or 'embedded'.
        self::fullscreen();
    }

    /**
     * Whether the player page is the real new window the core opens for a
     * "new window" activity (display=popup without our same-tab mark). That
     * window has no exit button and is closed by the student: it is left as is.
     *
     * @param \stdClass $scorm The SCORM record.
     * @return bool
     */
    private static function is_real_new_window(\stdClass $scorm): bool {
        return !empty($scorm->popup)
            && optional_param('display', '', PARAM_ALPHA) === 'popup'
            && !optional_param(self::TAB_PARAM, 0, PARAM_BOOL);
    }

    /**
     * Marks the current page as the full-screen player: the theme's minimal
     * layout, the body classes the plugin stylesheet keys on (full screen and,
     * on request, the hidden table of contents) and the player module with
     * the data of the top bar.
     */
    private static function fullscreen(): void {
        global $PAGE;
        $PAGE->set_pagelayout('popup');
        $PAGE->add_body_class('local-efscorm-fullscreen');
        if (!empty(get_config('local_efscorm', 'hidetoc'))) {
            $PAGE->add_body_class('local-efscorm-hidetoc');
        }
        $PAGE->requires->js_call_amd('local_efscorm/player', 'init', [self::bar_data()]);
    }

    /**
     * The data of the top bar: the activity name (plain text; the module sets
     * it as text), the label of the exit button and where it goes when the
     * core exit button cannot be used (the new-window player has none, so the
     * link goes back to the section of the course, as the core button would).
     * In a "single activity" course the course page is the activity itself, so
     * the exit goes to the site home, as the core does to avoid a redirection
     * loop; teachers keep the course link.
     *
     * @return array {title, exit, fallback, force}
     */
    public static function bar_data(): array {
        global $CFG, $PAGE;

        $singleactivity = course_get_format($PAGE->course)->get_format() === 'singleactivity'
            && !has_capability('mod/scorm:viewreport', $PAGE->context);
        $fallback = $singleactivity ? $CFG->wwwroot . '/' : course_get_url($PAGE->course, $PAGE->cm->sectionnum)->out(false);
        return [
            'title' => format_string($PAGE->cm->name, true, ['context' => $PAGE->context, 'escape' => false]),
            'exit' => get_string('exit', 'local_efscorm'),
            'fallback' => $fallback,
            'force' => $singleactivity,
        ];
    }

    /**
     * Whether activities set to open in a new window are shown in the current tab.
     *
     * @return bool
     */
    private static function same_tab(): bool {
        return !empty(get_config('local_efscorm', 'samewindow'));
    }

    /**
     * Sends the student straight into the content, as the core does with
     * "Skip content structure page: Always" (teachers and report viewers keep
     * the normal page). The core guards are honoured: the mod/scorm:skipview
     * capability, the preventskip parameter of the core redirections and the
     * open/close dates (the activity page shows them). Activities that open in
     * a new window are sent to the player in the current tab when the "same
     * tab" setting is on, and left alone otherwise.
     */
    private static function direct_entry(): void {
        global $CFG, $PAGE, $USER;

        if (empty(get_config('local_efscorm', 'directentry')) || optional_param('preventskip', 0, PARAM_INT)) {
            return;
        }
        $scorm = self::scorm();
        if (!$scorm || !has_capability('mod/scorm:skipview', $PAGE->context)) {
            return;
        }
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');
        [$available] = scorm_get_availability_status($scorm);
        if (!$available) {
            return;
        }
        if (empty($scorm->popup)) {
            $forced = clone $scorm;
            $forced->skipview = SCORM_SKIPVIEW_ALWAYS;
            // Redirects when the user may go in; returns otherwise.
            self::$redirecting = true;
            scorm_simple_play($forced, $USER, $PAGE->context, $PAGE->cm->id);
            self::$redirecting = false;
            return;
        }
        if (!self::same_tab()) {
            return;
        }
        $url = self::same_tab_url($scorm, $USER, $PAGE->context, $PAGE->cm->id);
        if ($url) {
            self::$redirecting = true;
            redirect($url);
        }
    }

    /**
     * The URL of the new-window player ("popup" display mode) for the current
     * tab, built as scorm_simple_play() builds the one of the current-window
     * player: same first SCO, same organisation, same new-attempt rules; plus
     * our mark, so the player page knows it is in a tab and not in the real
     * new window. Null when the user may not be sent in (report viewers) or
     * when the package has nothing to launch.
     *
     * @param \stdClass $scorm The SCORM record.
     * @param \stdClass $user The user.
     * @param \context $context The module context.
     * @param int $cmid The course module id.
     * @return \moodle_url|null
     */
    public static function same_tab_url(\stdClass $scorm, \stdClass $user, \context $context, int $cmid): ?\moodle_url {
        global $DB;

        if (has_capability('mod/scorm:viewreport', $context) || !has_capability('mod/scorm:skipview', $context)) {
            return null;
        }
        if ($scorm->updatefreq == SCORM_UPDATE_EVERYTIME) {
            scorm_parse($scorm, false);
        }
        $scoes = $DB->get_records_select('scorm_scoes', 'scorm = ? AND ' .
            $DB->sql_isnotempty('scorm_scoes', 'launch', false, true), [$scorm->id], 'sortorder, id', 'id');
        if (!$scoes) {
            return null;
        }
        $orgidentifier = '';
        if ($sco = scorm_get_sco($scorm->launch, SCO_ONLY)) {
            $orgidentifier = ($sco->organization == '' && $sco->launch == '') ? $sco->identifier : $sco->organization;
        }
        $first = current($scoes);
        $result = scorm_get_toc($user, $scorm, $cmid, TOCFULLURL, $orgidentifier);
        $url = new \moodle_url('/mod/scorm/player.php', [
            'a' => $scorm->id, 'currentorg' => $orgidentifier, 'display' => 'popup', self::TAB_PARAM => 1,
        ]);
        if (!empty($result->sco->id) && $scorm->forcenewattempt != SCORM_FORCEATTEMPT_ALWAYS) {
            $url->param('scoid', $result->sco->id);
        } else {
            $url->param('scoid', $first->id);
        }
        $oncomplete = $result->incomplete === false && $scorm->forcenewattempt == SCORM_FORCEATTEMPT_ONCOMPLETE;
        if ($scorm->forcenewattempt == SCORM_FORCEATTEMPT_ALWAYS || $oncomplete) {
            $url->param('newattempt', 'on');
        }
        return $url;
    }

    /**
     * The SCORM record of the current activity (the one the page already loaded).
     *
     * @return \stdClass|null
     */
    private static function scorm(): ?\stdClass {
        global $PAGE;
        return $PAGE->activityrecord ?: null;
    }
}
