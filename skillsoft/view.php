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
 * Prints a particular instance of aicc
 *
 * @package   mod-skillsoft
 * @author    Martin Holden
 * @copyright 2009-2011 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

require_js($CFG->wwwroot . '/mod/skillsoft/skillsoft.js');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // skillsoft asset instance ID - it should be named as the first character of the module

if (!empty($id)) {
	if (! $cm = get_coursemodule_from_id('skillsoft', $id)) {
		error('Course Module ID was incorrect');
	}
	if (! $course = get_record('course', 'id', $cm->course)) {
		error('Course is misconfigured');
	}
	if (! $skillsoft = get_record('skillsoft', 'id', $cm->instance)) {
		error('Course module is incorrect');
	}
} else if (!empty($a)) {
	if (! $skillsoft = get_record('skillsoft', 'id', $a)) {
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

require_login($course->id, false, $cm);

$context = get_context_instance(CONTEXT_COURSE, $course->id);

$strskillsofts = get_string('modulenameplural', 'skillsoft');
$strskillsoft  = get_string('modulename', 'skillsoft');

if (isset($SESSION->skillsoft_id)) {
	unset($SESSION->skillsoft_id);
}

$SESSION->skillsoft_id = $skillsoft->id;
$SESSION->skillsoft_status = 'Not Initialized';
$SESSION->skillsoft_mode = 'normal';
$SESSION->skillsoft_attempt = 1;


if ($course->id != SITEID) {

	if ($skillsofts = get_all_instances_in_course('skillsoft', $course)) {
		// The module AICC activity with the least id is the course
		$firstskillsoft = current($skillsofts);
		if (!(($course->format == 'skillsoft') && ($firstskillsoft->id == $skillsoft->id))) {
			$navlinks[] = array('name' => $strskillsofts, 'link' => "index.php?id=$course->id", 'type' => 'activity');
		}
	}
}
$pagetitle = strip_tags($course->shortname.': '.format_string($skillsoft->name).' ('.format_string($skillsoft->assetid).')');

add_to_log($course->id, 'skillsoft', 'view activity', 'view.php?id='.$cm->id, 'View SkillSoft Asset: '.$skillsoft->name, $cm->id);

//
// Print the page header
//
$navlinks = array();
$navlinks[] = array('name' => format_string($skillsoft->name,true), 'link' => 'view.php?id=$cm->id', 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

print_header($pagetitle, $course->fullname, $navigation,
                 '', '', true, update_module_button($cm->id, $course->id, $strskillsoft), navmenu($course, $cm));

$attempt = skillsoft_get_last_attempt($skillsoft->id, $USER->id);
if ($attempt == 0) {
	$attempt = 1;
}

echo '<div class="reportlink"><a href="report.php?id='.$skillsoft->id.'&user=true&attempt='.$attempt.'">'.get_string('skillsoft_viewreport','skillsoft').'</a></div>';

// Print the main part of the page
print_heading(format_string($skillsoft->name).' ('.format_string($skillsoft->assetid).')');

if (!empty($skillsoft->summary)) {
	print_box('<div class="structurehead">'.get_string('skillsoft_summary', 'skillsoft').'</div>'.format_text($skillsoft->summary), 'generalbox boxaligncenter boxwidthwide', 'summary');
}
if (!empty($skillsoft->audience)) {
	print_box('<div class="structurehead">'.get_string('skillsoft_audience', 'skillsoft').'</div>'.format_text($skillsoft->audience), 'generalbox boxaligncenter boxwidthwide', 'audience');
}
if (!empty($skillsoft->prereq)) {
	print_box('<div class="structurehead">'.get_string('skillsoft_prereq', 'skillsoft').'</div>'.format_text($skillsoft->prereq), 'generalbox boxaligncenter boxwidthwide', 'prereq');
}
if (!empty($skillsoft->duration)) {
	print_box('<div class="structurehead">'.get_string('skillsoft_duration', 'skillsoft').'</div>'.format_text($skillsoft->duration), 'generalbox boxaligncenter boxwidthwide', 'duration');
}
print_box(skillsoft_view_display($skillsoft, $USER, true), 'generalbox boxaligncenter boxwidthwide', 'courselaunch');

print_footer($course);

?>