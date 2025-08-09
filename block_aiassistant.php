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
 * Block aiassistant is defined here.
 *
 * @package     block_aiassistant
 * @copyright   2025 Ekaterina Vasileva <kat.vus8@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class block_aiassistant extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_aiassistant');
    }
    public function get_content() {
        global $OUTPUT;
        global $USER;

        if ($this->content !== null) return $this->content;

        $this->content = new stdClass();

        if (get_config('block_aiassistant', 'apikey') == '' or get_config('block_aiassistant', 'catalogid') == ''){
            $this->content->text = get_string('emptyfield', 'block_aiassistant');
        }
        else {
            $this->page->requires->js_call_amd('block_aiassistant/chat', 'init', ['instanceid' => $this->instance->id]);
            $this->content->text = html_writer::tag('button', get_string('newchat', 'block_aiassistant'), 
            ['type' => 'button', 'data-action' =>'new-chat', 'data-instance-id' => $this->instance->id]);

            $this->content->text .= html_writer::div(
                '', 
                'chat', 
                [
                    'data-role' => 'chat',
                    'id' => 'chat-' . $this->instance->id . '-' . $USER->id,
                    'data-user-id' => $USER->id,
                    'data-instance-id' => $this->instance->id
                ]
            );
            $this->content->footer = $OUTPUT->render_from_template('block_aiassistant/footer', [
                'instance' => $this->instance->id,
                'user' => $USER->id
            ]);
        }
        return $this->content;
    }

    public function specialization() {

        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_config('block_aiassistant', 'pluginheading');
            //$this->title = get_string('pluginname', 'block_aiassistant');
        } else {
            $this->title = $this->config->title;
        }
    }

    public function has_config() {
        return true;
    }

    public function applicable_formats() {
        return ['course-view' => true];
    }

    public function instance_allow_multiple() { return false; }
    
    public function get_aria_role() { return 'complementary'; }
}
