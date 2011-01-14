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
 * This file keeps track of upgrades to the olsasso module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installtion to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in
 * lib/ddllib.php
 *
 * @package   mod-olsa
 * @author 	  Martin Holden
 * @copyright 2009 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * xmldb_skillsoft_upgrade
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_skillsoft_upgrade($oldversion=0) {

	global $CFG, $THEME, $db;

	$result = true;
	
    if ($result && $oldversion < 2010040700) {
    /// Define field username to be added to skillsoft_tdr
        $table = new XMLDBTable('skillsoft_tdr');
        $field = new XMLDBField('username');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, null, null, null, null, 'null', 'userid');

    /// Launch add field username
        $result = $result && add_field($table, $field);
    }
	
	return $result;
}