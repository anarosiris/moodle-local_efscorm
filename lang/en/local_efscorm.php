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

/**
 * English strings.
 *
 * @package    local_efscorm
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['exit'] = 'Exit activity';
$string['pluginname'] = 'Direct SCORM Player';
$string['privacy:metadata'] = 'The Direct SCORM Player plugin only changes how SCORM activities are opened and displayed. It stores no personal data.';
$string['setting_directentry'] = 'Enter directly';
$string['setting_directentry_desc'] = 'Students go straight into the content when they click the activity, without the "Enter" page. Teachers and anyone who can view the reports keep the normal page, as Moodle does with "Skip content structure page: Always".';
$string['setting_enabled'] = 'Full-screen player';
$string['setting_enabled_desc'] = 'Show SCORM content on a full-screen page (no navigation bar, no blocks) with its own top bar and exit button. It is the same activity page, so time-tracking plugins keep measuring it. Untick to switch the plugin off completely (direct entry included).';
$string['setting_hidetoc'] = 'Hide the table of contents';
$string['setting_hidetoc_desc'] = 'In the full-screen player, hide the SCORM table of contents even if the activity shows it.';
$string['setting_samewindow'] = 'New-window activities in the same tab';
$string['setting_samewindow_desc'] = 'Activities set to open in a new window are shown in the full-screen player in the current tab instead (with direct entry). Pop-up blockers no longer get in the way and time-tracking plugins measure the content as any other page. Untick to keep the new window for those activities.';
$string['setting_timetracking'] = 'Active time on each SCORM';
$string['setting_timetracking_desc'] = 'This plugin only changes how SCORM activities open. With the Training Evidence Report (local_efreport) or the free Time Clock (local_eftimeclock) by eLearning Fácil, each student\'s active time inside every SCORM is measured and the student sees their clock in the player.';
