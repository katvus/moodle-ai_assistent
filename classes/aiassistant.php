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
namespace block_aiassistant;
defined('MOODLE_INTERNAL') || die();

use moodle_exception;
require_once($CFG->libdir . '/filelib.php');

class aiassistant{
    private $apikey;
    private $catalog_id;
    private $data;
    private int $history_limit = 10;

    function __construct($apikey, $catalog_id, $messages = [])
    {
        $this->apikey = $apikey;
        $this->catalog_id = $catalog_id;
        $this->data = [
            'modelUri' => 'gpt://' . $catalog_id . '/yandexgpt',
            'completionOptions'=> [
                'stream' => false,
                'temperature' => 0.6,
                'maxTokens' => '2000',
                'reasoningOptions'=> [
                    'mode' => 'DISABLED'
                ]
            ],
            'messages' => $messages,
        ];
    }

    function get_history_limit(){
        return $this->history_limit;
    }

    function make_request($messages){
        $this->data['messages'] = $messages;
        error_log("messages text " . $messages[0]['text']);
        error_log("ai assistant question:". print_r($this->data, true));
        $curl = new \curl();
        $curl->setHeader([
            'Authorization: Api-Key ' . $this->apikey,
            'Content-Type: application/json',
            ]
        );
        $response = $curl->post(
            'https://llm.api.cloud.yandex.net/foundationModels/v1/completion',
            json_encode($this->data),
        );
        $info = $curl->get_info();
        $httpcode = $info['http_code'];
        if ($response === false){
            $error = [
                'error' => $curl->error,
                'errno' => $curl->errno,
                'info' => $curl->get_info()
            ];
            throw new moodle_exception('invalidresponse', 'block_aiassistant', '', 
            null, json_encode($error));
        }
        elseif ($httpcode >= 400){
            throw new moodle_exception('httperror', 'block_aiassistant', '', 
            null, "Code: $httpcode, Response: $response");
        }
        else{
            $answer = json_decode($response, true)['result']['alternatives'][0]['message'];
            error_log("ai assistant answer:". print_r(json_decode($response, true), true));
            array_push($this->data['messages'], $answer);
            return $answer;
        }
    }

}