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
 * core player page (player.php) gets the theme's 'popup' layout plus our own
 * CSS/JS so the content fills the window. It is still the activity page, so
 * time-tracking plugins (local_efreport, local_eftimeclock) keep measuring it.
 *
 * Activities set to open in a new window are, with the "same tab" setting,
 * sent to the core player in its "popup" display mode but in the current tab:
 * the content the activity would show in the new window, measured like any
 * other page and without pop-up blockers in the way.
 *
 * @package    local_efscorm
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /** @var bool Whether the current page is being rendered as the full-screen player. */
    private static bool $fullscreen = false;

    /**
     * Before the headers: direct entry on view.php, full-screen layout on player.php.
     *
     * @param \core\hook\output\before_http_headers $hook The hook.
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        global $PAGE;

        self::$fullscreen = false;
        if (empty(get_config('local_efscorm', 'enabled')) || !$PAGE->cm || $PAGE->cm->modname !== 'scorm') {
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
        if (!$scorm) {
            return;
        }
        $popupdisplay = optional_param('display', '', PARAM_ALPHA) === 'popup';
        if (empty($scorm->popup) && !$popupdisplay) {
            // In the current window: the theme's minimal layout (no navbar, blocks or footer).
            self::fullscreen();
        } else if (!empty($scorm->popup) && $popupdisplay && self::same_tab()) {
            // The new-window player, shown in the current tab: same minimal layout instead of 'embedded'.
            self::fullscreen();
        }
    }

    /**
     * Marks the current page as the full-screen player.
     */
    private static function fullscreen(): void {
        global $PAGE;
        $PAGE->set_pagelayout('popup');
        $PAGE->add_body_class('local-efscorm-fullscreen');
        self::$fullscreen = true;
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
     * the normal page). Activities that open in a new window are sent to the
     * player in the current tab when the "same tab" setting is on, and left
     * alone otherwise.
     */
    private static function direct_entry(): void {
        global $CFG, $PAGE, $USER;

        if (empty(get_config('local_efscorm', 'directentry'))) {
            return;
        }
        $scorm = self::scorm();
        if (!$scorm) {
            return;
        }
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');
        if (empty($scorm->popup)) {
            $forced = clone $scorm;
            $forced->skipview = SCORM_SKIPVIEW_ALWAYS;
            // Redirects when the user may go in; returns otherwise.
            scorm_simple_play($forced, $USER, $PAGE->context, $PAGE->cm->id);
            return;
        }
        if (!self::same_tab()) {
            return;
        }
        $url = self::same_tab_url($scorm, $USER, $PAGE->context, $PAGE->cm->id);
        if ($url) {
            redirect($url);
        }
    }

    /**
     * The URL of the new-window player ("popup" display mode) for the current
     * tab, built as scorm_simple_play() builds the one of the current-window
     * player: same first SCO, same organisation, same new-attempt rules.
     * Null when the user may not be sent in (report viewers) or when the
     * package has nothing to launch.
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
            'a' => $scorm->id, 'currentorg' => $orgidentifier, 'display' => 'popup',
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
     * The SCORM record of the current activity.
     *
     * @return \stdClass|null
     */
    private static function scorm(): ?\stdClass {
        global $DB, $PAGE;
        return $DB->get_record('scorm', ['id' => $PAGE->cm->instance]) ?: null;
    }

    /**
     * CSS of the full-screen player, only on that page. Neutral colours
     * (dark bar, white text) so it fits any theme.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook The hook.
     */
    public static function before_standard_head_html_generation(
        \core\hook\output\before_standard_head_html_generation $hook
    ): void {
        if (!self::$fullscreen) {
            return;
        }
        $hidetoc = !empty(get_config('local_efscorm', 'hidetoc'));
        $css = '
body.local-efscorm-fullscreen { overflow: hidden; }
.local-efscorm-fullscreen #page, .local-efscorm-fullscreen #page-wrapper, .local-efscorm-fullscreen #page-content,
.local-efscorm-fullscreen #region-main-box, .local-efscorm-fullscreen #region-main, .local-efscorm-fullscreen [role="main"] {
    margin: 0; padding: 0; max-width: none; }
.local-efscorm-fullscreen #scormpage { position: fixed !important; top: 48px !important; left: 0 !important;
    right: 0 !important; bottom: 0 !important; width: auto !important; height: auto !important; margin: 0 !important;
    padding: 0 !important; overflow: auto !important; background: #fff; }
.local-efscorm-fullscreen #scormpage #scorm_layout { height: 100%; }
.local-efscorm-fullscreen #scorm_object { border: 0; display: block;
    width: calc(100% - var(--local-efscorm-toc, 0px)) !important;
    height: calc(100vh - 48px - var(--local-efscorm-top, 0px)) !important; min-height: 200px; }
.local-efscorm-fullscreen .local-efscorm-coreexit { display: none !important; }
#local-efscorm-bar { position: fixed; top: 0; left: 0; right: 0; height: 48px; z-index: 1040; display: flex;
    align-items: center; justify-content: space-between; gap: 12px; padding: 0 14px; background: #1f2937; color: #fff;
    font: 600 15px/1.2 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
#local-efscorm-bar .local-efscorm-title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
#local-efscorm-bar a.local-efscorm-exit { flex: none; color: #1f2937; background: #fff; border-radius: 999px;
    padding: 8px 16px; text-decoration: none; font-weight: 700; }
#local-efscorm-bar a.local-efscorm-exit:hover, #local-efscorm-bar a.local-efscorm-exit:focus { background: #e5e7eb;
    color: #111827; outline: 2px solid #fff; outline-offset: 2px; }
' . ($hidetoc ? '.local-efscorm-fullscreen #scorm_toc, .local-efscorm-fullscreen #scorm_toc_toggle { display: none !important; }
.local-efscorm-fullscreen #scorm_content { margin-left: 0 !important; width: 100% !important; }' : '');
        $hook->add_html('<style id="local-efscorm-css">' . $css . '</style>');
    }

    /**
     * The top bar with the activity name and the exit button (same link as the
     * core exit button, so the SCORM is closed and saved the usual way; in the
     * new-window player there is no core button, so the link goes back to the
     * section of the course, as the core button would).
     *
     * @param \core\hook\output\before_footer_html_generation $hook The hook.
     */
    public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
        global $PAGE;

        if (!self::$fullscreen) {
            return;
        }
        $title = format_string($PAGE->cm->name, true, ['context' => $PAGE->context]);
        $exit = get_string('exit', 'local_efscorm');
        $fallback = course_get_url($PAGE->course, $PAGE->cm->sectionnum)->out(false);
        $data = json_encode(['title' => $title, 'exit' => $exit, 'fallback' => $fallback], JSON_HEX_TAG | JSON_HEX_AMP);
        $hook->add_html('<script id="local-efscorm-js">(function () {
    var d = ' . $data . ';
    var core = document.querySelector("#page-mod-scorm-player .flex-row-reverse > a.btn, " +
        "#page-mod-scorm-player .d-flex.flex-row-reverse a.btn");
    var href = core ? core.getAttribute("href") : d.fallback;
    if (core && core.parentElement) { core.parentElement.classList.add("local-efscorm-coreexit"); }
    var bar = document.createElement("div");
    bar.id = "local-efscorm-bar";
    var t = document.createElement("span"); t.className = "local-efscorm-title"; t.textContent = d.title;
    var a = document.createElement("a"); a.className = "local-efscorm-exit"; a.href = href; a.textContent = d.exit;
    bar.appendChild(t); bar.appendChild(a);
    document.body.insertBefore(bar, document.body.firstChild);
    var fit = function () {
        var o = document.getElementById("scorm_object");
        var page = document.getElementById("scormpage");
        if (!o || !page) { return; }
        var toc = document.getElementById("scorm_toc");
        var top = o.getBoundingClientRect().top - page.getBoundingClientRect().top + page.scrollTop;
        document.documentElement.style.setProperty("--local-efscorm-top", Math.max(0, Math.round(top)) + "px");
        document.documentElement.style.setProperty("--local-efscorm-toc", (toc && toc.offsetParent ? toc.offsetWidth : 0) + "px");
    };
    fit();
    window.addEventListener("resize", fit);
    setTimeout(fit, 500); setTimeout(fit, 2000);
})();</script>');
    }
}
