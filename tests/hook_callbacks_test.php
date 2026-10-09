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
     * @return \moodle_page
     */
    private function run_hook(string $script, array $params = []): \moodle_page {
        global $PAGE;
        $_GET = $params;
        $PAGE = new \moodle_page();
        $PAGE->set_cm($this->cm, $this->course);
        $PAGE->set_url('/mod/scorm/' . $script . '.php', ['id' => $this->cm->id] + $params);
        $PAGE->set_pagetype('mod-scorm-' . $script);
        $hook = new \core\hook\output\before_http_headers(new \core\output\core_renderer($PAGE, RENDERER_TARGET_GENERAL));
        hook_callbacks::before_http_headers($hook);
        return $PAGE;
    }

    /**
     * Runs the view hook expecting the redirection to the player; returns the message of the exception.
     *
     * @return string
     */
    private function expect_redirect(): string {
        try {
            $this->run_hook('view');
        } catch (\moodle_exception $e) {
            // In tests redirect() throws "Unsupported redirect detected": that is the redirection.
            return $e->getMessage();
        }
        $this->fail('The student was not redirected to the player.');
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
     * A teacher (who can view the reports) keeps the normal activity page.
     */
    public function test_teacher_keeps_the_activity_page(): void {
        $teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->setUser($teacher);
        $page = $this->run_hook('view');
        $this->assertSame('mod-scorm-view', $page->pagetype);
    }

    /**
     * With direct entry off nothing changes; with a new-window activity and the
     * same-tab setting off, nothing changes either.
     */
    public function test_direct_entry_respects_the_settings(): void {
        global $DB;
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($student);

        set_config('directentry', 0, 'local_efscorm');
        $page = $this->run_hook('view');
        $this->assertSame('mod-scorm-view', $page->pagetype);

        set_config('directentry', 1, 'local_efscorm');
        set_config('samewindow', 0, 'local_efscorm');
        $DB->set_field('scorm', 'popup', 1, ['id' => $this->scorm->id]);
        $page = $this->run_hook('view');
        $this->assertSame('mod-scorm-view', $page->pagetype);
    }

    /**
     * A new-window activity with the same-tab setting on: the student is sent to
     * the core player in "popup" display mode, in the current tab; the URL has
     * the first SCO and the organisation, like the core one.
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
        $this->assertSame((string) $scorm->id, $url->get_param('a'));
        $sco = $DB->get_record_select('scorm_scoes', 'scorm = ? AND launch <> ?', [$scorm->id, ''], 'id', MUST_EXIST);
        $this->assertSame((string) $sco->id, $url->get_param('scoid'));
        $this->assertStringContainsString('redirect', $this->expect_redirect());

        // A teacher is never sent in.
        $teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->setUser($teacher);
        $this->assertNull(hook_callbacks::same_tab_url($scorm, $teacher, $context, $this->cm->id));
        $page = $this->run_hook('view');
        $this->assertSame('mod-scorm-view', $page->pagetype);
    }

    /**
     * The player page gets the minimal layout and the body class; the player of a
     * new-window activity only in "popup" display mode and with the same-tab
     * setting on; with the plugin off, never.
     */
    public function test_player_gets_the_fullscreen_layout(): void {
        global $DB;
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($student);

        $page = $this->run_hook('player');
        $this->assertSame('popup', $page->pagelayout);
        $this->assertStringContainsString('local-efscorm-fullscreen', $page->bodyclasses);

        // New-window activity: the launcher page (no display=popup) is not touched...
        $DB->set_field('scorm', 'popup', 1, ['id' => $this->scorm->id]);
        $page = $this->run_hook('player');
        $this->assertNotSame('popup', $page->pagelayout);
        // ...the "popup" player in the same tab gets the layout...
        $page = $this->run_hook('player', ['display' => 'popup']);
        $this->assertSame('popup', $page->pagelayout);
        $this->assertStringContainsString('local-efscorm-fullscreen', $page->bodyclasses);
        // ...unless the same-tab setting is off (the real new window keeps 'embedded').
        set_config('samewindow', 0, 'local_efscorm');
        $page = $this->run_hook('player', ['display' => 'popup']);
        $this->assertNotSame('popup', $page->pagelayout);

        set_config('enabled', 0, 'local_efscorm');
        $DB->set_field('scorm', 'popup', 0, ['id' => $this->scorm->id]);
        $page = $this->run_hook('player');
        $this->assertNotSame('popup', $page->pagelayout);
    }

    /**
     * The CSS and the top bar are only emitted on the full-screen player page.
     */
    public function test_css_and_bar_only_on_the_fullscreen_player(): void {
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($student);

        $page = $this->run_hook('player');
        $renderer = new \core\output\core_renderer($page, RENDERER_TARGET_GENERAL);
        $head = new \core\hook\output\before_standard_head_html_generation($renderer);
        hook_callbacks::before_standard_head_html_generation($head);
        $this->assertStringContainsString('local-efscorm-css', $head->get_output());
        $footer = new \core\hook\output\before_footer_html_generation($renderer);
        hook_callbacks::before_footer_html_generation($footer);
        $this->assertStringContainsString('local-efscorm-bar', $footer->get_output());
        $this->assertStringContainsString(get_string('exit', 'local_efscorm'), $footer->get_output());
    }
}
