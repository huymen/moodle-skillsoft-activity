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
 * This php script contains all the stuff to backup/restore skillsoft mods
 *
 *
 * @package   mod-skillsoft
 * @author 	  Martin Holden
 * @copyright 2009-20111 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 *This is the "graphical" structure of the skillsoft mod:
 *
 *                      skillsoft
 *                   (CL,pk->id)-------------------------------------
 *                        |                                         |
 *                        |                                         |
 *             skillsoft_au_track                                   |
 *        (UL,k->id, fk->skillsoftid)--------------------------------
 *
 * Meaning: pk->primary key field of the table
 *          fk->foreign key to link with parent
 *          nt->nested field (recursive data)
 *          CL->course level info
 *          UL->user level info
 *          files->table may have files)
 *
 */

//This function executes all the restore procedure about this mod
function skillsoft_restore_mods($mod,$restore) {

	global $CFG;

	$status = true;

	//Get record from backup_ids
	$data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

	if ($data) {
		//Now get completed xmlized object
		$info = $data->info;
		//traverse_xmlize($info);                                                                     //Debug
		//print_object ($GLOBALS['traverse_array']);                                                  //Debug
		//$GLOBALS['traverse_array']="";                                                              //Debug

		//Now, build the SKILLSOFT record structure
		$skillsoft->course = $restore->course_id;
		$skillsoft->assetid = backup_todb($info['MOD']['#']['ASSETID']['0']['#']);
		$skillsoft->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
		$skillsoft->summary = backup_todb($info['MOD']['#']['SUMMARY']['0']['#']);
		$skillsoft->audience = backup_todb($info['MOD']['#']['AUDIENCE']['0']['#']);
		$skillsoft->prereq = backup_todb($info['MOD']['#']['PREREQ']['0']['#']);
		$skillsoft->launch = backup_todb($info['MOD']['#']['LAUNCH']['0']['#']);

		$skillsoft->mastery = backup_todb($info['MOD']['#']['MASTERY']['0']['#']);
		if (!is_int($skillsoft->mastery)) {
			$skillsoft->mastery = '';
		}
		$skillsoft->assettype = backup_todb($info['MOD']['#']['ASSETTYPE']['0']['#']);
		$skillsoft->duration = backup_todb($info['MOD']['#']['DURATION']['0']['#']);
		$skillsoft->completable = backup_todb($info['MOD']['#']['COMPLETABLE']['0']['#']);
		$skillsoft->timemodified = time();

		//The structure is equal to the db, so insert the scorm
		$newid = insert_record ('skillsoft',$skillsoft);
		//Do some output
		if (!defined('RESTORE_SILENTLY')) {
			echo "<li>".get_string("modulename","skillsoft")." \"".format_string(stripslashes($skillsoft->name),true)."\"</li>";
		}
		backup_flush(300);

		if ($newid) {
			//We have the newid, update backup_ids
			backup_putid($restore->backup_unique_code,$mod->modtype,$mod->id, $newid);
			$skillsoft->id = $newid;

			//Now we need to restore the tracking data
			if ($status) {
				$status =skillsoft_au_tracks_restore_mods ($newid,$info,$restore);
			}
		} else {
			$status = false;
		}
	} else {
		$status = false;
	}
	return $status;
}

//This function restores the skillsoft_au_track
function skillsoft_au_tracks_restore_mods($skillsoft_id,$info,$restore) {

	global $CFG;

	$status = true;
	$autracks = NULL;

	//Get the au array
	if (!empty($info['MOD']['#']['AU_TRACKS']['0']['#']['AU_TRACK']))
	$autracks = $info['MOD']['#']['AU_TRACKS']['0']['#']['AU_TRACK'];

	//Iterate over
	for($i = 0; $i < sizeof($autracks); $i++) {
		$sub_info = $autracks[$i];
		unset($autrack);

		//Now, build the scorm_scoes_track record structure
		$autrack->skillsoftid = $skillsoft_id;
		$autrack->userid = backup_todb($sub_info['#']['USERID']['0']['#']);
		$autrack->element = backup_todb($sub_info['#']['ELEMENT']['0']['#']);
		$autrack->attempt = backup_todb($sub_info['#']['ATTEMPT']['0']['#']);
		$autrack->value = backup_todb($sub_info['#']['VALUE']['0']['#']);

		//We have to recode the userid field
		$user = backup_getid($restore->backup_unique_code,"user",$autrack->userid);
		if (!empty($user)) {
			$autrack->userid = $user->new_id;
		}

		$autrack->timemodified = time();
		//The structure is equal to the db, so insert the skillsoft_au_track
		$newid = insert_record ("skillsoft_au_track",$autrack);

		//Do some output
		if (($i+1) % 50 == 0) {
			if (!defined('RESTORE_SILENTLY')) {
				echo ".";
				if (($i+1) % 1000 == 0) {
					echo "<br />";
				}
			}
			backup_flush(300);
		}

	}

	return $status;
}

//This function returns a log record with all the necessay transformations
//done. It's used by restore_log_module() to restore modules log.
function skillsoft_restore_logs($restore,$log) {

	$status = true;

	return $status;
}
?>
