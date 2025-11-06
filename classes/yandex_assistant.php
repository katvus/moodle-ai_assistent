<?php
namespace block_aiassistant;
defined('MOODLE_INTERNAL') || die();

use moodle_exception;

class yandex_assistant implements aiassistant{
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
        $curl = new \curl();
        $curl->setHeader([
            'Authorization: Api-Key ' . $this->apikey,
            'Content-Type: application/json',
            ]
        );
        $request_data = $this->data;
        $request_data['messages'] = $messages;
        $response = $curl->post(
            'https://llm.api.cloud.yandex.net/foundationModels/v1/completion',
            json_encode($request_data),
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
            $answer = json_decode($response, true)['result']['alternatives'][0]['message']['text'];
            return $answer;
        }
    }
}