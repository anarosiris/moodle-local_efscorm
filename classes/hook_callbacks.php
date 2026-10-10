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

    /** @var bool Whether the current page is being rendered as the full-screen player. */
    private static bool $fullscreen = false;

    /** @var bool Set while redirecting: redirect() may render a page (debug notices), which runs the hook again. */
    private static bool $redirecting = false;

    /**
     * Before the headers: direct entry on view.php, full-screen layout on player.php.
     *
     * @param \core\hook\output\before_http_headers $hook The hook.
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        global $PAGE;

        self::$fullscreen = false;
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
        // Notes: #scorm_content already leaves room for the table of contents (75 % wide), so the iframe
        // takes 100 % of it; its height is the window minus the bar, whatever sits above it (the
        // collapsed table of contents) and below it (the SCO navigation panel), measured by the script;
        // the core sets an inline height of at least 680px on #scorm_layout, hence the !important; the
        // notices the core prints outside #scormpage (not open yet, hidden, no attempts left) land below
        // the fixed bar thanks to the padding on the main region.
        $css = '
body.local-efscorm-fullscreen { overflow: hidden; }
.local-efscorm-fullscreen #page, .local-efscorm-fullscreen #page-wrapper, .local-efscorm-fullscreen #page-content,
.local-efscorm-fullscreen #region-main-box, .local-efscorm-fullscreen #region-main, .local-efscorm-fullscreen [role="main"] {
    margin: 0; padding: 0; max-width: none; }
.local-efscorm-fullscreen #region-main { padding-top: 48px; }
.local-efscorm-fullscreen #scormpage { position: fixed !important; top: 48px !important; left: 0 !important;
    right: 0 !important; bottom: 0 !important; width: auto !important; height: auto !important; margin: 0 !important;
    padding: 0 !important; overflow: auto !important; background: #fff; }
.local-efscorm-fullscreen #scormpage #scorm_layout { height: auto !important; margin-bottom: 0 !important; }
.local-efscorm-fullscreen #scorm_object { border: 0; display: block; width: 100% !important;
    height: calc(100vh - 48px - var(--local-efscorm-top, 0px) - var(--local-efscorm-bottom, 0px)) !important;
    height: calc(100dvh - 48px - var(--local-efscorm-top, 0px) - var(--local-efscorm-bottom, 0px)) !important;
    min-height: 200px; }
.local-efscorm-fullscreen .local-efscorm-coreexit { display: none !important; }
#local-efscorm-bar { position: fixed; top: 0; left: 0; right: 0; height: 48px; z-index: 1040; display: flex;
    align-items: center; justify-content: space-between; gap: 12px; padding: 0 14px; background: #1f2937; color: #fff;
    font: 600 15px/1.2 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
#local-efscorm-bar h1.local-efscorm-title { font: inherit; color: inherit; margin: 0; overflow: hidden;
    text-overflow: ellipsis; white-space: nowrap; }
#local-efscorm-bar a.local-efscorm-exit { flex: none; display: inline-flex; align-items: center; min-height: 40px;
    color: #1f2937; background: #fff; border-radius: 999px; padding: 0 18px; text-decoration: none; font-weight: 700; }
#local-efscorm-bar a.local-efscorm-exit:hover { background: #e5e7eb; color: #111827; }
#local-efscorm-bar a.local-efscorm-exit:focus-visible { background: #e5e7eb; color: #111827;
    outline: 2px solid #fff; outline-offset: 2px; }
' . ($hidetoc ? '.local-efscorm-fullscreen #scorm_toc, .local-efscorm-fullscreen #scorm_toc_toggle,
.local-efscorm-fullscreen #scormnav { display: none !important; }
.local-efscorm-fullscreen #scorm_content { margin-left: 0 !important; width: 100% !important; }' : '');
        $hook->add_html('<style id="local-efscorm-css">' . $css . '</style>');
    }

    /**
     * The top bar with the activity name and the exit button (same link as the
     * core exit button, so the SCORM is closed and saved the usual way; in the
     * new-window player there is no core button, so the link goes back to the
     * section of the course, as the core button would). In a "single activity"
     * course the course page is the activity itself, so the exit goes to the
     * site home, as the core does to avoid a redirection loop.
     *
     * @param \core\hook\output\before_footer_html_generation $hook The hook.
     */
    public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
        global $CFG, $PAGE;

        if (!self::$fullscreen) {
            return;
        }
        $singleactivity = course_get_format($PAGE->course)->get_format() === 'singleactivity'
            && !has_capability('mod/scorm:viewreport', $PAGE->context);
        $fallback = $singleactivity ? $CFG->wwwroot . '/' : course_get_url($PAGE->course, $PAGE->cm->sectionnum)->out(false);
        $data = [
            'title' => format_string($PAGE->cm->name, true, ['context' => $PAGE->context, 'escape' => false]),
            'exit' => get_string('exit', 'local_efscorm'),
            'fallback' => $fallback,
            'force' => $singleactivity,
        ];
        $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP);
        $hook->add_html('<script id="local-efscorm-js">(function () {
    var d = ' . $json . ';
    var core = document.querySelector("#page-mod-scorm-player .flex-row-reverse > a.btn, " +
        "#page-mod-scorm-player .d-flex.flex-row-reverse a.btn");
    var href = (core && !d.force) ? core.getAttribute("href") : d.fallback;
    if (core && core.parentElement) { core.parentElement.classList.add("local-efscorm-coreexit"); }
    var bar = document.createElement("header");
    bar.id = "local-efscorm-bar"; bar.setAttribute("role", "banner");
    var t = document.createElement("h1"); t.className = "local-efscorm-title";
    t.textContent = d.title; t.title = d.title;
    var a = document.createElement("a"); a.className = "local-efscorm-exit"; a.href = href; a.textContent = d.exit;
    bar.appendChild(t); bar.appendChild(a);
    document.body.insertBefore(bar, document.body.firstChild);
    var fit = function () {
        var o = document.getElementById("scorm_object");
        var page = document.getElementById("scormpage");
        if (!o || !page) { return; }
        var top = o.getBoundingClientRect().top - page.getBoundingClientRect().top + page.scrollTop;
        var nav = document.getElementById("scorm_navpanel");
        var bottom = (nav && nav.offsetParent) ? nav.offsetHeight : 0;
        document.documentElement.style.setProperty("--local-efscorm-top", Math.max(0, Math.round(top)) + "px");
        document.documentElement.style.setProperty("--local-efscorm-bottom", Math.round(bottom) + "px");
    };
    var refit = function () { window.requestAnimationFrame(fit); setTimeout(fit, 300); };
    fit();
    window.addEventListener("resize", refit);
    setTimeout(fit, 500); setTimeout(fit, 2000);
    document.addEventListener("click", function (e) {
        if (e.target && e.target.closest && e.target.closest("#scorm_toc_toggle")) { refit(); }
    });
    var content = document.getElementById("scorm_content");
    if (content && window.ResizeObserver) { new ResizeObserver(refit).observe(content); }
})();</script>');
    }
}
