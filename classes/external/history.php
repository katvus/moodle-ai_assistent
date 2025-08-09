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
use core_external\external_multiple_structure;

class history extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'sessionid' => new external_value(PARAM_INT, 'Session ID'),
        ]);
    }

    public static function execute($sessionid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'sessionid' => $sessionid,
        ]);

        $context = \context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('block/aiassistant:ownaction', $context);

        $message = [];
        $history = $DB->get_records('block_aiassistant_messages', ['session_id' => $params['sessionid']]);
        foreach ($history as $record){
            array_push($message, 
            ['role' => 'user', 'text' => $record->question, 'time' => $record->questiontime], 
            ['role' => 'assistant', 'text' => $record->answer, 'time' => $record->questiontime] 
            );
        }
        return $message;
    }

    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'role' => new external_value(PARAM_TEXT, 'Who is the author: user or assistant'),
                'text' => new external_value(PARAM_TEXT, 'Message'),
                'time' => new external_value(PARAM_INT, 'When the message was send'),
            ]),
            'Messages in session'
        );
    }
}