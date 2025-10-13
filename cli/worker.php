<?php
DEFINE('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
use block_aiassistant\aiassistant;

const MAX_CONCURRENT_REQUEST = 10;
const POLLING_PERIOD = 3;
$work_request = 0;

while (true) {
    global $DB;
    while ($work_request < MAX_CONCURRENT_REQUEST) {
        $sql = "SELECT id, session_id, question
        FROM {block_aiassistant_messages} 
        WHERE status = ?
        ORDER BY question_time ASC";
        $params = ['queue'];
        $need_execute = $DB->get_records_sql($sql, $params, 0, MAX_CONCURRENT_REQUEST - $work_request);
        if (empty($need_execute)) {
            sleep(POLLING_PERIOD);
        }
        else {
            foreach ($need_execute as $request_info) {
                $DB->set_field("block_aiassistant_messages", 'status', 'processing', ['id' => $request_info->id]);
                execute($request_info);
            }
        }
    }
}

function execute($request_info) {
    error_log("execute");
    global $work_request, $DB;
    $work_request++;
    $apikey = get_config('block_aiassistant', 'apikey');
    $catalog_id = get_config('block_aiassistant', 'catalogid');
    $ai = new aiassistant($apikey, $catalog_id);
    $message = [];

    try {
        $sql = "SELECT id, question, answer
        FROM {block_aiassistant_messages} 
        WHERE session_id = ? AND status = ? 
        ORDER BY question_time DESC";

        $history_from_db = $DB->get_records_sql(
            $sql, 
            [$request_info->session_id, 'completed'],
            0,
            $ai->get_history_limit()
        );
        $history = array_reverse($history_from_db);
        foreach ($history as $record){
            array_push($message, 
            ['role' => 'user', 'text' => $record->question], 
            ['role' => 'assistant', 'text' => $record->answer] 
            );
        }
        array_push($message, ['role' => 'user', 'text' => $request_info->question]);
        $result = $ai->make_request($message);
        $time = new \DateTime();
        $answertime =  $time->getTimestamp();

        $DB->set_field("block_aiassistant_messages", 'answer', $result['text'], ['id' => $request_info->id]);
        $DB->set_field("block_aiassistant_messages", 'answer_time', $answertime, ['id' => $request_info->id]);
        $DB->set_field("block_aiassistant_messages", 'status', 'completed', ['id' => $request_info->id]);

    } catch (\dml_exception $e) {
        error_log("DB error: " . $e->getMessage());

    } catch (\moodle_exception $e) {
        error_log("aiassistant error: " . $e->getMessage());
        $DB->set_field("block_aiassistant_messages", 'status', 'failed', ['id' => $request_info->id]);
    }
    $work_request--;
}