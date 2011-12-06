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
 * @copyright 2009-20111 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/// Replace skillsoft with the name of your module and remove this line

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT);   // course id

if (!empty($id)) {
	if (! $course = get_record('course', 'id', $id)) {
		error('Course ID is incorrect');
	}
} else {
	error('A required parameter is missing');
}

require_course_login($course);

add_to_log($course->id, 'skillsoft', 'view all', 'index.php?id='.$course->id, 'View all SkillSoft Assets');

//Retrieve the localisation strings

$strskillsoft = get_string('modulename', 'skillsoft');
$strskillsofts = get_string('modulenameplural', 'skillsoft');
$strweek = get_string('week');
$strtopic = get_string('topic');
$strskillsoftid = get_string('skillsoft_assetid', 'skillsoft');
$strskillsoftsummary = get_string('skillsoft_summary', 'skillsoft');
$strlastmodified = get_string('lastmodified');
$strname = get_string('skillsoft_name','skillsoft');

//Navigation Links
$navlinks = array();
$navlinks[] = array('name' => $strskillsofts, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple($strskillsofts, '', $navigation, '', '', true, '', navmenu($course));

if (! $skillsofts = get_all_instances_in_course('skillsoft', $course)) {
	notice(get_string('thereareno', 'moodle', $strskillsofts), '../../course/view.php?id=$course->id');
	exit;
}

if ($course->format == 'weeks') {
	$table->head  = array ($strweek, $strskillsoftid, $strname, $strskillsoftsummary);
	$table->align = array ('center', 'left', 'left','left');
} else if ($course->format == 'topics') {
	$table->head  = array ($strtopic, $strskillsoftid, $strname, $strskillsoftsummary);
	$table->align = array ('center', 'left', 'left','left');
} else {
	$table->head  = array ($strlastmodified, $strskillsoftid, $strname, $strskillsoftsummary);
	$table->align = array ('left', 'left', 'left','left');
}

foreach ($skillsofts as $skillsoft) {
	$context = get_context_instance(CONTEXT_MODULE,$skillsoft->coursemodule);
	$tt = '';
	if ($course->format == 'weeks' or $course->format == 'topics') {
		if ($skillsoft->section) {
			$tt = $skillsoft->section;
		}
	} else {
		$tt = userdate($skillsoft->timemodified);
	}

	if (!$skillsoft->visible) {
		//Show dimmed if the mod is hidden
		$table->data[] = array ($tt, $skillsoft->assetid, '<a class="dimmed" href="view.php?id='.$skillsoft->coursemodule.'">'.format_string($skillsoft->name).'</a>', $skillsoft->summary);
	} else {
		//Show normal if the mod is visible
		$table->data[] = array ($tt, $skillsoft->assetid, '<a href="view.php?id='.$skillsoft->coursemodule.'">'.format_string($skillsoft->name).'</a>', $skillsoft->summary);
	}
}
echo '<br />';
print_table($table);
print_footer($course);
?>
