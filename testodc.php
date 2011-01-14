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
 * Retrieve the Asset metadata from the SkillSoft OLSA server
 * and update the create/edit form using Javascript.
 *
 * @package   mod-skillsoft
 * @author    Martin Holden
 * @copyright 2009 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/olsalib.php');



$initresponse = OC_InitializeTrackingData();
if ($initresponse->success) {
	//Initialise was successful
	$moreFlag = true;
	while ($moreFlag) {
		$tdrresponse = OC_GetTrackingData();
		echo 'Retrieving TDRs for Handle='.$tdrresponse->result->handle.'<br/>';
		foreach ( $tdrresponse->result->tdrs->tdr as $tdr) {
			echo '--Inserting TDR ID='.$tdr->id.'<br/>';
			$id = skillsoft_insert_tdr($tdr);
		}
    	$moreFlag = $tdrresponse->result->moreFlag;
    	$ackresponse = OC_AcknowledgeTrackingData($tdrresponse->result->handle);
	}
} else {
	error($initresponse->errormessage);
}
skillsoft_process_received_tdrs();


?>


