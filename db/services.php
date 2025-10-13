<?php
$functions = [
    'block_aiassistant_request_assistant' => [
        'classname' => 'block_aiassistant\external\api',
        'methodname' => 'execute',
        'description' => 'Get request from AI assistant',
        'type' => 'write',
        'ajax' => true
    ],
    'block_aiassistant_get_session' => [
        'classname' => 'block_aiassistant\external\session',
        'methodname' => 'execute',
        'description' => 'Get session id for current user and instance',
        'type' => 'write',
        'ajax' => true
    ],
    'block_aiassistant_load_history' => [
        'classname' => 'block_aiassistant\external\history',
        'methodname' => 'execute',
        'description' => 'Get history of session',
        'type' => 'read',
        'ajax' => true
    ],
    'block_aiassistant_check_status' => [
        'classname' => 'block_aiassistant\external\check_status',
        'methodname' => 'execute',
        'description' => 'Checking if request has been completed',
        'type' => 'read',
        'ajax' => true
    ]
];