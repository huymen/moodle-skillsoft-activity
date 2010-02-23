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
 * @copyright 2009 Martin Holden
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

function skillsoft_backup_mods($bf,$preferences) {
	global $CFG;
	$status = true;

	//Iterate over skillsoft table
	$skillsofts = get_records ('skillsoft','course',$preferences->backup_course,'id');
	if ($skillsofts) {
		foreach ($skillsofts as $skillsoft) {
			if (backup_mod_selected($preferences,'skillsoft',$skillsoft->id)) {
				$status = skillsoft_backup_one_mod($bf,$preferences,$skillsoft);
			}
		}
	}
	return $status;
}

function skillsoft_backup_one_mod($bf,$preferences,$skillsoft) {
	$status = true;

	if (is_numeric($skillsoft)) {
		$skillsoft = get_record('skillsoft','id',$skillsoft);
	}

	//Start mod
	fwrite ($bf,start_tag('MOD',3,true));
	//Print skillsoft data
	fwrite ($bf,full_tag('ID',4,false,$skillsoft->id));
	fwrite ($bf,full_tag('MODTYPE',4,false,'skillsoft'));
	fwrite ($bf,full_tag('ASSETID',4,false,$skillsoft->assetid));
	fwrite ($bf,full_tag('NAME',4,false,$skillsoft->name));
	fwrite ($bf,full_tag('SUMMARY',4,false,$skillsoft->summary));
	fwrite ($bf,full_tag('AUDIENCE',4,false,$skillsoft->audience));
	fwrite ($bf,full_tag('PREREQ',4,false,$skillsoft->prereq));
	fwrite ($bf,full_tag('LAUNCH',4,false,$skillsoft->launch));
	fwrite ($bf,full_tag('MASTERY',4,false,$skillsoft->mastery));
	fwrite ($bf,full_tag('ASSETTYPE',4,false,$skillsoft->assettype));
	fwrite ($bf,full_tag('DURATION',4,false,$skillsoft->duration));
	fwrite ($bf,full_tag('COMPLETABLE',4,false,$skillsoft->completable));
	fwrite ($bf,full_tag('TIMEMODIFIED',4,false,$skillsoft->timemodified));

	//if we've selected to backup users info, then execute backup_skillsoft_au_track
	if ($status) {
		if (backup_userdata_selected($preferences,'skillsoft',$skillsoft->id)) {
			$status = backup_skillsoft_au_track($bf,$preferences,$skillsoft->id);
		}
	}
	//End mod
	$status =fwrite ($bf,end_tag('MOD',3,true));
	return $status;
}

//Backup skillsoft_au_track contents (executed from skillsoft_backup_mods)
function backup_skillsoft_au_track ($bf,$preferences,$skillsoft) {
	global $CFG;
	$status = true;

	$skillsoft_au_track = get_records('skillsoft_au_track','skillsoftid',$skillsoft,'id');
	//If there is track
	if ($skillsoft_au_track) {
		//Write start tag
		$status =fwrite ($bf,start_tag('AU_TRACKS',4,true));
		//Iterate over each au
		foreach ($skillsoft_au_track as $au_track) {
			//Start track
			$status =fwrite ($bf,start_tag('AU_TRACK',5,true));
			//Print track contents
			fwrite ($bf,full_tag('ID',6,false,$au_track->id));
			fwrite ($bf,full_tag('USERID',6,false,$au_track->userid));
			fwrite ($bf,full_tag('ELEMENT',6,false,$au_track->element));
			fwrite ($bf,full_tag('VALUE',6,false,$au_track->value));
			fwrite ($bf,full_tag('ATTEMPT',6,false,$au_track->attempt));
			fwrite ($bf,full_tag('TIMEMODIFIED',6,false,$au_track->timemodified));
			//End track
			$status =fwrite ($bf,end_tag('AU_TRACK',5,true));
		}
		//Write end tag
		$status =fwrite ($bf,end_tag('AU_TRACKS',4,true));
	}
	return $status;
}

////Return an array of info (name,value)
function skillsoft_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
	if (!empty($instances) && is_array($instances) && count($instances)) {
		$info = array();
		foreach ($instances as $id => $instance) {
			$info += skillsoft_check_backup_mods_instances($instance,$backup_unique_code);
		}
		return $info;
	}
	//First the course data
	$info[0][0] = get_string('modulenameplural','skillsoft');
	if ($ids = skillsoft_ids ($course)) {
		$info[0][1] = count($ids);
	} else {
		$info[0][1] = 0;
	}

	//Now, if requested, the user_data
	if ($user_data) {
		$info[1][0] = get_string('skillsoft_trackedelement','skillsoft');
		if ($ids = skillsoft_au_track_ids_by_course ($course)) {
			$info[1][1] = count($ids);
		} else {
			$info[1][1] = 0;
		}
	}
	return $info;
}

function skillsoft_check_backup_mods_instances($instance,$backup_unique_code) {
	$info[$instance->id.'0'][0] = $instance->name;
	$info[$instance->id.'0'][1] = '';
	if (!empty($instance->userdata)) {
		$info[$instance->id.'1'][0] = get_string('skillsoft_trackedelement','skillsoft');
		if ($ids = skillsoft_au_track_ids_by_instance ($instance->id)) {
			$info[$instance->id.'1'][1] = count($ids);
		} else {
			$info[$instance->id.'1'][1] = 0;
		}
	}

	return $info;

}

// INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

//Returns an array of skillsoft id
function skilsoft_ids ($course) {

	global $CFG;

	return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}skillsoft a
                                 WHERE a.course = '$course'");
}

//Returns an array of skilloft_au_track id
function skillsoft_au_track_ids_by_course ($course) {

	global $CFG;

	return get_records_sql ("SELECT s.id , s.skillsoftid
                                 FROM {$CFG->prefix}skillsoft_au_track s,
                                 {$CFG->prefix}skillsoft a
                                 WHERE a.course = '$course' AND
                                       s.skillsoftid = a.id");
}

//Returns an array of skillsoft_au_track id
function skillsoft_au_track_ids_by_instance ($instanceid) {

	global $CFG;

	return get_records_sql ("SELECT s.id , s.skillsoftid
                                 FROM {$CFG->prefix}skillsoft_au_track s
                                 WHERE s.skillsoftid = $instanceid");
}
?>
