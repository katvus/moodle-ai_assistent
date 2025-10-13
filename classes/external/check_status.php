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

class check_status extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'ID of request')
        ]);
    }

    public static function execute($id) {
        global $USER, $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'id' => $id
        ]);
        $context = \context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('block/aiassistant:ownaction', $context);

        $record = $DB->get_record(
            "block_aiassistant_messages", 
            ['id' => $params['id']],
            'id, status, answer, answer_time',
        );
        if ('status' !== 'completed') {
            return ['status' => $record->status];
        }
        else {
            return [
                'status' => $record->status,
                'answer' => $record->answer,
                'answertime' => $record->answer_time
            ];
        }
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of answer'),
            'answer' => new external_value(PARAM_TEXT, 'AI answer', VALUE_OPTIONAL),
            'answertime' => new external_value(PARAM_INT, 'Time of the answer', VALUE_OPTIONAL)
        ]);
    }

}