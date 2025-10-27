<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');

class admin_priority_form extends moodleform {
    
    protected function definition() {
        global $DB;
        
        $mform = $this->_form;
        
        $sql = "SELECT *
        FROM {block_aiassistant_messages} 
        WHERE status = ? 
        ORDER BY question_time DESC";

        $requests = $DB->get_records_sql($sql, ['queue']);
        
        foreach ($requests as $request) {
            $mform->addElement('header', 'requestheader_' . $request->id, 
                'Request ID: ' . $request->id . ' - ' . userdate($request->question_time),
                'Message: ' . $request->question);
            
            $mform->addElement('select', 'priority_' . $request->id, 
                'Priority', 
                array(0 => 'Low', 1 => 'High'));
            $mform->setDefault('priority_' . $request->id, $request->priority);
            
            $mform->addElement('hidden', 'requestid_' . $request->id, $request->id);
        }
        
        $this->add_action_buttons(true, 'Save change');
    }
}