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
 * Tests for the hook callbacks.
 *
 * @package    local_efscorm
 * @category   test
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_efscorm\hook_callbacks
 */
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
     * @return \moodle_page
     */
    private function run_hook(string $script): \moodle_page {
        global $PAGE;
        $PAGE = new \moodle_page();
        $PAGE->set_cm($this->cm, $this->course);
        $PAGE->set_url('/mod/scorm/' . $script . '.php', ['id' => $this->cm->id]);
        $PAGE->set_pagetype('mod-scorm-' . $script);
        $hook = new \core\hook\output\before_http_headers(new \core\output\core_renderer($PAGE, RENDERER_TARGET_GENERAL));
        hook_callbacks::before_http_headers($hook);
        return $PAGE;
    }

    /**
     * A student opening the activity page is sent to the player.
     */
    public function test_student_is_sent_to_the_player(): void {
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($student);
        // In tests redirect() throws "Unsupported redirect detected": that is the redirection.
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessageMatches('/redirect/');
        $this->run_hook('view');
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
     * With the setting off, or with an activity that opens in a new window, nothing changes.
     */
    public function test_direct_entry_respects_the_settings(): void {
        global $DB;
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($student);

        set_config('directentry', 0, 'local_efscorm');
        $page = $this->run_hook('view');
        $this->assertSame('mod-scorm-view', $page->pagetype);

        set_config('directentry', 1, 'local_efscorm');
        $DB->set_field('scorm', 'popup', 1, ['id' => $this->scorm->id]);
        $page = $this->run_hook('view');
        $this->assertSame('mod-scorm-view', $page->pagetype);
    }

    /**
     * The player page gets the minimal layout and the body class; a new-window player does not.
     */
    public function test_player_gets_the_fullscreen_layout(): void {
        global $DB;
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->setUser($student);

        $page = $this->run_hook('player');
        $this->assertSame('popup', $page->pagelayout);
        $this->assertStringContainsString('local-efscorm-fullscreen', $page->bodyclasses);

        $DB->set_field('scorm', 'popup', 1, ['id' => $this->scorm->id]);
        $page = $this->run_hook('player');
        $this->assertNotSame('popup', $page->pagelayout);

        set_config('enabled', 0, 'local_efscorm');
        $DB->set_field('scorm', 'popup', 0, ['id' => $this->scorm->id]);
        $page = $this->run_hook('player');
        $this->assertNotSame('popup', $page->pagelayout);
    }
}
