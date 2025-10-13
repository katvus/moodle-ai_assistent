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
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'question' => $question,
            'questiontime' => $questiontime,
            'sessionid' => $sessionid
        ]);

        $context = \context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('block/aiassistant:ownaction', $context);

        try {
            $today_start = (new \DateTime('today'))->getTimestamp();
            $user_request_limit = get_config('block_aiassistant', 'userlimit');
            $user_id = ($DB->get_record("block_aiassistant_session", 
            ['id' => $params["sessionid"]], 'user_id'))->user_id;
            $sql = 'SELECT COUNT(*)
            FROM {block_aiassistant_messages} messages
            JOIN {block_aiassistant_session} session
            ON session.id = messages.session_id
            WHERE session.user_id = :user_id
            AND messages.question_time >= :today_start';
            $count = $DB->count_records_sql($sql, [
                'user_id' => $user_id,
                'today_start' => $today_start
            ]);
            if ($count >= $user_request_limit){
                return [
                    'status' => 'request_limit'
                ];
            }

            $message_data = [
                'session_id' => $params['sessionid'],
                'question' => $params['question'],
                'question_time' => $params['questiontime'],
                'status' => 'queue'
            ];

            $id = $DB->insert_record("block_aiassistant_messages", $message_data);
            return [
                'status' => 'queue',
                'id' => $id
            ];
        } catch (\dml_exception $e) {
            error_log("DB error: " . $e->getMessage());
            return [
                'status' => 'error'
            ];
        } 
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of answer'),
            'id' => new external_value(PARAM_INT, 'ID in table', VALUE_OPTIONAL),
        ]);
    }
}