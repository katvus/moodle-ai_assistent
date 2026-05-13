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

        if ((get_config('block_aiassistant', 'apikey') == '' or get_config('block_aiassistant', 'catalogid') == '') and get_config('block_aiassistant', 'authorizationkey') == ''){
            $this->content->text = get_string('emptyfield', 'block_aiassistant');
        }
        else { 
            if (get_config('block_aiassistant', 'userlimit') < 0 or get_config('block_aiassistant', 'textarealimit') < 0 ) {
                $this->content->text = get_string('negativefield', 'block_aiassistant');
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
                    'user' => $USER->id,
                    'textarealimit' => get_config('block_aiassistant', 'textarealimit')
                ]);
            }
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

    public function instance_config_save($data, $nolongerused = false) {
        global $USER;
 
        $draftitemid = $data->files ?? 0;
        $usercontext = context_user::instance($USER->id);
        $context = context_block::instance($this->instance->id);
        $fs = get_file_storage();


        $old_file = $fs->get_file(
            $context->id,
            'block_aiassistant',
            'config_teachermaterial',
            0,
            '/',
            'teachermaterial.txt'
        );
        
        if (empty($new_text)) {
            if ($old_file) {
                delete_document_rag($this->instance->id, $old_file->get_id());
                $old_file->delete();
            }
            return;
        }
        
        $need_update = false;
        if (!$old_file) {
            $need_update = true;
        } else {
            $old_content = $old_file->get_content();
            if ($old_content !== $new_text) {
                $need_update = true;
                delete_document_rag($this->instance->id, $old_file->get_id());
                $old_file->delete();
            }
        }
        
        if ($need_update) {
            $text_record = new stdClass();
            $text_record->contextid = $context->id;
            $text_record->component = 'block_aiassistant';
            $text_record->filearea = 'config_teachermaterial';
            $text_record->itemid = 0;
            $text_record->filepath = '/';
            $text_record->filename = 'teachermaterial.txt';
            $text_record->userid = $USER->id;
            $text_record->timecreated = time();
            $text_record->timemodified = time();
            
            $new_file = $fs->create_file_from_string($text_record, $new_text);
            
            add_document_rag($this->instance->id, $new_file->get_id());
        }



        $oldfiles = $fs->get_area_files(
            $context->id,
            'block_aiassistant',
            'config_files',
            0,
            '',
            false
        );

        $newfiles = $fs->get_area_files(
            $usercontext->id,
            'user',
            'draft',
            $draftitemid,
            '',
            false
        );

        $old_by_name = [];
        foreach ($oldfiles as $file) {
            $old_by_name[$file->get_filename()] = $file;
        }
        
        foreach ($newfiles as $newfile) {
            $filename = $newfile->get_filename();
            $file_id = $newfile->get_id();
            
            if (!isset($old_by_name[$filename])) {
                error_log("new file " . $filename);
                add_document_rag($this->instance->id, $file_id);
            } else {
                $oldfile = $old_by_name[$filename];
                if ($oldfile->get_contenthash() !== $newfile->get_contenthash()) {
                    error_log("changed file " . $filename);
                    delete_document_rag($this->instance->id, $oldfile->get_id());
                    add_document_rag($this->instance->id, $newfile->get_id());
                }
                unset($old_by_name[$filename]);
            }
        }
        
        foreach ($old_by_name as $filename => $oldfile) {
            error_log("delete file " . $filename);
            delete_document_rag($this->instance->id, $file_id);
        }

        file_save_draft_area_files(
            $draftitemid,
            $context->id,
            'block_aiassistant',
            'config_files',  
            0
        );

        return parent::instance_config_save($data, $nolongerused);
    }
}
