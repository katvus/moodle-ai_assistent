<?php
defined('MOODLE_INTERNAL') || die();

function check_gptcache($instance_id, $question) {
    error_log("in check_gptcache");
    $url = 'http://localhost:8000/check';
    
    $data = [
        'instance_id' => $instance_id,
        'question' => $question
    ];
    
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        throw new Exception("cURL error: " . $error);
    }
    
    if ($http_code == 200) {
        $result = json_decode($response, true);
        return $result;
    } else {
        throw new Exception("HTTP error: " . $http_code);
    }
}

function store_in_gptcache($instance_id, $question, $answer) {
    $url = 'http://localhost:8000/store';
    
    $data = [
        'instance_id' => $instance_id,
        'question' => $question,
        'answer' => $answer
    ];
    
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    return $http_code == 200;
}