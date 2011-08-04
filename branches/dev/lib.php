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
 * @copyright 2009-2011 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Given an object containing all the necessary data,
 * determine if the item is completable..
 *
 * @param object $skillsoft An object from the form in mod_form.php
 * @return bool true if the item is completable
 */
function skillsoft_iscompletable($skillsoft) {
	if (strcasecmp(substr($skillsoft->assetid, 0, 9),"_scorm12_")===0) {
		//SCORM content hosted on SkillPort will not have hacp=0 in the
		//URL so we look at course code and if _scorm12_* then mark as completable
		return true;
	} else if (stripos($skillsoft->launch,'hacp=0')) {
		return false;
	} else if (strtolower($skillsoft->assetid) == 'sso') {
		return false;
	} else {
		return true;
	}
}


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
	global $CFG;

	$skillsoft->timecreated = time();
	$skillsoft->timemodified = time();

	$skillsoft->completable = skillsoft_iscompletable($skillsoft);

	if ($result = insert_record('skillsoft', $skillsoft)) {
		$skillsoft->id = $result;
		//$newskillsoft = get_record('skillsoft', 'id' , $result);
		skillsoft_grade_item_update(stripslashes_recursive($skillsoft),NULL);
	}

	//We have added an instance so now we need to unset the processed flag
	//in the ODC/CustomReport data so that this new "instance" of a course
	//gets the data updated next time CRON runs
	if ($CFG->skillsoft_trackingmode == TRACK_TO_OLSA_CUSTOMREPORT) {

		$countofunprocessed = count_records('skillsoft_report_results','assetid',$skillsoft->assetid,'processed','1');

		//We are in "Track to OLSA (Custom Report)"
		//We get all skillsoft_report_results where assetid match and they have already been processed
		$limitfrom=0;
		$limitnum=1000;
		do {
			if ($unmatchedreportresults = get_records_select('skillsoft_report_results','assetid="'.$skillsoft->assetid.'" and processed=1','id ASC','*',$limitfrom,$limitnum)) {
				foreach ($unmatchedreportresults as $reportresults) {
					$reportresults->processed = 0;
					$id = update_record('skillsoft_report_results',$reportresults);
				}
			}
			$limitfrom += 1000;
		} while (($unmatchedreportresults != false) && ($limitfrom < $countofunprocessed));
	}

	if ($CFG->skillsoft_trackingmode == TRACK_TO_OLSA) {

		$countofunprocessed = count_records('skillsoft_tdr','assetid',$skillsoft->assetid,'processed','1');

		//We are in "Track to OLSA"
		//We get all skillsoft_tdr where assetid match and they have already been processed
		$limitfrom=0;
		$limitnum=1000;
		do {
			if ($unmatchedtdrs = get_records_select('skillsoft_tdr','assetid="'.$skillsoft->assetid.'" and processed=1','id ASC','*',$limitfrom,$limitnum)) {
				foreach ($unmatchedtdrs as $tdr) {
					$tdr->processed = 0;
					$id = update_record('skillsoft_tdr',$tdr);
				}
			}
			$limitfrom += 1000;
		} while (($unmatchedtdrs != false) && ($limitfrom < $countofunprocessed));
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

	$skillsoft->completable = skillsoft_iscompletable($skillsoft);

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
					$rawgradeinfo =  skillsoft_grade_user($skillsoft, $auuser->userid);
					
					$grades[$auuser->userid] = new object();
					$grades[$auuser->userid]->id         = $auuser->userid;
					$grades[$auuser->userid]->userid     = $auuser->userid;
					$grades[$userid]->rawgrade = isset($rawgradeinfo->score) ? $rawgradeinfo->score : NULL;
					$grades[$userid]->dategraded = isset($rawgradeinfo->time) ? $rawgradeinfo->time : NULL;
				}
			} else {
				return false;
			}

		} else {
			if (!get_records_select('skillsoft_au_track', "skillsoftid='$skillsoft->id' AND userid='$userid' GROUP BY userid", "", "userid,null")) {
				return false; //no attempt yet
			}
			$rawgradeinfo =  skillsoft_grade_user($skillsoft, $userid);
			
			$grades[$userid] = new object();
			$grades[$userid]->id         = $userid;
			$grades[$userid]->userid     = $userid;
			$grades[$userid]->rawgrade = isset($rawgradeinfo->score) ? $rawgradeinfo->score : NULL;
			$grades[$userid]->dategraded = isset($rawgradeinfo->time) ? $rawgradeinfo->time : NULL;
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


	//If the item is completable we base the grade on the SCORE which is 0-100
	//
	// MAR2011 NOTE: In some instances a course maybe completable but NOT return a score
	// we see this when for example a course can be completed by paging through all
	// screens instead of taking a test
	// We could consider making the grading a config setting in mod_form to allow
	// it to be changed per asset.
	//
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

	if (empty($attempt)) {
		$attempt = skillsoft_get_last_attempt($skillsoft->id,$user->id);
		if ($attempt == 0) {
			$attempt = 1;
		}	
	}
	$return = NULL;

	if ($userdata = skillsoft_get_tracks($skillsoft->id, $user->id, $attempt)) {
		$a = new object();
		$a->attempt = $attempt;
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
	$table->tablealign = 'center';
	$table->head = array(
	get_string('skillsoft_attempt', 'skillsoft'),
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
	$table->align = array('left','left', 'left', 'left', 'center','center','right','right','right','right');
	$table->wrap = array('','', '','','nowrap','nowrap','nowrap','nowrap','nowrap','nowrap');
	$table->width = '80%';
	$table->size = array('*','*', '*', '*', '*', '*', '*', '*', '*', '*');
	$row = array();
	$score = '&nbsp;';

	$maxattempts = skillsoft_get_last_attempt($skillsoft->id,$user->id);
	if ($maxattempts == 0) {
		$maxattempts = 1;
	}
	for ($a = $maxattempts; $a > 0; $a--) {
		$row = array();
		$score = '&nbsp;';
		if ($trackdata = skillsoft_get_tracks($skillsoft->id,$user->id,$a)) {
			$row[] = '<a href="'.$CFG->wwwroot.'/mod/skillsoft/report.php?id='.$skillsoft->id.'&user=true&attempt='.$trackdata->attempt.'">'.$trackdata->attempt.'</a>';
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

	$sql=	"SELECT	s.id,
       				s.name,
       				Count(s.id) AS countlaunches
			FROM {$CFG->prefix}skillsoft s
			LEFT JOIN {$CFG->prefix}skillsoft_au_track m ON s.id = m.skillsoftid
			WHERE 	s.course=$course->id
			 		AND m.element='[SUMMARY]lastaccess'
			 		AND m.value>$timestart
			GROUP BY s.id, s.name";

	if(!$records = get_records_sql($sql)) {
		return false;
	}
	
	$names = array();
	foreach ($records as $id => $record){
		if ($cm = get_coursemodule_from_instance('skillsoft', $record->id, $course->id)) {
			$context = get_context_instance(CONTEXT_MODULE, $cm->id);
			if (has_capability('mod/skillsoft:viewreport', $context)) {
				$name = '<a href="'.$CFG->wwwroot.'/mod/skillsoft/report.php?id='.$id.'">'.$record->name.'</a>'.'&nbsp;';
				if ($record->countlaunches > 1) {
					$name .= " ($record->countlaunches)";
				}
				$names[] = $name;
			}
		}
	}

	if (count($names) > 0) {
		print_headline(get_string('modulenameplural', 'skillsoft').':');
		echo '<div class="head"><div class="name">'.implode('<br />', $names).'</div></div>';
		return true;
	} else {
		return false;  //  True if anything was printed, otherwise false
	}
}

/**
 * Returns all SkillSoft Asset Accesses since a given time in specified course.
 *
 * @todo Document this functions args
 * @global object
 * @global object
 * @global object
 * @global object
 */
function skillsoft_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
	// Returns all skillsoft access since a given time.
	global $CFG, $COURSE;

	if ($COURSE->id == $courseid) {
		$course = $COURSE;
	} else {
		$course = get_record('course', 'id' ,$courseid);
	}

	$modinfo =& get_fast_modinfo($course);

	$cm = $modinfo->cms[$cmid];

	if ($userid) {
		$userselect = "AND u.id = '$userid'";
	} else {
		$userselect = "";
	}

	$sql = "SELECT
	  a.*,
	  s.name,
	  s.course,
	  cm.instance,
	  cm.section,
	  u.firstname,
	  u.lastname,
	  u.email,
	  u.picture,
	  u.imagealt
	FROM {$CFG->prefix}skillsoft_au_track AS a
	LEFT JOIN {$CFG->prefix}user AS u ON a.userid = u.id
	LEFT JOIN {$CFG->prefix}skillsoft AS s ON a.skillsoftid = s.id
	LEFT JOIN {$CFG->prefix}course_modules AS cm ON a.skillsoftid = cm.instance
	WHERE
	  a.value > $timestart
	  AND a.element = '[SUMMARY]lastaccess'
	  AND cm.id = $cm->id
	  $userselect
	ORDER BY
	  a.skillsoftid DESC, a.timemodified ASC";
	
	$records = get_records_sql($sql);

        if (!empty($records)) {
        	foreach ($records as $record) {
        		if (empty($groupid) || groups_is_member($groupid, $record->userid)) {

        			unset($activity);

					$activity->type = "skillsoft";
					$activity->cmid = $cm->id;
					$activity->name = $record->name;
					$activity->sectionnum = $cm->sectionnum;
					$activity->timestamp = $record->timemodified;
					
					$activity->content = new stdClass();
					$activity->content->instance = $record->instance;
					$activity->content->attempt = $record->attempt;
					$activity->content->lastaccessdate = $record->value;
					
					$activity->user = new stdClass();
					$activity->user->id = $record->userid;
					$activity->user->firstname = $record->firstname;
					$activity->user->lastname  = $record->lastname;
					$activity->user->picture   = $record->picture;
					$activity->user->imagealt = $record->imagealt;
					$activity->user->email = $record->email;
					
					$activities[] = $activity;
        
        			$index++;
        		}
        	} // end foreach
        }
}

function skillsoft_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
	/// Basically, this function prints the results of "skillsoft_get_recent_activity"
	global $CFG;
	
	echo '<table border="0" cellpadding="3" cellspacing="0" class="skillsoft-recent">';
	echo "<tr>";
	
	echo "<td class=\"userpicture\" valign=\"top\">";
	print_user_picture($activity->user, $courseid);
	echo "</td>";
	
	echo "<td>";
	echo '<div class="title">';
	if ($detail) {
		// activity icon
		$src = "$CFG->modpixpath/$activity->type/icon.gif";
		echo '<img src="'.$src.'" class="icon" alt="'.$activity->type.'" /> ';
	}
	echo $activity->name.' - ';
	echo get_string('skillsoft_attempt', 'skillsoft').' '.$activity->content->attempt;
	echo '</div>';
	
	echo '<div class="user">';
	$fullname = fullname($activity->user, $viewfullnames);
	$timeago = format_time(time() - $activity->content->lastaccessdate);
	$userhref = "$CFG->wwwroot/user/view.php?id=".$activity->user->id."&course=".$courseid;
	echo '<a href="'.$userhref.'">'.$fullname.'</a>';
	echo ' - ' . userdate($activity->content->lastaccessdate) . ' ('.$timeago.')';
	echo '</div>';
	
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	return;
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


function skillsoft_customreport($includetoday=false) {
	require_once('olsalib.php');


	//Constants for custom report preocessing
	define('CUSTOMREPORT_RUN', '0');
	define('CUSTOMREPORT_POLL', '1');
	define('CUSTOMREPORT_DOWNLOAD', '2');
	define('CUSTOMREPORT_IMPORT', '3');
	define('CUSTOMREPORT_PROCESS', '4');

	global $CFG;

	//Step 1 - Check if we have an outstanding report
	//Get last report where url = '' indicating report submitted BUT not ready yet
	//Should only be 1 record
	$reports = get_records_select('skillsoft_report_track', '', 'id desc', '*', '0', '1');
	//We have a report row now we have to decide what to do:
	if ($reports) {
		$report = end($reports);
		if ($report->polled == 0) {
			$state= CUSTOMREPORT_POLL;
		} else if ($report->downloaded == 0) {
	 	$state= CUSTOMREPORT_DOWNLOAD;
		} else if ($report->imported == 0) {
	 	$state= CUSTOMREPORT_IMPORT;
		} else if ($report->processed == 0) {
	 	$state= CUSTOMREPORT_PROCESS;
		} else {
	 	$state = CUSTOMREPORT_RUN;
		}
	} else {
		$state = CUSTOMREPORT_RUN;
	}

	$tab = '    ';

	mtrace(get_string('skillsoft_customreport_init','skillsoft'));
	//Now switch based on state
	switch ($state) {
		case CUSTOMREPORT_POLL:
			skillsoft_poll_customreport($report->handle, true);
			break;
		case CUSTOMREPORT_DOWNLOAD:
			//The report is there so lets download it
			$downloadedfile=skillsoft_download_customreport($report->handle, $report->url,NULL,true);
			flush();
			break;
		case CUSTOMREPORT_IMPORT:
			//Import the CSV to the database
			$importsuccess = skillsoft_import_customreport($report->handle, $report->localpath,true);
			if ($importsuccess) {
				//Update the $CFG setting
				set_config('skillsoft_reportstartdate', $report->enddate);
				//Delete the downloaded file
				if(is_file($report->localpath)) {
					$deleteokay = unlink($report->localpath);
				}
			}
			break;
		case CUSTOMREPORT_PROCESS:
			//Convert the imported results into moodle records and gradebook
			skillsoft_process_received_customreport($report->handle, true);
			break;
		case CUSTOMREPORT_RUN:
			skillsoft_run_customreport(true,NULL,$includetoday);
			break;
	}
	mtrace(get_string('skillsoft_customreport_end','skillsoft'));
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

	if ($CFG->skillsoft_trackingmode == TRACK_TO_OLSA_CUSTOMREPORT) {
		//We are in "Track to OLSA (Custom Report)" so perform custom report cycle
		//This is where we generate custom report for last 24 hours (or catchup), download it and then import it
		//assumption is LoginName will be the value we selected here for $CFG->skillsoft_useridentifier
		skillsoft_customreport();
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
