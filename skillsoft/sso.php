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
 * Performs a seamless login to SkillPort
 *
 * @package   mod-skillsoft
 * @author    Martin Holden
 * @copyright 2009-2011 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/olsalib.php');

$a = required_param('a', PARAM_INT); 

if (!empty($a)) {
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

if (strtolower($skillsoft->assetid) == "sso") {
	//We are using the "custom" assetid for seamless login to the home page
	//of SkillPort
	$lcl_actiontype = "home";
	$lcl_assetid = "";
} else {
	//We have a real SkillSoft AssetId
	$lcl_actiontype = $CFG->skillsoft_sso_actiontype;
	$lcl_assetid = $skillsoft->assetid;
}

//Section 508 Enhancement - add x508 value of $user->screenreader
if ($USER->screenreader == 1) {
	$userx508 = true;
} else {
	$userx508 = false;
}

if ($CFG->skillsoft_trackingmode != TRACK_TO_LMS ) {
	//We are in "Track to OLSA" so perform SSO
	$response = SO_GetMultiActionSignOnUrl(
	$CFG->skillsoft_accountprefix.$USER->{$CFG->skillsoft_useridentifier},
	$USER->firstname,
	$USER->lastname,
	$USER->email,
	"",
	"",
	$lcl_actiontype,
	$lcl_assetid,
	$userx508
	);
	
	if (!$response->success) {
		//Check if we failed because no groupcode and resubmit
		if (!stripos($response->errormessage, "the property '_pathid_' or '_orgcode_' must be specified") == false)
		{
			$response = SO_GetMultiActionSignOnUrl(
			$CFG->skillsoft_accountprefix.$USER->{$CFG->skillsoft_useridentifier},
			$USER->firstname,
			$USER->lastname,
			$USER->email,
			"",
			$CFG->skillsoft_defaultssogroup,
			$lcl_actiontype,
			$lcl_assetid,
			$userx508
			);				
		} 
	}
} else {
	$response = new olsaresponse(false,get_string('skillsoft_ssomodeerror','skillsoft'),NULL);
}

//Log minimal data if success
//Disabled for anything other than SSO
// issues when importing the resulting OLSA data for assets as timestamps differ for firstaccess,
//resulting in incorrect recording of attempts
if (!$skillsoft->completable) {
	if ($response->success) {
		$now = time();
		$id = skillsoft_setFirstAccessDate($USER->id, $skillsoft->id, 1, $now);
		$id = skillsoft_setLastAccessDate($USER->id, $skillsoft->id, 1, $now);
		$id = skillsoft_setAccessCount($USER->id, $skillsoft->id, 1);
	}
}

$skillsoftpixdir = $CFG->modpixpath.'/skillsoft/pix';
?><html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<title><?php echo get_string('skillsoft_ssotitle', 'skillsoft');?></title>
<script type="text/javascript">
	function doit() {
		<?php
		if ($response->success) {
			print "var popupBlocker = false;\n";
			print "var win = window.open('".$response->result->olsaURL."','_blank');\n";
			print "try {\n";
			print "win.focus();\n";
			print "}\n";
			print "catch(e) {\n";
			print "popupBlocker = true;\n";
			print "}\n";
			
			print "if (popupBlocker) {\n";
			print "var errorDiv = document.getElementById('errormessage');\n";
			print "errorDiv.innerHTML = '".get_string('skillsoft_ssopopupdetected','skillsoft')."';\n";
			//print "var olsaURL = document.createElement('a');\n";
			//print "olsaURL.setAttribute('id','olsalaunchurl');\n";
			//print "olsaURL.setAttribute('href','".$response->result->olsaURL."');\n";
			//print "olsaURL.innerHTML = 'Click Here';\n";
			//print "errorDiv.appendChild(olsaURL);\n";
			print "errorDiv.style.display = 'block';\n";
			print "document.getElementById('waitingmessage').style.display = 'none';\n";			
			print "} else { \n";
			print "var errorDiv = document.getElementById('errormessage');\n";
			print "errorDiv.innerHTML = '".get_string('skillsoft_ssopopupopened','skillsoft')."';\n";
			print "errorDiv.style.display = 'block';\n";
			print "document.getElementById('waitingmessage').style.display = 'none';\n";			
			print "//Close the window after 5 seconds";
			print "window.open('', '_self', '');\n";
			print "window.setTimeout('window.close();', 5000);\n";
			print "}\n";
			//print "document.location = ".'"'.$response->result->olsaURL.'";';
		} else {
			//error($response->errormessage);
			
			print "document.getElementById('waitingmessage').style.display = 'none';";
			print "document.getElementById('errormessage').style.display = 'block';";
		}
		?>	
	}


</script>
</head>
<body onload="doit();">
<div id="waitingmessage">
<p><?php echo get_string('skillsoft_ssoloading', 'skillsoft');?>&nbsp;<img
	src='<?php echo $skillsoftpixdir;?>/wait.gif'></p>
</div>
<div id="errormessage" style="display: none;">
<p><?php
print close_window_button();
if ($CFG->skillsoft_trackingmode != TRACK_TO_LMS) {
	print '<p>'.get_string('skillsoft_ssoerror', 'skillsoft').'</p>';
}
print '<p>'.$response->errormessage.'</p>';
?></p>
</div>
</body>
</html>
