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

class session extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'isNew' => new external_value(PARAM_BOOL, 'Need to start new session or continue previous one', VALUE_DEFAULT, false),
        ]);
    }

    public static function execute($isNew) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'isNew' => $isNew,
        ]);

        $context = \context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('block/aiassistant:ownaction', $context);

        $active = $DB->get_record(
            'block_aiassistant_session', 
            [
                'user_id' => $USER->id,
                'context_id' => $context->id,
                'status' => 1
            ],
            'id'
        );

        if ($active == false){
            $new_id = $DB->insert_record(
                'block_aiassistant_session', 
                [
                'user_id' => $USER->id,
                'context_id' => $context->id,
                'status' => 1
                ]
            );
            return ['sessionid' => $new_id, 'isNew' => true];
        } else {
            if ($params['isNew']){
                $DB->set_field('block_aiassistant_session', 'status', '0', ['id' => $active->id]);
                $new_id = $DB->insert_record(
                    'block_aiassistant_session', 
                    [
                    'user_id' => $USER->id,
                    'context_id' => $context->id,
                    'status' => 1
                    ]
                );
                return ['sessionid' => $new_id, 'isNew' => true];
            }
            else {
                return ['sessionid' => $active->id, 'isNew' => false];
            }
        }
    }

    public static function execute_returns() {
        return new external_single_structure([
            'sessionid' => new external_value(PARAM_INT, 'Session ID'),
            'isNew' => new external_value(PARAM_BOOL, 'New session started or continued previous one'),
        ]);
    }
}