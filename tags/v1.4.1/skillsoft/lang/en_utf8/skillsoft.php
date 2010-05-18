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
 * English strings for aicc
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package   mod-skillsoft
 * @author 	  Martin Holden
 * @copyright 2009 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'SkillSoft Asset';
$string['modulenameplural'] = 'SkillSoft Assets';
$string['noassets'] = 'No SkillSoft Assets';
$string['skillsoft_na'] = 'N/A';

//Capabilities
$string['skillsoft:viewreport'] = 'View Report for all users';

//Settings.php
$string['skillsoft_olsaendpoint'] = 'SkillSoft OLSA EndPoint';
$string['skillsoft_olsaendpointdesc'] = 'The URI for the SkillSoft OLSA Web Services EndPoint, for example http://test.skillwsa.com/olsa/services/Olsa. The URI is case sensitive';

$string['skillsoft_olsacustomerid'] = 'SkillSoft OLSA Customer ID';
$string['skillsoft_olsacustomeriddesc'] = 'The Customer ID used with OLSA for authentication';

$string['skillsoft_olsasharedsecret'] = 'SkillSoft OLSA Shared Secret';
$string['skillsoft_olsasharedsecretdesc'] = 'The Shared Secret used with OLSA for authentication';

$string['skillsoft_sessionpurge'] = 'Number of hours to keep sessionid';
$string['skillsoft_sessionpurgedesc'] = 'The number of hours that sessionids are kept before purging during CRON run.';

$string['skillsoft_trackingmode'] = 'SkillSoft Tracking Mode';
$string['skillsoft_trackingmodedesc'] = 'The mode the OLSA site is configured for, if Track to LMS results are returned to LMS using AICC. If Track to OLSA the results are stored in OLSA Server and need to be retrieved on ExitAU call';

$string['skillsoft_useridentifier'] = 'Moodle/SkillSoft User Identifier';
$string['skillsoft_useridentifierdesc'] = 'The user data field to use as common identifier between Moodle and OLSA. We recommend the Moodle user ID as this is a system generated value and will not change in Moodle even if the users Username is modified.';
$string['skillsoft_userid_identifier'] = 'ID';
$string['skillsoft_username_identifier'] = 'Username';

$string['skillsoft_tracktolms'] = "Track to LMS";
$string['skillsoft_tracktoolsa'] = "Track to OLSA";

$string['skillsoft_settingsmissing'] = 'Can not retrieve SkillSoft OLSA Settings: please check the configuration settings.';

//mod_form.php
$string['skillsoft_assetid'] = 'Asset ID';
$string['skillsoft_retrievemetadata'] = 'Retrieve Metadata';
$string['skillsoft_updatemetadata'] = 'Update Metadata';
$string['skillsoft_name'] = 'Title';
$string['skillsoft_summary'] = 'Overview/Description';
$string['skillsoft_audience'] = 'Target Audience';
$string['skillsoft_prereq'] = 'Prerequisites';
$string['skillsoft_launch'] = 'Launch URL';
$string['skillsoft_mastery'] = 'Mastery Score';
$string['skillsoft_duration'] = 'Duration (minutes)';
$string['skillsoft_assettype'] = 'Asset Type';

//view.php
$string['skillsoft_enter'] = 'Launch';
$string['skillsoft_viewreport'] = 'View My Report';
$string['skillsoft_viewallreport'] = 'View Report';

//loadau.php
$string['skillsoft_loading'] = "You will be automatically redirected to the activity in";  // used in conjunction with numseconds
$string['skillsoft_pleasewait'] = "Activity loading, please wait ....";

$string['skillsoft_olsassoapauthentication'] = 'The OLSA Credentials are incorrect: please check the module configuration settings.';
$string['skillsoft_olsassoapinvalidassetid'] = 'The Asset ID specified does not exist. Asset ID=$a';
$string['skillsoft_olsassoapfault'] = 'SOAP Fault During OLSA Call. Faultstring=$a';

//preloader.php
$string['skillsoft_metadatatitle'] = "Updating";
$string['skillsoft_metadataloading'] = "Please wait while we retrieve the asset metadata from the OLSA Server";
$string['skillsoft_metadatasetting'] = "Please wait while we configure the activity";
$string['skillsoft_metadataerror'] = "An error has occurred while trying to retrieve the metadata. Details:";

//report.php
$string['skillsoft_firstaccess'] = "First Access";
$string['skillsoft_lastaccess'] = "Last Access";
$string['skillsoft_completed'] = "Completed";
$string['skillsoft_lessonstatus'] = "Status";
$string['skillsoft_totaltime'] = "Total Time";
$string['skillsoft_firstscore'] = "First Score";
$string['skillsoft_currentscore'] = "Current Score";
$string['skillsoft_bestscore'] = "Best Score";
$string['skillsoft_accesscount'] = "Access Count";

$string['skillsoft_noncompletable'] = 'This asset does not supply a Completed status or score';
$string['skillsoft_report'] = 'Report';

//cron.php
$string['skillsoft_purgemessage'] = 'Purging skillsoft session ids from database created before $a';
$string['skillsoft_odcinit'] = 'Initialising SkillSoft On-Demand Communications Cycle';
$string['skillsoft_odciniterror'] = 'Error Recieved while initialising On-Demand Communications. Error=$a';
$string['skillsoft_odcgetdatastart'] = 'Start Retrieving SkillSoft TDRs for handle=$a';
$string['skillsoft_odcgetdataend'] = 'End Retrieving SkillSoft TDRs for handle=$a';
$string['skillsoft_odcgetdataerror'] = 'Error while retrieving TDRs. Error=$a';
$string['skillsoft_odcgetdataprocess'] = 'Processing TDR. ID=$a';
$string['skillsoft_odcnoresultsavailable'] = 'No Results Available';
$string['skillsoft_odcackdata'] = 'Acknowledging handle=$a';
$string['skillsoft_odcackdataerror'] = 'Error while acknowledging handle. Error=$a';
$string['skillsoft_odcprocessinginit'] = 'Start Processing retrieved TDRs';
$string['skillsoft_odcprocessretrievedtdr'] = 'Processing TDR. ID=$a->tdrid   SkillSoftID=$a->skillsoftid   UserID=$a->userid';
$string['skillsoft_odcprocessingend'] = 'End Processing retrieved TDRs';

//summary
$string['skillsoft_summarymessage'] = 'Access Count: $a->accesscount<br/>Total Time: $a->duration<br />Best Score: $a->bestscore';

//backuplib.php
$string['skillsoft_trackedelement'] = 'AICC Datamodel Elements';
