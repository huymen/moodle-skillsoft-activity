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
 * @copyright 2009-2011 Martin Holden
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
$string['skillsoft_trackingmodedesc'] = 'The mode the OLSA site is configured for, if Track to LMS results are returned to LMS using AICC. If Track to OLSA the results are stored in OLSA Server and need to be retrieved, options for this are On Demand Communications or via a custom report for previous 24-hrs data.';

$string['skillsoft_useridentifier'] = 'Moodle/SkillSoft User Identifier';
$string['skillsoft_useridentifierdesc'] = 'The user data field to use as common identifier between Moodle and OLSA. We recommend the Moodle user ID as this is a system generated value and will not change in Moodle even if the users Username is modified.';
$string['skillsoft_userid_identifier'] = 'ID';
$string['skillsoft_username_identifier'] = 'Username';

$string['skillsoft_tracktolms'] = 'Track to LMS';
$string['skillsoft_tracktoolsa'] = 'Track to OLSA (On Demand Communications)';
$string['skillsoft_tracktoolsacustomreport'] = 'Track to OLSA (Custom Report)';

$string['skillsoft_ssourl'] = 'Single SignOn URL';
$string['skillsoft_ssourldesc'] = 'Enter the URL for the single signon use %%s to indicate the activity id location. i.e. http://myserver/signon.aspx?coursename=%%s&action=launch. Leave blank to use AICC.';

$string['skillsoft_sso_actiontype'] = 'Select the OLSA Action Type';
$string['skillsoft_sso_actiontypedesc'] = 'Select the actiontype for launching assets using SSO mode';
$string['skillsoft_sso_actiontype_launch'] = 'Launch Asset without showing SkillPort UI (launch)';
$string['skillsoft_sso_actiontype_summary'] = 'Launch Asset Summary Page in SkillPort UI (summary)';

$string['skillsoft_defaultssogroup'] = 'SkillSoft Default Group List';
$string['skillsoft_defaultssogroupdesc'] = 'A comma seperated list of the default groups to send for new users during SSO to SkillPort. Existing users group membership in SkillPort is not altered.';

$string['skillsoft_settingsmissing'] = 'Can not retrieve SkillSoft OLSA Settings: please check the configuration settings.';

$string['skillsoft_accountprefix'] = 'Account Prefix';
$string['skillsoft_accountprefixdesc'] = 'Enter a prefix which will be added in front of the username sent to SkillPort.';

$string['skillsoft_reportstartdate'] = 'Custom Report Start Date';
$string['skillsoft_reportstartdatedesc'] = 'Enter the start date for the custom report to retrieve data. This field is automatically updated every time the report successfully runs.';

$string['skillsoft_reportincludetoday'] = 'Custom Report Include Todays Data';
$string['skillsoft_reportincludetodaydesc'] = 'The report defaults to including data upto and including the previous day, this override makes the report include todays data.';

$string['skillsoft_usesso'] = 'Use OLSA SSO';
$string['skillsoft_usessodesc'] = 'Use the OLSA Web Services SSO function, thiis requires one of the Track to OLSA modes. If unchecked all launches uses the AICC launch process';


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
$string['skillsoft_newattempt'] = 'You have already completed this course. Tick here to start a new attempt?';

//loadau.php
$string['skillsoft_loading'] = "You will be automatically redirected to the activity in";  // used in conjunction with numseconds
$string['skillsoft_pleasewait'] = "Activity loading, please wait ....";

$string['skillsoft_olsassoapauthentication'] = 'The OLSA Credentials are incorrect: please check the module configuration settings.';
$string['skillsoft_olsassoapinvalidassetid'] = 'The Asset ID specified does not exist. Asset ID=$a';
$string['skillsoft_olsassoapfault'] = 'SOAP Fault During OLSA Call. Faultstring=$a';

$string['skillsoft_olsassoapreportnotready'] = 'The report is not yet ready.';
$string['skillsoft_olsassoapreportnotvalid'] = 'The report handle specified does not exist. Handle=$a';


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

$string['skillsoft_customreport_init'] = 'Initialising SkillSoft Custom Report Cycle';
$string['skillsoft_customreport_end'] = 'End SkillSoft Custom Report Cycle';

$string['skillsoft_customreport_run_start'] = 'Start Submit Custom Report';
$string['skillsoft_customreport_run_initerror'] = 'Error Received while initialising Custom Report Download Cycle. Error=$a';
$string['skillsoft_customreport_run_alreadyrun'] = 'Report for startdate and endate are the same indicating report already processed.';
$string['skillsoft_customreport_run_startdate'] = 'Report Start Date = $a';
$string['skillsoft_customreport_run_enddate'] = 'Report End Date = $a';
$string['skillsoft_customreport_run_response'] = 'Report Submitted. Handle = $a';
$string['skillsoft_customreport_run_end'] = 'End Submit Custom Report';

$string['skillsoft_customreport_poll_start'] = 'Start Poll for Custom Report';
$string['skillsoft_customreport_poll_polling'] = 'Polling for Report. Handle = $a';
$string['skillsoft_customreport_poll_ready'] = 'Report Ready';
$string['skillsoft_customreport_poll_notready'] = 'Report Not Ready.';
$string['skillsoft_customreport_poll_doesnotexist'] = 'Report Does Not Exist.';
$string['skillsoft_customreport_poll_end'] = 'End Poll for Custom Report';

$string['skillsoft_customreport_download_start'] = 'Start Download of Report';
$string['skillsoft_customreport_download_url'] = 'Report URL. URL=$a';
$string['skillsoft_customreport_download_curlnotavailable'] = 'curl extension not available.';
$string['skillsoft_customreport_download_createdirectoryfailed'] = 'Unable to create download folder. Folder=$a';
$string['skillsoft_customreport_download_socksproxyerror'] = 'SOCKS5 proxy is not supported in PHP4';
$string['skillsoft_customreport_download_result'] = 'Downloaded $a->bytes bytes in $a->total_time seconds. Saved to $a->filepath';
$string['skillsoft_customreport_download_error'] = 'Download Failed. Error=$a';
$string['skillsoft_customreport_download_end'] = 'End Download of Report';

$string['skillsoft_customreport_import_start'] = 'Start Importing Downloaded Report';
$string['skillsoft_customreport_import_rowcount'] = 'Rows Processed = $a';
$string['skillsoft_customreport_import_totalrow'] = 'Total Rows Processed = $a';
$string['skillsoft_customreport_import_errorrow'] = 'Import Failed on row = $a';
$string['skillsoft_customreport_import_end'] = 'End Importing Downloaded Report';


$string['skillsoft_customreport_process_start'] = 'Start Processing retrieved Report Results';
$string['skillsoft_customreport_process_totalrecords'] = 'Total records to process = $a';
$string['skillsoft_customreport_process_batch'] = 'Processing batch of records. Start Record Position = $a';
$string['skillsoft_customreport_process_retrievedresults'] = 'Processing Report Results. ID=$a->id   SkillSoftID=$a->skillsoftid   UserID=$a->userid';
$string['skillsoft_customreport_process_end'] = 'End Processing retrieved Report Results';


//summary
$string['skillsoft_summarymessage'] = 'Attempt: $a->attempt<br/>Access Count: $a->accesscount<br/>Total Time: $a->duration<br />Best Score: $a->bestscore';

//backuplib.php
$string['skillsoft_trackedelement'] = 'AICC Datamodel Elements';

//ssopreloader.php
$string['skillsoft_ssotitle'] = 'Logging in to SkillPort';
$string['skillsoft_ssoloading'] = 'Please wait while we log you into SkillPort';
$string['skillsoft_ssoerror'] = 'An error has occurred while trying to perform Skillport login. Details:';
$string['skillsoft_ssomodeerror'] = 'SkillPort seamless login is only available in Track to OLSA mode.';

$string['skillsoft_ssopopupopened'] = 'This window will automatically close in 5 seconds.<br/>';
$string['skillsoft_ssopopupdetected'] = 'A popup blocker prevented the completion of this launch.<br/>Please disable your popup blocker and try again.<br/>';

//getolsadata.php - SSO
$string['skillsoft_ssoassettitle'] = 'Login to SkillPort';
$string['skillsoft_ssoassetsummary'] = 'Login to SkillPort seamlessly';

//Attempts
$string['skillsoft_attempt'] = 'Attempt';
$string['skillsoft_lastattempt'] = 'Last Attempt';
$string['skillsoft_allattempt'] = 'All Attempts';