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
 * Tests of the hook callbacks: direct entry, same-tab player and full-screen layout.
 *
 * @package    local_efscorm
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(hook_callbacks::class)]
final class hook_callbacks_test extends \advanced_testcase {
    /** @var \stdClass Course. */
    private $course;
    /** @var \stdClass SCORM activity (module record). */
    private $scorm;
    /** @var \cm_info Course module. */
    private $cm;

    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $this->course = $gen->create_course();
        $this->scorm = $gen->create_module('scorm', [
            'course' => $this->course->id,
            'packagefilepath' => $CFG->dirroot . '/mod/scorm/tests/packages/singlescobasic.zip',
        ]);
        $this->cm = get_fast_modinfo($this->course)->get_cm($this->scorm->cmid);
    }

    /**
     * Prepares $PAGE as the given SCORM page and runs the before_http_headers callback.
     *
     * @param string $script 'view' or 'player'.
     * @param array $params Extra URL parameters (e.g. display=popup), also set as request parameters.
     * @param \cm_info|null $cm The course module (the one of setUp() by default).
     * @return \moodle_page
     */
    private function run_hook(string $script, array $params = [], ?\cm_info $cm = null): \moodle_page {
        global $PAGE;
        $cm = $cm ?? $this->cm;
        $_GET = $params;
        // A redirection in a previous call leaves the "redirecting" guard set (redirect() throws in tests).
        $guard = new \ReflectionProperty(hook_callbacks::class, 'redirecting');
        $guard->setValue(null, false);
        $PAGE = new \moodle_page();
        $PAGE->set_cm($cm, get_course($cm->course));
        $PAGE->set_url('/mod/scorm/' . $script . '.php', ['id' => $cm->id] + $params);
        $PAGE->set_pagetype('mod-scorm-' . $script);
        $hook = new \core\hook\output\before_http_headers(new \core\output\core_renderer($PAGE, RENDERER_TARGET_GENERAL));
        hook_callbacks::before_http_headers($hook);
        return $PAGE;
    }

    /**
     * Runs the view hook expecting the redirection to the player; returns the message of the exception.
     *
     * @param array $params Extra URL parameters.
     * @return string
     */
    private function expect_redirect(array $params = []): string {
        try {
            $this->run_hook('view', $params);
        } catch (\moodle_exception $e) {
            // In tests redirect() throws "Unsupported redirect detected": that is the redirection.
            return $e->getMessage();
        }
        $this->fail('The student was not redirected to the player.');
    }

    /**
     * Runs the view hook expecting no redirection (the activity page stays).
     *
     * @param array $params Extra URL parameters.
     * @param \cm_info|null $cm The course module.
     */
    private function expect_activity_page(array $params = [], ?\cm_info $cm = null): void {
        $page = $this->run_hook('view', $params, $cm);
        $this->assertSame('mod-scorm-view', $page->pagetype);
    }

    /**
     * The data (title, exit, fallback, force) the page hands to the player
     * module through js_call_amd(), on the given page.
     *
     * @param \moodle_page $page
     * @return array|null Null when the module is not called (not the full-screen player).
     */
    private function bar_data(\moodle_page $page): ?array {
        $property = new \ReflectionProperty(\core\output\requirements\page_requirements_manager::class, 'amdjscode');
        $code = implode("\n", $property->getValue($page->requires));
        $call = "/require\\(\\['local_efscorm\\/player'\\], function\\(amd\\) \\{amd\\.init\\((\\{.*?\\})\\);/s";
        if (!preg_match($call, $code, $m)) {
            return null;
        }
        return json_decode($m[1], true);
    }

    /**
     * A student opening the activity page is sent to the player.
     */
    public function test_student_is_sent_to_the_player(): void {
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($student);
        $this->assertStringContainsString('redirect', $this->expect_redirect());
    }

    /**
     * Teachers (editing or not: both can view the reports) keep the normal activity page.
     */
    public function test_teachers_keep_the_activity_page(): void {
        $gen = $this->getDataGenerator();
        $this->setUser($gen->create_and_enrol($this->course, 'editingteacher'));
        $this->expect_activity_page();
        $this->setUser($gen->create_and_enrol($this->course, 'teacher'));
        $this->expect_activity_page();
    }

    /**
     * With the plugin or the direct entry off nothing changes; with a new-window
     * activity and the same-tab setting off, nothing changes either.
     */
    public function test_direct_entry_respects_the_settings(): void {
        global $DB;
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($student);

        set_config('enabled', 0, 'local_efscorm');
        $this->expect_activity_page();
        set_config('enabled', 1, 'local_efscorm');

        set_config('directentry', 0, 'local_efscorm');
        $this->expect_activity_page();

        set_config('directentry', 1, 'local_efscorm');
        set_config('samewindow', 0, 'local_efscorm');
        $DB->set_field('scorm', 'popup', 1, ['id' => $this->scorm->id]);
        $this->expect_activity_page();
    }

    /**
     * The guards of the core activity page are honoured: preventskip (set by the
     * core redirections), the mod/scorm:skipview capability (students have it,
     * guests do not) and the open/close dates.
     */
    public function test_direct_entry_honours_the_core_guards(): void {
        global $DB;
        $gen = $this->getDataGenerator();
        $student = $gen->create_and_enrol($this->course, 'student');
        $this->setUser($student);

        // Sanity check: the student is sent in...
        $this->assertStringContainsString('redirect', $this->expect_redirect());
        // ...but not with preventskip=1.
        $this->expect_activity_page(['preventskip' => 1]);

        // Not with the skipview capability prohibited for the role.
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        assign_capability('mod/scorm:skipview', CAP_PROHIBIT, $studentrole, \context_module::instance($this->cm->id));
        $this->expect_activity_page();
        unassign_capability('mod/scorm:skipview', $studentrole, \context_module::instance($this->cm->id));

        // Not when the activity is not open yet or already closed.
        $DB->set_field('scorm', 'timeopen', time() + DAYSECS, ['id' => $this->scorm->id]);
        $this->expect_activity_page();
        $DB->set_field('scorm', 'timeopen', 0, ['id' => $this->scorm->id]);
        $DB->set_field('scorm', 'timeclose', time() - DAYSECS, ['id' => $this->scorm->id]);
        $this->expect_activity_page();
        $DB->set_field('scorm', 'timeclose', 0, ['id' => $this->scorm->id]);

        // Not when the package has nothing to launch (nor for a new-window activity).
        $DB->set_field('scorm_scoes', 'launch', '', ['scorm' => $this->scorm->id]);
        $this->expect_activity_page();
        $DB->set_field('scorm', 'popup', 1, ['id' => $this->scorm->id]);
        $this->expect_activity_page();
        $scorm = $DB->get_record('scorm', ['id' => $this->scorm->id]);
        $context = \context_module::instance($this->cm->id);
        $this->assertNull(hook_callbacks::same_tab_url($scorm, $student, $context, $this->cm->id));

        // Guests have no skipview capability.
        $this->setGuestUser();
        $this->expect_activity_page();
    }

    /**
     * A new-window activity with the same-tab setting on: the student is sent to
     * the core player in "popup" display mode, in the current tab; the URL has
     * the first SCO and the organisation, like the core one, plus our mark.
     */
    public function test_new_window_activity_opens_in_the_same_tab(): void {
        global $DB;
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $DB->set_field('scorm', 'popup', 1, ['id' => $this->scorm->id]);
        $scorm = $DB->get_record('scorm', ['id' => $this->scorm->id]);
        $context = \context_module::instance($this->cm->id);

        $this->setUser($student);
        $url = hook_callbacks::same_tab_url($scorm, $student, $context, $this->cm->id);
        $this->assertNotNull($url);
        $this->assertSame('popup', $url->get_param('display'));
        $this->assertSame('1', $url->get_param(hook_callbacks::TAB_PARAM));
        $this->assertSame((string) $scorm->id, $url->get_param('a'));
        $sco = $DB->get_record_select('scorm_scoes', 'scorm = ? AND launch <> ?', [$scorm->id, ''], 'id', MUST_EXIST);
        $this->assertSame((string) $sco->id, $url->get_param('scoid'));
        $this->assertNull($url->get_param('newattempt'));
        $this->assertStringContainsString('redirect', $this->expect_redirect());

        // Force new attempt "Always" asks for a new attempt, as the core does.
        $scorm->forcenewattempt = SCORM_FORCEATTEMPT_ALWAYS;
        $url = hook_callbacks::same_tab_url($scorm, $student, $context, $this->cm->id);
        $this->assertSame('on', $url->get_param('newattempt'));

        // A teacher is never sent in.
        $teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->setUser($teacher);
        $this->assertNull(hook_callbacks::same_tab_url($scorm, $teacher, $context, $this->cm->id));
        $this->expect_activity_page();
    }

    /**
     * The player in the current tab gets the minimal layout and the body class,
     * whatever its display mode (also display=popup typed by hand on a normal
     * activity); the real new window of a new-window activity (display=popup
     * without our mark) is left alone; with the plugin off, never.
     */
    public function test_player_gets_the_fullscreen_layout(): void {
        global $DB;
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($student);

        $page = $this->run_hook('player');
        $this->assertSame('popup', $page->pagelayout);
        $this->assertStringContainsString('local-efscorm-fullscreen', $page->bodyclasses);
        $page = $this->run_hook('player', ['display' => 'popup']);
        $this->assertSame('popup', $page->pagelayout);

        // New-window activity: the real new window is not touched...
        $DB->set_field('scorm', 'popup', 1, ['id' => $this->scorm->id]);
        $page = $this->run_hook('player', ['display' => 'popup']);
        $this->assertNotSame('popup', $page->pagelayout);
        $this->assertStringNotContainsString('local-efscorm-fullscreen', $page->bodyclasses);
        // ...the "popup" player opened by the plugin in the same tab gets the layout...
        $page = $this->run_hook('player', ['display' => 'popup', hook_callbacks::TAB_PARAM => 1]);
        $this->assertSame('popup', $page->pagelayout);
        $this->assertStringContainsString('local-efscorm-fullscreen', $page->bodyclasses);
        // ...and so does the current-window player (the form without JavaScript).
        $page = $this->run_hook('player');
        $this->assertSame('popup', $page->pagelayout);

        set_config('enabled', 0, 'local_efscorm');
        $DB->set_field('scorm', 'popup', 0, ['id' => $this->scorm->id]);
        $page = $this->run_hook('player');
        $this->assertNotSame('popup', $page->pagelayout);
    }

    /**
     * The body classes the stylesheet keys on and the player module (with the
     * data of the top bar) are only set on the full-screen player page; the
     * activity name travels as plain text (the module sets it as text) and
     * the exit falls back to the section of the course.
     */
    public function test_classes_and_bar_only_on_the_fullscreen_player(): void {
        global $DB;
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($student);
        $DB->set_field('scorm', 'name', 'Multi SCO & TOC <b>1</b>', ['id' => $this->scorm->id]);
        rebuild_course_cache($this->course->id, true);
        $this->cm = get_fast_modinfo($this->course)->get_cm($this->scorm->cmid);

        $page = $this->run_hook('player');
        $this->assertStringContainsString('local-efscorm-fullscreen', $page->bodyclasses);
        $this->assertStringNotContainsString('local-efscorm-hidetoc', $page->bodyclasses);
        $data = $this->bar_data($page);
        $this->assertNotNull($data);
        $this->assertSame('Multi SCO & TOC 1', $data['title']);
        $this->assertSame(get_string('exit', 'local_efscorm'), $data['exit']);
        $this->assertFalse($data['force']);
        $this->assertSame(course_get_url($this->course, $this->cm->sectionnum)->out(false), $data['fallback']);

        // The hidden table of contents, on request: a body class the stylesheet acts on.
        set_config('hidetoc', 1, 'local_efscorm');
        $page = $this->run_hook('player');
        $this->assertStringContainsString('local-efscorm-hidetoc', $page->bodyclasses);

        // Not on the activity page.
        $this->setUser($this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher'));
        $page = $this->run_hook('view');
        $this->assertStringNotContainsString('local-efscorm-', $page->bodyclasses);
        $this->assertNull($this->bar_data($page));
    }

    /**
     * In a "single activity" course the course page is the activity itself: the
     * exit goes to the site home (as the core does to avoid a redirection loop),
     * overriding the core button; teachers keep the course link.
     */
    public function test_single_activity_course_exits_to_the_site_home(): void {
        global $CFG;
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['format' => 'singleactivity', 'activitytype' => 'scorm']);
        $scorm = $gen->create_module('scorm', [
            'course' => $course->id,
            'packagefilepath' => $CFG->dirroot . '/mod/scorm/tests/packages/singlescobasic.zip',
        ]);
        $cm = get_fast_modinfo($course)->get_cm($scorm->cmid);

        $this->setUser($gen->create_and_enrol($course, 'student'));
        $data = $this->bar_data($this->run_hook('player', [], $cm));
        $this->assertTrue($data['force']);
        $this->assertSame($CFG->wwwroot . '/', $data['fallback']);

        $this->setUser($gen->create_and_enrol($course, 'editingteacher'));
        $data = $this->bar_data($this->run_hook('player', [], $cm));
        $this->assertFalse($data['force']);
        $this->assertSame(course_get_url($course, $cm->sectionnum)->out(false), $data['fallback']);
    }
}
