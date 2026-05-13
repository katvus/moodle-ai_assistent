<?php
class block_aiassistant_edit_form extends block_edit_form {
    
    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('addscript', 'block_aiassistant'));
        $mform->addElement('textarea', 'config_script', get_string('script', 'block_aiassistant', ['rows' => 10, 'cols' => 80]));
        $mform->setDefault('config_script', get_string('scriptdefault', 'block_aiassistant'));

        $mform->addElement('header', 'configheader', get_string('addmaterial', 'block_aiassistant'));
        
        $mform->addElement('textarea', 'config_teachermaterial', get_string('teachermaterial', 'block_aiassistant'), ['rows' => 10, 'cols' => 80]);
        $mform->setType('config_teachermaterial', PARAM_TEXT);
        
        $filemanageroptions = array(
            'accepted_types' => array('.pdf', '.doc', '.docx', '.txt'),
            'maxbytes' => 1024 * 1024 * 2,
            'maxfiles' => 5,
            'subdirs' => 0, 
            'areamaxbytes' => 1024 * 1024 * 10 
        );
        
        $mform->addElement('filemanager', 'config_files', get_string('files', 'block_aiassistant'), 
            null, $filemanageroptions);
    

        $mform->addElement('header', 'configheader', get_string('addtask', 'block_aiassistant'));   
        $mform->addElement('static', 'addtask_desc', '', get_string('addtask_desc', 'block_aiassistant'));         
        
        $repeatarray = [
            $mform->createElement('text', 'config_task', get_string('task', 'block_aiassistant')),
        ];

        $this->repeat_elements(
            $repeatarray,         
            1,           
            array(), 
            'repeat_task',            
            'add_task',       
            1,  
            '+', 
            true,       
            'delete_task'      
        ); 
    }

    public function set_data($defaults) {
        $draftitemid = file_get_submitted_draft_itemid('config_files');
        file_prepare_draft_area(
            $draftitemid,
            $this->block->context->id,
            'block_aiassistant',
            'config_files',
            0
        );
        $defaults->config_files = $draftitemid;
        parent::set_data($defaults);
    }
}