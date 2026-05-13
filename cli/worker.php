<?php
DEFINE('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../classes/aiassistant.php');
require_once(__DIR__ . '/../classes/gigachat_assistant.php');
require_once($CFG->dirroot . '/blocks/aiassistant/classes/gptcache.php');
use block_aiassistant\gigachat_assistant;
use block_aiassistant\yandex_assistant;

const MAX_CONCURRENT_REQUEST = 10;
const POLLING_PERIOD = 3;
$work_request = 0;

while (true) {
    global $DB;
    while ($work_request < MAX_CONCURRENT_REQUEST) {
        $sql = "SELECT id, session_id, question
        FROM {block_aiassistant_messages} 
        WHERE status = ?
        ORDER BY priority DESC, question_time ASC";
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

function create_system_prompt($context) {
    global $DB;
    $blockinstanceid = $context->instanceid;
    $instance = $DB->get_record('block_instances', array('id' => $blockinstanceid));
    $config_data = unserialize(base64_decode($instance->configdata));

    $teacher_script = $config_data->script ?? '';

    if (isset($config_data->task)) {
        $tasks = $config_data->task;
        if (!empty($tasks)) {
            $teacher_script = $teacher_script . "\n" . get_string('taskscript', 'block_aiassistant') . "\n";
            foreach ($tasks as $index => $task) {
                if (!empty(trim($task))) {
                    $teacher_script = $teacher_script . ($index + 1) . ". " . $task . "\n";
                }
            }
        }
    }

    $course_info = '';
    if (get_config('block_aiassistant', 'coursecontext') === '1'){
        $course_context = $context->get_course_context();
        $course = $DB->get_record('course', ['id' => $course_context->instanceid]);
        $clean_text = strip_tags(format_text(
            $course->summary, 
            $course->summaryformat, 
            ['context' => $course_context, 'noclean' => false]
        ));
        $course_info = "You assistant in course: " . $course->fullname;
        if (!empty(trim($clean_text))){
            $course_info = $course_info . " Description of course: " .   $clean_text;
        }
    }

    if (!empty(trim($teacher_script))){
        $course_info = $course_info . $teacher_script;
    }

    return $course_info;
}

function add_message(&$array, $role, $text, $assistant) {
    if ($assistant === 'yandex') {
        array_push($array, ['role' => $role, 'text' => $text]);
    }
    else {
        array_push($array, ['role' => $role, 'content' => $text]);
    }
}

function execute($request_info) {
    global $work_request, $DB;
    $work_request++;
    $message = [];
    $ai_provider = $DB->get_field('config_plugins', 'value', [
        'plugin' => 'block_aiassistant', 
        'name' => 'selectai'
    ]);

    $record = $DB->get_record("block_aiassistant_session", ['id' => $request_info->session_id]);
    $context = \context::instance_by_id($record->context_id);
    $blockinstanceid = $context->instanceid;
    $system_prompt = create_system_prompt($context);

    if (!empty(trim($system_prompt))){
        add_message($message, 'system', $system_prompt, $ai_provider);
    }

    if ($ai_provider === 'yandex') {
        $apikey = get_config('block_aiassistant', 'apikey');
        $catalog_id = get_config('block_aiassistant', 'catalogid');
        $ai = new yandex_assistant($apikey, $catalog_id);
    }
    else {
        $authorizationkey = get_config('block_aiassistant', 'authorizationkey');
        $ai = new gigachat_assistant($authorizationkey);
    }

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
            add_message($message, 'user', $record->question, $ai_provider);
            add_message($message, 'assistant', $record->answer, $ai_provider);
        }
        $current_request = use_rag($blockinstanceid, $request_info->question);
        add_message($message, 'user', $current_request, $ai_provider);

        if (get_config('block_aiassistant', 'cacheavailable') === '1') {
            $response = check_gptcache($blockinstanceid, $request_info->question);
            if ($response["cached"]) {
                $result = $response["answer"];
            }
            else {
                $result = $ai->make_request($message);
                $store = store_in_gptcache($blockinstanceid, $request_info->question, $result);
                if (!$store){
                    error_log("error in store");
                }
            }
        }
        else {
            $result = $ai->make_request($message);
        }
        $time = new \DateTime();
        $answertime =  $time->getTimestamp();

        $DB->set_field("block_aiassistant_messages", 'answer', $result, ['id' => $request_info->id]);
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