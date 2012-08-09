<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * The main aicc configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package   mod-skillsoft
 * @author 	  Martin Holden
 * @copyright 2009-2011 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once(dirname(__FILE__).'/locallib.php');
require_js($CFG->wwwroot . '/mod/skillsoft/skillsoft.js');
require_js($CFG->wwwroot . '/mod/skillsoft/md5.js');

class mod_skillsoft_mod_form extends moodleform_mod {

	function definition() {
		global $form, $CFG;

		$mform =& $this->_form;

		//-------------------------------------------------------------------------------
		// Adding the "general" fieldset, where all the common settings are showed

		$mform->addElement('header', 'general', get_string('general', 'form'));



        if (isset($form->add)) {
			// Asset ID
			$mform->addElement('text', 'assetid', get_string('skillsoft_assetid','skillsoft'));
			$mform->setType('assetid', PARAM_TEXT);
    		$mform->addRule('assetid', null, 'required', null, 'client');
			$mform->addRule('assetid', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
			$mform->setHelpButton('assetid',array('assetid', get_string('skillsoft_assetid', 'skillsoft'), 'skillsoft'));

        } else {
        	$mform->addElement('hidden', 'assetid', NULL, array('id'=>'id_assetid'));
        }


		//Dont allow change of assetid if we have saved this
		//$mform->disabledIf('assetid', 'timemodified','neq','');


		//Button to get data from OLSA
		//pass assetid to page
		$assetid="'+document.getElementById('id_assetid').value+'";
		$url = '/mod/skillsoft/preloader.php?id='.$assetid;
        $options = 'menubar=0,location=0,scrollbars,resizable,width=600,height=200';


         if (isset($form->add)) {
			$buttonattributes = array(
				'title'=>get_string('skillsoft_retrievemetadata', 'skillsoft'),
				'onclick'=>"return openpopup('$url', '', '$options', 0);",
			);
			$mform->addElement('button', 'getolsa', get_string('skillsoft_retrievemetadata', 'skillsoft'), $buttonattributes);
         } else {
			$buttonattributes = array(
				'title'=>get_string('skillsoft_updatemetadata', 'skillsoft'),
				'onclick'=>"return openpopup('$url', '', '$options', 0);",
			);
			$mform->addElement('button', 'getolsa', get_string('skillsoft_updatemetadata', 'skillsoft'), $buttonattributes);
         }
		$mform->setHelpButton('getolsa',array('retrievemetadata', get_string('skillsoft_retrievemetadata', 'skillsoft'), 'skillsoft'));

		// Name
		$mform->addElement('text', 'name', get_string('skillsoft_name','skillsoft'), array('size' => '75'));
		if (!empty($CFG->formatstringstriptags)) {
			$mform->setType('name', PARAM_TEXT);
		} else {
			$mform->setType('name', PARAM_CLEAN);
		}
		$mform->addRule('name', null, 'required', null, 'client');
		$mform->setHelpButton('name',array('title', get_string('skillsoft_name', 'skillsoft'), 'skillsoft'));

		// Summary
		$mform->addElement('htmleditor', 'summary', get_string('skillsoft_summary','skillsoft'));
		$mform->setType('summary', PARAM_RAW);
		$mform->addRule('summary', get_string('required'), 'required', null, 'client');
		$mform->setHelpButton('summary',array('overview', get_string('skillsoft_summary', 'skillsoft'), 'skillsoft'));

		// Audience
		$mform->addElement('htmleditor', 'audience', get_string('skillsoft_audience','skillsoft'));
		$mform->setType('audience', PARAM_RAW);
		$mform->setHelpButton('audience',array('audience', get_string('skillsoft_audience', 'skillsoft'), 'skillsoft'));

		// Pre-Requisites
		$mform->addElement('htmleditor', 'prereq', get_string('skillsoft_prereq','skillsoft'));
		$mform->setType('prereq', PARAM_RAW);
		$mform->setHelpButton('prereq',array('prereq', get_string('skillsoft_prereq', 'skillsoft'), 'skillsoft'));

		// Duration
		$mform->addElement('text', 'duration', get_string('skillsoft_duration','skillsoft'));
		$mform->setType('duration', PARAM_INT);
		$mform->setHelpButton('duration',array('duration', get_string('skillsoft_duration', 'skillsoft'), 'skillsoft'));

		// Asset Type
		$mform->addElement('hidden', 'assettype', null);

		// Launch URL


	    if (isset($form->add)) {
			$mform->addElement('text', 'launch', get_string('skillsoft_launch','skillsoft'), array('size' => '75'));
			$mform->setType('launch', PARAM_TEXT);
			$mform->addRule('launch', null, 'required', null, 'client');
			$mform->addRule('launch', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
			$mform->setHelpButton('launch',array('launch', get_string('skillsoft_launch', 'skillsoft'), 'skillsoft'));
	    } else {
        	$mform->addElement('hidden', 'launch', NULL, array('id'=>'id_launch'));
        }



		//Mastery
		//Set a NULL as first
		$mastery[''] = "No Mastery Score";
		for ($i=1; $i<=100; $i++) {
			$mastery[$i] = "$i";
		}
		$mform->addElement('select', 'mastery', get_string('skillsoft_mastery','skillsoft'), $mastery);
		$mform->setDefault('mastery', '');
		$mform->setHelpButton('mastery',array('mastery', get_string('skillsoft_mastery', 'skillsoft'), 'skillsoft'));


		//Time modified
		$mform->addElement('hidden', 'timemodified');
		$mform->addElement('hidden', 'timecreated');
		$mform->addElement('hidden', 'completable');
		
		
		//-------------------------------------------------------------------------------
		//-------------------------------------------------------------------------------
		$features = new stdClass;
		$features->groups = false;
		$features->groupings = true;
		$features->groupmembersonly = true;
		$this->standard_coursemodule_elements($features);

		//-------------------------------------------------------------------------------
		// add standard buttons, common to all modules
		$this->add_action_buttons();
	}


}
?>