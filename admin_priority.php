<?php
require_once('../../config.php');
require_once('classes/admin_priority_form.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url(new moodle_url('/blocks/aiassistant/admin_priority.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Manage API request');


echo $OUTPUT->header();

$form = new admin_priority_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/index.php'));
} else if ($data = $form->get_data()) {
    global $DB;

    foreach ($data as $key => $value) {
        if (strpos($key, 'priority_') === 0) {
            $requestid = str_replace('priority_', '', $key);
            
            $DB->update_record('block_aiassistant_messages', [
                'id' => $requestid,
                'priority' => $value
            ]);
        }
    }
}

$form->display();

echo $OUTPUT->footer();