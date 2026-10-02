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
 * Site settings.
 *
 * @package    local_efscorm
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_efscorm', get_string('pluginname', 'local_efscorm'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_efscorm/enabled',
        get_string('setting_enabled', 'local_efscorm'),
        get_string('setting_enabled_desc', 'local_efscorm'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_efscorm/directentry',
        get_string('setting_directentry', 'local_efscorm'),
        get_string('setting_directentry_desc', 'local_efscorm'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_efscorm/hidetoc',
        get_string('setting_hidetoc', 'local_efscorm'),
        get_string('setting_hidetoc_desc', 'local_efscorm'),
        0
    ));
}
