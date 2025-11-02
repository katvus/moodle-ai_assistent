<?php
class block_aiassistant_edit_form extends block_edit_form {
    
    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('addmaterial', 'block_aiassistant'));
        
        $mform->addElement('text', 'config_teachermaterial', get_string('teachermaterial', 'block_aiassistant'));
        $mform->setType('config_teachermaterial', PARAM_TEXT);
    }
}