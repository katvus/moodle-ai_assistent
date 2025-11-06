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
 * Plugin administration pages are defined here.
 *
 * @package     block_aiassistant
 * @category    admin
 * @copyright   2025 Ekaterina Vasileva <kat.vus8@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('block_aiassistant_settings', get_string('adminpageheading', 'block_aiassistant'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configtext(
            'block_aiassistant/pluginheading', 
            get_string('pluginheading', 'block_aiassistant'),
            get_string('pluginheading', 'block_aiassistant'),
            get_string('pluginname', 'block_aiassistant')
        ));

        $settings->add(new admin_setting_configselect(
            'block_aiassistant/selectai', 
            get_string('selectai', 'block_aiassistant'),
            get_string('selectaidesc', 'block_aiassistant'),
            'yandex',
            [
                'yandex' => 'YandexGPT',
                'gigachat' => 'GigaChat'
            ]
        ));

        $settings->add(new admin_setting_configtext(
            'block_aiassistant/apikey', 
            get_string('apikey', 'block_aiassistant'),
            get_string('apikeydesc', 'block_aiassistant'),
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
            'block_aiassistant/catalogid', 
            get_string('catalogid', 'block_aiassistant'),
            get_string('catalogiddesc', 'block_aiassistant'),
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
            'block_aiassistant/authorizationkey', 
            get_string('authorizationkey', 'block_aiassistant'),
            get_string('authorizationkeydesc', 'block_aiassistant'),
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
            'block_aiassistant/userlimit', 
            get_string('userlimit', 'block_aiassistant'),
            get_string('userlimitdesc', 'block_aiassistant'),
            5,
            PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'block_aiassistant/textarealimit', 
            get_string('textarealimit', 'block_aiassistant'),
            get_string('textarealimitdesc', 'block_aiassistant'),
            100,
            PARAM_INT
        ));

        $settings->add(new admin_setting_configcheckbox(
            'block_aiassistant/coursecontext', 
            get_string('coursecontext', 'block_aiassistant'),
            get_string('coursecontextdesc', 'block_aiassistant'),
            1
        ));

        $settings->add(new admin_setting_heading(
            'block_aiassistant/priotitymanagment',
            get_string('prioritymanagement', 'block_aiassistant'),
            html_writer::link(
                new moodle_url('/blocks/aiassistant/admin_priority.php'),
                get_string('managepriorities', 'block_aiassistant'),
                ['class' => 'transition-button']
            )
        ));
    }
}
