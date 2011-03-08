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
 * @copyright 2009-2011 Martin Holden
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

    if ($result && $oldversion < 2011011200) {

    /// Define table skillsoft_report_track to be created
        $table = new XMLDBTable('skillsoft_report_track');

    /// Adding fields to table skillsoft_report_track
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('startdate', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('enddate', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('handle', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('url', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('localpath', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('polled', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('downloaded', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('imported', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('processed', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timerequested', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timepolled', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timedownloaded', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timeimported', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timeprocessed', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table skillsoft_report_track
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for skillsoft_report_track
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2011011200) {

    /// Define table skillsoft_report_results to be created
        $table = new XMLDBTable('skillsoft_report_results');

    /// Adding fields to table skillsoft_report_results
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('loginname', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, 'null');
        $table->addFieldInfo('lastname', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, 'null');
        $table->addFieldInfo('firstname', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, 'null');
        $table->addFieldInfo('assetid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('firstaccessdate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');
        $table->addFieldInfo('lastaccessdate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');
        $table->addFieldInfo('completeddate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');
        $table->addFieldInfo('firstscore', XMLDB_TYPE_NUMBER, '10, 2', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('currentscore', XMLDB_TYPE_NUMBER, '10, 2', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('bestscore', XMLDB_TYPE_NUMBER, '10, 2', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('lessonstatus', XMLDB_TYPE_CHAR, '30', null, null, null, null, null, null);
        $table->addFieldInfo('duration', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('accesscount', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('processed', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table skillsoft_report_results
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table skillsoft_report_results
        $table->addIndexInfo('loginname-assetid', XMLDB_INDEX_UNIQUE, array('loginname', 'assetid'));

    /// Launch create table for skillsoft_report_results
        $result = $result && create_table($table);
    }

    if ($result && $oldversion = 2011011200) {
        /// Define field username to be added to skillsoft_tdr
        $table = new XMLDBTable('skillsoft_report_track');
        $field = new XMLDBField('polled');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'localpath');
	    /// Launch add field polled
        $result = $result && add_field($table, $field);

        $field = new XMLDBField('imported');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'downloaded');
	    /// Launch add field polled
        $result = $result && add_field($table, $field);

        $field = new XMLDBField('timepolled');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timerequested');
        $result = $result && add_field($table, $field);

        $field = new XMLDBField('timeimported');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timedownloaded');
        $result = $result && add_field($table, $field);
    }
	return $result;
}