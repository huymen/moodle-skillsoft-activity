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
 * Library of interface functions and constants for module skillsoft
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the skillsoft specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package   mod-skillsoft
 * @author    Martin Holden
 * @copyright 2009 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $skillsoft An object from the form in mod_form.php
 * @return int The id of the newly inserted skillsoft record
 */
function skillsoft_add_instance($skillsoft) {
	$skillsoft->timecreated = time();
	$skillsoft->timemodified = time();
	
	if (stripos(strtolower($skillsoft->launch),'hacp=0')) {
		$skillsoft->completable = false;
	} else {
	if (strtolower($skillsoft->assetid) == 'sso') {
		$skillsoft->completable = false;
	} else {
		$skillsoft->completable = true;
	}
	}

	
	if ($result = insert_record('skillsoft', $skillsoft)) {
		$skillsoft->id = $result;
		//$newskillsoft = get_record('skillsoft', 'id' , $result);
		skillsoft_grade_item_update(stripslashes_recursive($skillsoft),NULL);
	}

	return $result;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $skillsoft An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function skillsoft_update_instance($skillsoft) {
	$skillsoft->timemodified = time();
	$skillsoft->id = $skillsoft->instance;

	if (stripos(strtolower($skillsoft->launch),'hacp=0')) {
		$skillsoft->completable = false;
	} else {
		$skillsoft->completable = true;
	}

	if ($result = update_record('skillsoft', $skillsoft)) {
		skillsoft_grade_item_update(stripslashes_recursive($skillsoft),NULL);
	}

	return $result;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function skillsoft_delete_instance($id) {
	$result = true;

	//Does the record exist
	if (! $skillsoft = get_record('skillsoft', 'id' , $id)) {
		$result = false;
	} else {
		//Delete the grade items
		if (skillsoft_grade_item_delete(stripslashes_recursive($skillsoft)) != 0) {
			$result = false;
		}
	}

	// Delete any dependent records, AKA the usage data
	if (! delete_records('skillsoft_au_track', 'skillsoftid', $skillsoft->id)) {
		$result = false;
	}

	// Delete the record
	if (! delete_records('skillsoft', 'id' , $skillsoft->id)) {
		$result = false;
	}

	return $result;
}

/**
 * Return grade for given user or all users.
 *
 * @param int $skillsoftid id of skillsoft
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function skillsoft_get_user_grades($skillsoft, $userid=0) {
	global $CFG;
	require_once('locallib.php');

	$grades = array();

	if ($skillsoft->completable == true) {
		if (empty($userid)) {
			if ($auusers = get_records_select('skillsoft_au_track', "skillsoftid='$skillsoft->id' GROUP BY userid", "", "userid,null")) {
				foreach ($auusers as $auuser) {
					$grades[$auuser->userid] = new object();
					$grades[$auuser->userid]->id         = $auuser->userid;
					$grades[$auuser->userid]->userid     = $auuser->userid;
					$grades[$auuser->userid]->rawgrade = skillsoft_grade_user($skillsoft, $auuser->userid);
				}
			} else {
				return false;
			}

		} else {
			if (!get_records_select('skillsoft_au_track', "skillsoftid='$skillsoft->id' AND userid='$userid' GROUP BY userid", "", "userid,null")) {
				return false; //no attempt yet
			}
			$grades[$userid] = new object();
			$grades[$userid]->id         = $userid;
			$grades[$userid]->userid     = $userid;
			$grades[$userid]->rawgrade = skillsoft_grade_user($skillsoft, $userid);
		}
	}
	return $grades;
}


/**
 * Update grades in central gradebook
 *
 * @param object $skillsoft null means all skillsoftbases
 * @param int $userid specific user only, 0 mean all
 */
function skillsoft_update_grades($skillsoft=null, $userid=0, $nullifnone=true) {
	global $CFG;
	if (!function_exists('grade_update')) { //workaround for buggy PHP versions
		require_once($CFG->libdir.'/gradelib.php');
	}

	if ($skillsoft != null) {
		if ($skillsoft->completable == true) {
			if ($grades = skillsoft_get_user_grades($skillsoft, $userid)) {
				skillsoft_grade_item_update($skillsoft, $grades);
			} else if ($userid and $nullifnone) {
				$grade = new object();
				$grade->userid   = $userid;
				$grade->rawgrade = NULL;
				skillsoft_grade_item_update($skillsoft, $grade);
			} else {
				skillsoft_grade_item_update($skillsoft);
			}
		}
	} else {
		$sql = "SELECT s.*, cm.idnumber as cmidnumber
                  FROM {$CFG->prefix}skillsoft s, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
                 WHERE m.name='skillsoft' AND m.id=cm.module AND cm.instance=s.id";
		if ($rs = get_recordset_sql($sql)) {
			while ($skillsoft = rs_fetch_next_record($rs)) {
				skillsoft_update_grades($skillsoft, 0, false);
			}
			rs_close($rs);
		}
	}
}

/**
 * Update/create grade item for given skillsoft
 *
 * @param object $skillsoft object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return object grade_item
 */
function skillsoft_grade_item_update($skillsoft, $grades=NULL) {
	global $CFG;

	if ($skillsoft->completable == true) {
		if (!function_exists('grade_update')) { //workaround for buggy PHP versions
			require_once($CFG->libdir.'/gradelib.php');
		}

		$params = array('itemname'=>$skillsoft->name);
		if (isset($skillsoft->cmidnumber)) {
			$params['idnumber'] = $skillsoft->cmidnumber;
		}

		$params['gradetype'] = GRADE_TYPE_VALUE;
		$params['grademax']  = 100;
		$params['grademin']  = 0;

		if ($grades  === 'reset') {
			$params['reset'] = true;
			$grades = NULL;
		}

		return grade_update('mod/skillsoft', $skillsoft->course, 'mod', 'skillsoft', $skillsoft->id, 0, $grades, $params);
	} else {
		return true;
	}
}


/**
 * Delete grade item for given skillsoft
 *
 * @param object $skillsoft object
 * @return object grade_item
 */
function skillsoft_grade_item_delete($skillsoft) {
	global $CFG;
	if ($skillsoft->completable == true) {

		if (!function_exists('grade_update')) { //workaround for buggy PHP versions
			require_once($CFG->libdir.'/gradelib.php');
		}
		$params = array('deleted'=>1);
		return grade_update('mod/skillsoft', $skillsoft->course, 'mod', 'skillsoft', $skillsoft->id, 0, NULL, $params);
	} else {
		return 0;
	}
}


/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 */
function skillsoft_user_outline($course, $user, $mod, $skillsoft) {
	require_once('locallib.php');

	$attempt=1;
	$return = NULL;

	if ($userdata = skillsoft_get_tracks($skillsoft->id, $user->id, $attempt)) {
		$a = new object();

		if ($skillsoft->completable == true) {
			$a->duration = isset($userdata->{'[CORE]time'}) ? $userdata->{'[CORE]time'} : '-';
			$a->bestscore = isset($userdata->{'[SUMMARY]bestscore'}) ? $userdata->{'[SUMMARY]bestscore'} : '-';
		} else {
			$notapplicable = get_string('skillsoft_na','skillsoft').helpbutton('noncompletable', get_string('skillsoft_noncompletable','skillsoft'),'skillsoft', true, false,NULL,true);
			$a->duration = $notapplicable;
			$a->bestscore =$notapplicable;
		}
		$a->accesscount = isset($userdata->{'[SUMMARY]accesscount'}) ? $userdata->{'[SUMMARY]accesscount'} : '-';
		$return = new object();
		$return->info = get_string("skillsoft_summarymessage", "skillsoft", $a);
		$return->time = $userdata->{'[SUMMARY]lastaccess'};
	}

	return $return;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 * This needs to output the information
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function skillsoft_user_complete($course, $user, $mod, $skillsoft) {
	require_once('locallib.php');



	$table = new stdClass();
	$table->head = array(
	get_string('skillsoft_firstaccess','skillsoft'),
	get_string('skillsoft_lastaccess','skillsoft'),
	get_string('skillsoft_completed','skillsoft'),
	get_string('skillsoft_lessonstatus','skillsoft'),
	get_string('skillsoft_totaltime','skillsoft'),
	get_string('skillsoft_firstscore','skillsoft'),
	get_string('skillsoft_currentscore','skillsoft'),
	get_string('skillsoft_bestscore','skillsoft'),
	get_string('skillsoft_accesscount','skillsoft'),
	);
	$table->align = array('left', 'left', 'left', 'center','center','right','right','right','right');
	$table->wrap = array('', '','','nowrap','nowrap','nowrap','nowrap','nowrap','nowrap');
	$table->width = '80%';
	$table->size = array('*', '*', '*', '*', '*', '*', '*', '*', '*');
	$row = array();
	$score = '&nbsp;';

	if ($trackdata = skillsoft_get_tracks($skillsoft->id,$user->id)) {
		$row[] = isset($trackdata->{'[SUMMARY]firstaccess'}) ? userdate($trackdata->{'[SUMMARY]firstaccess'}):'';
		$row[] = isset($trackdata->{'[SUMMARY]lastaccess'}) ? userdate($trackdata->{'[SUMMARY]lastaccess'}):'';
		if ($skillsoft->completable == true) {
			$row[] = isset($trackdata->{'[SUMMARY]completed'}) ? userdate($trackdata->{'[SUMMARY]completed'}):'';
			$row[] = isset($trackdata->{'[CORE]lesson_status'}) ? $trackdata->{'[CORE]lesson_status'}:'';
			$row[] = isset($trackdata->{'[CORE]time'}) ? $trackdata->{'[CORE]time'}:'';
			$row[] = isset($trackdata->{'[SUMMARY]firstscore'}) ? $trackdata->{'[SUMMARY]firstscore'}:'';
			$row[] = isset($trackdata->{'[SUMMARY]currentscore'}) ? $trackdata->{'[SUMMARY]currentscore'}:'';
			$row[] = isset($trackdata->{'[SUMMARY]bestscore'}) ? $trackdata->{'[SUMMARY]bestscore'}:'';
		} else {
			$notapplicable = get_string('skillsoft_na','skillsoft').helpbutton('noncompletable', get_string('skillsoft_noncompletable','skillsoft'),'skillsoft', true, false,NULL,true);
			$row[] = $notapplicable;
			$row[] = $notapplicable;
			$row[] = $notapplicable;
			$row[] = $notapplicable;
			$row[] = $notapplicable;
			$row[] = $notapplicable;
		}
		$row[] = isset($trackdata->{'[SUMMARY]accesscount'}) ? $trackdata->{'[SUMMARY]accesscount'} :'';
		$table->data[] = $row;
	}
	print_table($table);

	return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in skillsoft activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function skillsoft_print_recent_activity($course, $isteacher, $timestart) {
	global $CFG;
	$result = false;

	$records = get_records_sql("
        SELECT
            s.id AS id,
            s.name AS name,
            COUNT(*) AS count_launches
        FROM
        {$CFG->prefix}skillsoft s,
        {$CFG->prefix}skillsoft_au_track a
        WHERE
            s.course = $course->id
            AND s.id = a.skillsoftid
            AND a.element = '[SUMMARY]accesscount'
            AND a.timemodified > $timestart
        GROUP BY
            s.id, s.name
    ");
        // note that PostGreSQL requires h.name in the GROUP BY clause
        //
        if($records) {
        	$names = array();
        	foreach ($records as $id => $record){
        		if ($cm = get_coursemodule_from_instance('skillsoft', $record->id, $course->id)) {
        			$context = get_context_instance(CONTEXT_MODULE, $cm->id);

        			if (has_capability('mod/skillsoft:viewreport', $context)) {
        				$href = "$CFG->wwwroot/mod/skillsoft/report.php?id=$id";
        				$name = '&nbsp;<a href="'.$href.'">'.$record->name.'</a>';
        				if ($record->count_launches > 1) {
        					$name .= " ($record->count_launches)";
        				}
        				$names[] = $name;
        			}
        		}
        	}
        	if (count($names) > 0) {
        		print_headline(get_string('modulenameplural', 'skillsoft').':');
        		echo '<div class="head"><div class="name">'.implode('<br />', $names).'</div></div>';
        		$result = true;
        	}
        }
        return $result;  //  True if anything was printed, otherwise false
}

function skillsoft_get_recent_mod_activity(&$activities, &$index, $sincetime, $courseid, $cmid="", $userid="", $groupid="") {
	// Returns all skillsoft access since a given time.
	global $CFG;

	// If $cmid or $userid are specified, then this restricts the results
	$cm_select = empty($cmid) ? "" : " AND cm.id = '$cmid'";
	$user_select = empty($userid) ? "" : " AND u.id = '$userid'";

	$records = get_records_sql("
        SELECT
            a.*,
            s.name, s.course,
            cm.instance, cm.section,
            u.firstname, u.lastname, u.picture
        FROM
        {$CFG->prefix}skillsoft_au_track a,
        {$CFG->prefix}skillsoft s,
        {$CFG->prefix}course_modules cm,
        {$CFG->prefix}user u
        WHERE
            a.timemodified > '$sincetime'
            AND a.element = '[SUMMARY]accesscount'
            AND a.userid = u.id $user_select
            AND a.skillsoftid = s.id $cm_select
            AND cm.instance = s.id
            AND cm.course = '$courseid'
            AND s.course = cm.course
        ORDER BY
            a.timemodified ASC
    ");

        if (!empty($records)) {
        	foreach ($records as $record) {
        		if (empty($groupid) || groups_is_member($groupid, $record->userid)) {

        			unset($activity);

        			$activity->type = "skillsoft";
        			$activity->defaultindex = $index;
        			$activity->instance = $record->skillsoftid;

        			$activity->name = $record->name;
        			$activity->section = $record->section;

        			$activity->content->accesscount = $record->value;

        			$activity->user->userid = $record->userid;
        			$activity->user->fullname = fullname($record);
        			$activity->user->picture = $record->picture;

        			$activity->timestamp = $record->timemodified;

        			$activities[] = $activity;

        			$index++;
        		}
        	} // end foreach
        }
}

function skillsoft_print_recent_mod_activity($activity, $course, $detail=false) {
	/// Basically, this function prints the results of "skillsoft_get_recent_activity"

	global $CFG, $THEME, $USER;

	if (isset($THEME->cellcontent2)) {
		$bgcolor =  ' bgcolor="'.$THEME->cellcontent2.'"';
	} else {
		$bgcolor = '';
	}

	print '<table border="0" cellpadding="3" cellspacing="0">';

	print '<tr><td'.$bgcolor.' class="skillsoftpostpicture" width="35" valign="top">';
	print_user_picture($activity->user->userid, $course, $activity->user->picture);
	print '</td><td width="100%"><font size="2">';

	if ($detail) {
		// activity icon
		$src = "$CFG->modpixpath/$activity->type/icon.gif";
		print '<img src="'.$src.'" class="icon" alt="'.$activity->type.'" /> ';

		// link to activity
		$href = "$CFG->wwwroot/mod/skillsoft/view.php?id=$activity->instance";
		print '<a href="'.$href.'">'.$activity->name.'</a> - ';
	}
	if (has_capability('mod/skillsoft:viewreport',get_context_instance(CONTEXT_COURSE, $course))) {
		// score (with link to attempt details)
		$href = "$CFG->wwwroot/mod/skillsoft/report.php?id=".$activity->instance."&user=".$activity->user->userid;
		print '<a href="'.$href.'">'.get_string('skillsoft_viewreport','skillsoft').'</a> ';
		print '<br />';
	}

	// link to user
	$href = "$CFG->wwwroot/user/view.php?id=".$activity->user->userid."&course=".$course;
	print '<a href="'.$href.'">'.$activity->user->fullname.'</a> ';


	$timeago = format_time(time() - $activity->timestamp);
	// time and date
	print ' - ' . userdate($activity->timestamp) . ' ('.$timeago.')';
	print "</font></td></tr>";
	print "</table>";
}



/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function skillsoft_delete_sessions($time) {
	$result = true;

	// Delete any dependent records, AKA the usage data
	if (! delete_records_select('skillsoft_session_track', 'timecreated < '.$time)) {
		$result = false;
	}

	return $result;
}

function skillsoft_ondemandcommunications() {
	require_once('olsalib.php');

	mtrace(get_string('skillsoft_odcinit','skillsoft'));
	$initresponse = OC_InitializeTrackingData();
	if ($initresponse->success) {
		//Initialise was successful
		$moreFlag = true;
		while ($moreFlag) {
			$tdrresponse = OC_GetTrackingData();
			if ($tdrresponse->success) {
				mtrace(get_string('skillsoft_odcgetdatastart','skillsoft',$tdrresponse->result->handle));

				//Handle the use case where we only get ONE tdr
 				if ( is_array($tdrresponse->result->tdrs->tdr) && ! empty($tdrresponse->result->tdrs->tdr) )
    			{
					foreach ( $tdrresponse->result->tdrs->tdr as $tdr) {
						mtrace(get_string('skillsoft_odcgetdataprocess','skillsoft',$tdr->id));
						$id = skillsoft_insert_tdr($tdr);
					}
    			} else {
    				mtrace(get_string('skillsoft_odcgetdataprocess','skillsoft',$tdrresponse->result->tdrs->tdr->id));
					$id = skillsoft_insert_tdr($tdrresponse->result->tdrs->tdr);
    			}
		    	$moreFlag = $tdrresponse->result->moreFlag;

		    	$ackresponse = OC_AcknowledgeTrackingData($tdrresponse->result->handle);

		    	if ($tdrresponse->success) {
		    		mtrace(get_string('skillsoft_odcackdata','skillsoft',$tdrresponse->result->handle));
		    	} else {
		    		mtrace(get_string('skillsoft_odcackdataerror','skillsoft',$tdrresponse->errormessage));
		    	}
		    	mtrace(get_string('skillsoft_odcgetdataend','skillsoft',$tdrresponse->result->handle));
			} else {
				if ($tdrresponse->errormessage == get_string('skillsoft_odcnoresultsavailable','skillsoft')) {
					mtrace(get_string('skillsoft_odcnoresultsavailable','skillsoft'));
				} else {
					mtrace(get_string('skillsoft_odcgetdataerror','skillsoft',$tdrresponse->errormessage));
				}
				$moreFlag = false;
			}
		}
	} else {
		mtrace(get_string('skillsoft_odciniterror','skillsoft',$initresponse->errormessage));
	}
	skillsoft_process_received_tdrs(true);
}




/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function skillsoft_cron () {
	global $CFG;
	require_once('locallib.php');

	if (!isset($CFG->skillsoft_sessionpurge)) {
		set_config('skillsoft_sessionpurge', 8);
	}

	//Purge the values from skillsoft_session_track that are older than $CFG->skillsoft_sessionpurge hours.
	//Time now - hours*60*60
	$purgetime = time() - ($CFG->skillsoft_sessionpurge * 60 * 60);
	mtrace(get_string('skillsoft_purgemessage','skillsoft',userdate($purgetime)));
	skillsoft_delete_sessions($purgetime);

	if ($CFG->skillsoft_trackingmode == TRACK_TO_OLSA) {
		//We are in "Track to OLSA" so perform ODC cycle
		skillsoft_ondemandcommunications();
	}
	return true;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of skillsoft. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $skillsoftid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function skillsoft_get_participants($skillsoftid) {
	global $CFG;

	//Get students
	$students = get_records_sql("SELECT DISTINCT u.id, u.id
                                 FROM {$CFG->prefix}user u,
                                 {$CFG->prefix}lesson_au_tracks a
                                 WHERE a.skillsoftid = '$skillsoftid' and
                                       u.id = a.userid");

                                 //Return students array (it contains an array of unique users)
                                 return ($students);


                                 return false;
}


// For Participantion Reports
function skillsoft_get_view_actions() {
	return array('view activity', 'view report','view all');
}

// For Participantion Reports
function skillsoft_get_post_actions() {
	return array('add','update');
}


/**
 * This function returns if a scale is being used by one skillsoft
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $skillsoftid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 */
function skillsoft_scale_used($skillsoftid, $scaleid) {

	$return = false;

	//$rec = $DB->get_record("skillsoft", array("id" => "$skillsoftid", "scale" => "-$scaleid"));
	//
	//if (!empty($rec) && !empty($scaleid)) {
	//    $return = true;
	//}

	return $return;
}

/**
 * Checks if scale is being used by any instance of skillsoft.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any skillsoft
 */
function skillsoft_scale_used_anywhere($scaleid) {
	$return = false;
	/*
	 if ($scaleid and record_exists('skillsoft', 'grade', -$scaleid)) {
		return true;
		} else {
		return false;
		}
		*/
	return $return;
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function skillsoft_uninstall() {
	return true;
}
