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
 * Get request from AI assistant.
 *
 * @package     block_aiassistant
 * @category    admin
 * @copyright   2025 Ekaterina Vasileva <kat.vus8@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_aiassistant\external;

require_once('../../config.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;

use block_aiassistant\aiassistant;

use \moodle_exception;

class api extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'question' => new external_value(PARAM_TEXT, 'Chat message'),
            'questiontime' => new external_value(PARAM_INT, 'Time of sending the message'),
            'sessionid' => new external_value(PARAM_INT, 'Session ID')
        ]);
    }

    public static function execute($question, $questiontime, $sessionid) {
        global $DB, $USER, $OUTPUT;

        $params = self::validate_parameters(self::execute_parameters(), [
            'question' => $question,
            'questiontime' => $questiontime,
            'sessionid' => $sessionid
        ]);

        $context = \context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('block/aiassistant:ownaction', $context);

        $apikey = get_config('block_aiassistant', 'apikey');
        $catalog_id = get_config('block_aiassistant', 'catalogid');
        $ai = new aiassistant($apikey, $catalog_id);
        $message = [];

        try {
            $history_from_db = $DB->get_records(
                "block_aiassistant_messages", 
                ['session_id' => $sessionid],
                'question_time DESC',
                'id, question, answer',
                0,
                $ai->get_history_limit()
            );
            $history = array_reverse($history_from_db);
            foreach ($history as $record){
                array_push($message, 
                ['role' => 'user', 'text' => $record->question], 
                ['role' => 'assistant', 'text' => $record->answer] 
                );
            }
            array_push($message, ['role' => 'user', 'text' => $question]);
            $result = $ai->make_request($message);
            // $notification = html_writer::div(
            //     $result,
            //     'alert alert-danger alert-block fade in' // Bootstrap-классы Moodle
            // );
            // $this->content->text .= $notification;
            $time = new \DateTime();
            $answertime =  $time->getTimestamp();

            $message_data = [
                'session_id' => $sessionid,
                'question' => $question,
                'answer' => $result['text'],
                'question_time' => $questiontime,
                'answer_time' => $answertime
            ];
            $DB->insert_record("block_aiassistant_messages", $message_data);
            return [
                'status' => 'success',
                'answer' => $result['text'],
                'answertime' => $answertime
            ];
        } catch (\dml_exception $e) {
            error_log("DB error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        } catch (\moodle_exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of answer'),
            'message' => new external_value(PARAM_TEXT, 'Status of answer', VALUE_OPTIONAL),
            'answer' => new external_value(PARAM_TEXT, 'AI answer', VALUE_OPTIONAL),
            'answertime' => new external_value(PARAM_INT, 'Time of the answer', VALUE_OPTIONAL)
        ]);
    }
}