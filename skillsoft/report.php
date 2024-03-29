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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package   mod-skillsoft
 * @author    Martin Holden
 * @copyright 2009-2011 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../config.php');
require_once('locallib.php');

$id = required_param('id', PARAM_INT);    // skillsoft ID, or
$user = optional_param('user', '', PARAM_BOOL);  // User report
$attempt = optional_param('attempt', '', PARAM_INT);  // attempt number

if (!empty($id)) {
	if (! $skillsoft = get_record('skillsoft', 'id', $id)) {
		error('Course module is incorrect');
	}
	if (! $course = get_record('course', 'id', $skillsoft->course)) {
		error('Course is misconfigured');
	}
	if (! $cm = get_coursemodule_from_instance('skillsoft', $skillsoft->id, $course->id)) {
		error('Course Module ID was incorrect');
	}
} else {
	error('A required parameter is missing');
}

require_course_login($course);

$contextmodule = get_context_instance(CONTEXT_MODULE,$cm->id);

//Retrieve the localisation strings
$strskillsoft = get_string('modulename', 'skillsoft');
$strskillsofts = get_string('modulenameplural', 'skillsoft');
$strskillsoftid = get_string('skillsoft_assetid', 'skillsoft');
$strskillsoftsummary = get_string('skillsoft_summary', 'skillsoft');
$strlastmodified = get_string('lastmodified');
$notapplicable = get_string('skillsoft_na','skillsoft').helpbutton('noncompletable', get_string('skillsoft_noncompletable','skillsoft'),'skillsoft', true, false,NULL,true);
$strreport  = get_string('skillsoft_report', 'skillsoft');
$strattempt  = get_string('skillsoft_attempt', 'skillsoft');
$strallattempt  = get_string('skillsoft_allattempt', 'skillsoft');

//Navigation Links
//$navlinks = array();
//$navlinks[] = array('name' => $strskillsofts, 'link' => '', 'type' => 'activity');
//$navigation = build_navigation($navlinks);

$navlinks = array();

//If user has viewreport permission enable "Report" link allowing viewing all usage of asset
if (has_capability('mod/skillsoft:viewreport', $contextmodule)) {
	$navlinks[] = array('name' => $strreport, 'link' => "report.php?id=$id", 'type' => 'title');
} else {
	$navlinks[] = array('name' => $strreport, 'link' => '', 'type' => 'title');
}

if ($user) {
	if (empty($attempt)) {
		$navlinks[] = array('name' => $strallattempt, 'link' => "report.php?id=$id&user=true", 'type' => 'title');
	} else {
		$navlinks[] = array('name' => $strallattempt, 'link' => "report.php?id=$id&user=true", 'type' => 'title');
		$navlinks[] = array('name' => $strattempt.' '.$attempt, 'link' => '', 'type' => 'title');
	}
}
$navigation = build_navigation($navlinks, $cm);

print_header(format_string($skillsoft->name), $skillsoft->name, $navigation,'', '', true);
print_heading(format_string($skillsoft->name));

$skillsoftpixdir = $CFG->modpixpath.'/skillsoft/pix';

if ($user) {
	//Print User Specific Data
	// Print general score data
	$table = new stdClass();
	$table->tablealign = 'center';
	$table->head = array(
	$strattempt,
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
	$table->wrap = array('', '', '','','nowrap','nowrap','nowrap','nowrap','nowrap','nowrap');
	$table->width = '80%';
	$table->size = array('*', '*', '*', '*', '*', '*', '*', '*', '*', '*');


	if (empty($attempt)) {

		//Show all attempts
		add_to_log($course->id, 'skillsoft', 'view report', 'report.php?id='.$id."&user=".$user, 'View report for Asset: '.$skillsoft->name);

		$maxattempts = skillsoft_get_last_attempt($skillsoft->id,$USER->id);
		if ($maxattempts == 0) {
			$maxattempts = 1;
		}
		
		for ($a = $maxattempts; $a > 0; $a--) {
			$row = array();
			$score = '&nbsp;';
			if ($trackdata = skillsoft_get_tracks($skillsoft->id,$USER->id,$a)) {
				$row[] = isset($trackdata->attempt) ? '<a href="report.php?id='.$skillsoft->id.'&user=true&attempt='.$trackdata->attempt.'">'.$trackdata->attempt.'</a>' : '<a href="report.php?id='.$skillsoft->id.'&user=true&attempt=1">1</a>';
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

	} else {
		add_to_log($course->id, 'skillsoft', 'view report', 'report.php?id='.$id."&user=".$user."&attempt=".$attempt, 'View report for Asset: '.$skillsoft->name);
		$row = array();
		$score = '&nbsp;';
		if ($trackdata = skillsoft_get_tracks($skillsoft->id,$USER->id,$attempt)) {
			$row[] = isset($trackdata->attempt) ? '<a href="report.php?id='.$skillsoft->id.'&user=true&attempt='.$trackdata->attempt.'">'.$trackdata->attempt.'</a>' : '<a href="report.php?id='.$skillsoft->id.'&user=true&attempt=1">1</a>';
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
} else {
	require_capability('mod/skillsoft:viewreport', $contextmodule);
	add_to_log($course->id, 'skillsoft', 'view report', 'report.php?id='.$id."&user=".$user, 'View all users report for Asset: '.$skillsoft->name);

	//Just report on the activity
	//SQL to get all get all userid/skillsoftid records
	$sql = "SELECT ai.userid, ai.skillsoftid
                        FROM {$CFG->prefix}skillsoft_au_track ai
                        WHERE ai.skillsoftid = {$skillsoft->id}
                        GROUP BY ai.userid,ai.skillsoftid
                        ";
	$table = new stdClass();
	$table->tablealign = 'center';
	$table->head = array(
	get_string('name'),
	$strattempt,
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
	$table->align = array('left','left','left', 'left', 'left', 'center','center','right','right','right','right');
	$table->wrap = array('','','', '','','nowrap','nowrap','nowrap','nowrap','nowrap','nowrap');
	$table->width = '80%';
	$table->size = array('*','*','*', '*', '*', '*', '*', '*', '*', '*', '*');

	if ($skillsoftusers=get_records_sql($sql))
	{
		foreach($skillsoftusers as $skillsoftuser){
			$row = array();
			$userdata = get_record('user','id',$skillsoftuser->userid,'','','','','firstname, lastname, picture');
			$row[] = print_user_picture($skillsoftuser->userid, $course->id, $userdata->picture, false, true).' '.'<a href="'.$CFG->wwwroot.'/user/view.php?id='.$skillsoftuser->userid.'&amp;course='.$course->id.'">'.fullname($userdata).'</a>';
			
			//Show last attempt for all users
			if ($trackdata = skillsoft_get_tracks($skillsoftuser->skillsoftid,$skillsoftuser->userid)) {
				$row[] = isset($trackdata->attempt) ? $trackdata->attempt : '1';
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
			} else {
				$row[] = '&nbsp;';
				$row[] = '&nbsp;';
				$row[] = '&nbsp;';
				$row[] = '&nbsp;';
				$row[] = '&nbsp;';
				$row[] = '&nbsp;';
				$row[] = '&nbsp;';
				$row[] = '&nbsp;';
				$row[] = '&nbsp;';
			}
			$table->data[] = $row;
		}
	}
}

print_table($table);
print_footer($course);
?>
