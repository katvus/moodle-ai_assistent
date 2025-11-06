<?php
namespace block_aiassistant;
defined('MOODLE_INTERNAL') || die();

use moodle_exception;

class gigachat_assistant implements aiassistant{
    private $authorization_key;
    private $access_token = null;
    private $data;
    private int $history_limit = 10;

    function __construct($authorizationkey, $messages = [])
    {
        $this->authorization_key = $authorizationkey;
        $this->data = [
            'model' => 'GigaChat',
            'messages' => $messages,
            'stream' => false,
            'repetition_penalty' => 1
        ];
    }

    function get_history_limit(){
        return $this->history_limit;
    }

    private function get_access_token(){
        if ($this->access_token === null || (time() * 1000) >= $this->access_token['expires_at']){
            $curl = new \curl();
            $curl->setHeader([
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'RqUID: ' . generateRqUID(), 
                'Authorization: Basic ' . $this->authorization_key
            ]);
            $post_data = http_build_query(['scope' => 'GIGACHAT_API_PERS']);
            $response = $curl->post(
                'https://ngw.devices.sberbank.ru:9443/api/v2/oauth',
                $post_data
            );
            $info = $curl->get_info();
            $httpcode = $info['http_code'];
            if ($response === false) {
                $error = [
                    'error' => $curl->error,
                    'errno' => $curl->errno,
                    'info' => $curl->get_info()
                ];
                throw new moodle_exception('invalidresponse', 'block_aiassistant', '', 
                null, json_encode($error));
            } elseif ($httpcode >= 400) {
                throw new moodle_exception('httperror', 'block_aiassistant', '', 
                null, "Code: $httpcode, Response: $response");
            } else {
                $token_data = json_decode($response, true);
                $access_token = $token_data['access_token'];
                return $access_token;
            }
        }
        return $this->access_token['access_token'];
    }

    function make_request($messages){
        $curl = new \curl();
        $curl->setHeader([
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->get_access_token()
        ]);

        $request_data = $this->data;
        $request_data['messages'] = $messages;
        $response = $curl->post(
            'https://gigachat.devices.sberbank.ru/api/v1/chat/completions',
            json_encode($request_data)
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

            $answer = json_decode($response, true)['choices'][0]['message']['content'];
            return $answer;
        }
    }
}

function generateRqUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}