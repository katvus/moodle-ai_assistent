<?php
defined('MOODLE_INTERNAL') || die();

function use_rag($instance_id, $question) {
    error_log("in use_rag");
    $url = 'http://localhost:8001/use';
    
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

function add_document_rag($instance_id, $file_id) {
    error_log("in add_document_rag");
    $url = 'http://localhost:8001/add_document';

    $fs = get_file_storage();
    $file = $fs->get_file_by_id($file_id);
    if (!$file) {
        error_log("File not found: $file_id");
        return false;
    }
    $file_dir = '/tmp/rag_' . $instance_id . '/' . $file->get_filename();
    $file->copy_content_to($file_dir);  
    $data = [
        'instance_id' => $instance_id, 
        'file_dir' => $file_dir,
        'file_info' => [
            'file_id' => $file_id,
            'file_name' => $file->get_filename(),
            'file_hash' => $file->get_contenthash()
        ]
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

function delete_document_rag($instance_id, $file_id) {
    error_log("in delete_document_rag");
    $url = 'http://localhost:8001/delete_document';
    
    $data = ['instance_id' => $instance_id, 'file_id' => $file_id];
    
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