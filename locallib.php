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
 * Internal library of functions for module skillsoft
 *
 * All the skillsoft specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package   mod-skillsoft
 * @author	  Martin Holden
 * @copyright 2009 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(dirname(dirname(__FILE__))).'/lib/filelib.php');
require_once(dirname(__FILE__).'/aiccmodel.php');
require_once(dirname(__FILE__).'/aicclib.php');

defined('MOODLE_INTERNAL') || die();

/// Constants and settings for module skillsoft
define('TRACK_TO_LMS', '0');
define('TRACK_TO_OLSA', '1');
define('TRACK_TO_OLSA_CUSTOMREPORT', '2');

/// Constants and settings for module skillsoft
define('IDENTIFIER_USERID', 'id');
define('IDENTIFIER_USERNAME', 'username');

/// Constants and settings for module skillsoft
/// SSO actiontype for assets
define('SSO_ASSET_ACTIONTYPE_LAUNCH', 'launch');
define('SSO_ASSET_ACTIONTYPE_SUMMARY', 'summary');

/**
 * Returns an array of the array of what grade options
 *
 * @return array an array of OLSA Tracking Options
 */
function skillsoft_get_tracking_method_array(){
	return array (TRACK_TO_LMS => get_string('skillsoft_tracktolms', 'skillsoft'),
	TRACK_TO_OLSA => get_string('skillsoft_tracktoolsa', 'skillsoft'),
	TRACK_TO_OLSA_CUSTOMREPORT => get_string('skillsoft_tracktoolsacustomreport', 'skillsoft'),
	);
}

/**
 * Returns an array
 *
 * @return array an array of fileds to choose for tracking
 */
function skillsoft_get_user_identifier_array(){
	return array (IDENTIFIER_USERID => get_string('skillsoft_userid_identifier', 'skillsoft'),
	IDENTIFIER_USERNAME => get_string('skillsoft_username_identifier', 'skillsoft'),
	);
}

/**
 * Returns an array
 *
 * @return array an array of fileds to choose for sso asset action type
 */
function skillsoft_get_sso_asset_actiontype_array(){
	return array (SSO_ASSET_ACTIONTYPE_LAUNCH => get_string('skillsoft_sso_actiontype_launch', 'skillsoft'),
	SSO_ASSET_ACTIONTYPE_SUMMARY => get_string('skillsoft_sso_actiontype_summary', 'skillsoft'),
	);
}

/**
 * Creates a new sessionid key.
 * @param int $userid
 * @param int $skillsoftid
 * @return string access key value
 */
function skillsoft_create_sessionid($userid, $skillsoftid) {
    $key = new object();
    $key->skillsoftid      = $skillsoftid;
    $key->userid        = $userid;
    $key->timecreated   = time();

    $key->sessionid = md5($skillsoftid.'_'.$userid.'_'.$key->timecreated.random_string(40)); // something long and unique
    while (record_exists('skillsoft_session_track', 'sessionid', $key->sessionid)) {
        // must be unique
        $key->sessionid     = md5($skillsoftid.'_'.$userid.'_'.$key->timecreated.random_string(40));
    }

    if (!insert_record('skillsoft_session_track', $key)) {
        error('Can not insert new sessionid');
    }

    return $key->sessionid;
}

/**
 * Checks a sessionid key.
 * @param string $sessionid the skillsoft session_id
 * @return object $key
 */

function skillsoft_check_sessionid($sessionid) {
    $keyvalue = $sessionid;

    $key = get_record('skillsoft_session_track', 'sessionid', $keyvalue);

    return $key;
}

/**
 * Given an skillsoft object this will return
 * the HTML snippet for displaying the Launch Button
 * or output the HTML based on value of $return
 * @param object $skillsoft
 * @param boolean $return
 * @return string $output or null
 */
function skillsoft_view_display($skillsoft, $user, $return=false) {
	global $CFG;
	if (stripos($skillsoft->launch,'?') !== false) {
		$connector = '&';
	} else {
		$connector = '?';
	}

	/* We need logic here that if SSO url defined we use this */
	if (!$CFG->skillsoft_usesso) {
		//skillsoft_ssourl is not defined so do AICC
		$newkey = skillsoft_create_sessionid($user->id, $skillsoft->id);
		$launcher = $skillsoft->launch.$connector.'aicc_sid='.$newkey.'&aicc_url='.$CFG->wwwroot.'/mod/skillsoft/aicchandler.php';
		$options = "'width=800,height=600'";
	} else {
		//we have skillsoft_ssourl so we replace {0} with $skillsoft->id
		//$launcher = sprintf($CFG->skillsoft_ssourl,$skillsoft->assetid);
		$launcher = sprintf($CFG->skillsoft_ssourl,$skillsoft->id);
		$options = "''";
	}
	//Should look at making this call a JavaScript, that we include in the page
	$element = "<input type=\"button\" value=\"". get_string('skillsoft_enter','skillsoft') ."\" onclick=\"return openAICCWindow('$launcher', 'courseWindow',$options, false);\" />";

	if ($return) {
		return $element;
	} else {
		echo $element;
	}
}

/**
 * Insert values into the skillsoft_au_track table
 *
 * @param $userid
 * @param $skillsoftid
 * @param $attempt
 * @param $element
 * @param $value
 * @return bool true if succesful
 */
function skillsoft_insert_track($userid,$skillsoftid,$attempt,$element,$value) {
	$id = null;

	$attempt = 1;

	if ($track = get_record_select('skillsoft_au_track',"userid='$userid' AND skillsoftid='$skillsoftid' AND attempt='$attempt' AND element='$element'")) {
		$track->value = $value;
		$track->timemodified = time();
		$id = update_record('skillsoft_au_track',$track);
	} else {
		$track->userid = $userid;
		$track->skillsoftid = $skillsoftid;
		$track->attempt = $attempt;
		$track->element = $element;
		$track->value = addslashes($value);
		$track->timemodified = time();
		$id = insert_record('skillsoft_au_track',$track);
	}

	//if we have a best score OR we have passed/completed status then update the gradebook
    if ( strstr($element, ']bestscore') ||
     	(strstr($element,']lesson_status') && (substr($track->value,0,1) == 'c' || substr($track->value,0,1) == 'p'))
     	) {
        $skillsoft = get_record('skillsoft', 'id', $skillsoftid);
        include_once('lib.php');
        skillsoft_update_grades($skillsoft, $userid);
    }
	//print_object($track);
	return $id;
}

/**
 * setFirstAccessDate
 *
 * @param $userid
 * @param $skillsoftid
 * @param $attempt
 * @param $time
 * @return bool true if succesful
 */
function skillsoft_setFirstAccessDate($userid,$skillsoftid,$attempt,$time) {
	$id = null;
	$attempt = 1;
	if ($track = get_record_select('skillsoft_au_track',"userid='$userid' AND skillsoftid='$skillsoftid' AND attempt='$attempt' AND element='[SUMMARY]firstaccess'")) {
		//We have value so do nothing
	} else {
		$id = skillsoft_insert_track($userid, $skillsoftid, $attempt, '[SUMMARY]firstaccess', $time);
	}
	return $id;
}

/**
 * setLastAccessDate
 *
 * @param $userid
 * @param $skillsoftid
 * @param $attempt
 * @param $time
 * @return bool true if succesful
 */
function skillsoft_setLastAccessDate($userid,$skillsoftid,$attempt,$time) {
	$id = null;
	$attempt = 1;
	$id = skillsoft_insert_track($userid, $skillsoftid, $attempt, '[SUMMARY]lastaccess', $time);
	return $id;
}

/**
 * setCompletedDate
 *
 * @param $userid
 * @param $skillsoftid
 * @param $attempt
 * @param $time
 * @return bool true if succesful
 */
function skillsoft_setCompletedDate($userid,$skillsoftid,$attempt,$time) {
	$id = null;
	$attempt = 1;
	if ($track = get_record_select('skillsoft_au_track',"userid='$userid' AND skillsoftid='$skillsoftid' AND attempt='$attempt' AND element='[SUMMARY]completed'")) {
		//We have value so do nothing
	} else {
		$id = skillsoft_insert_track($userid, $skillsoftid, $attempt, '[SUMMARY]completed', $time);
	}
	return $id;
}


/**
 * setAccessCount
 *
 * @param $userid
 * @param $skillsoftid
 * @param $attempt
 * @return bool true if succesful
 */
function skillsoft_setAccessCount($userid,$skillsoftid,$attempt,$value=0) {
	$id = null;
	$attempt = 1;

	if ($value == 0 ) {
		if ($track = get_record_select('skillsoft_au_track',"userid='$userid' AND skillsoftid='$skillsoftid' AND attempt='$attempt' AND element='[SUMMARY]accesscount'")) {
			//We have value so increment it
			$accesscount = $track->value;
			$accesscount++;
			$id = skillsoft_insert_track($userid, $skillsoftid, $attempt, '[SUMMARY]accesscount', $accesscount);
		} else {
			$id = skillsoft_insert_track($userid, $skillsoftid, $attempt, '[SUMMARY]accesscount', 1);
		}
	} else {
		$id = skillsoft_insert_track($userid, $skillsoftid, $attempt, '[SUMMARY]accesscount', $value);
	}
	return $id;
}


/**
 * setFirstScore
 *
 * @param $userid
 * @param $skillsoftid
 * @param $attempt
 * @param $score
 * @return bool true if succesful
 */
function skillsoft_setFirstScore($userid,$skillsoftid,$attempt,$score) {
	$id = null;
	$attempt = 1;
	if ($score != 0) {
		if ($track = get_record_select('skillsoft_au_track',"userid='$userid' AND skillsoftid='$skillsoftid' AND attempt='$attempt' AND element='[SUMMARY]firstscore'")) {
			//We have value so do nothing
		} else {
			$id = skillsoft_insert_track($userid, $skillsoftid, $attempt, '[SUMMARY]firstscore', $score);
		}
	}
	return $id;
}

/**
 * setCurrentScore
 *
 * @param $userid
 * @param $skillsoftid
 * @param $attempt
 * @param $score
 * @return bool true if succesful
 */
function skillsoft_setCurrentScore($userid,$skillsoftid,$attempt,$score) {
	$id = null;
	$attempt = 1;
	if ($score != 0) {
		$id = skillsoft_insert_track($userid, $skillsoftid, $attempt, '[SUMMARY]currentscore', $score);
	}
	return $id;
}

/**
 * setBestScore
 *
 * @param $userid
 * @param $skillsoftid
 * @param $attempt
 * @param $score
 * @return bool true if succesful
 */
function skillsoft_setBestScore($userid,$skillsoftid,$attempt,$score) {
	$id = null;
	$attempt = 1;
	if ($score != 0) {
		if ($track = get_record_select('skillsoft_au_track',"userid='$userid' AND skillsoftid='$skillsoftid' AND attempt='$attempt' AND element='[SUMMARY]bestscore'")) {
			//We this score is higher
			$currentscore =  $track->value;
			if ($score > $currentscore) {
				$id = skillsoft_insert_track($userid, $skillsoftid, $attempt, '[SUMMARY]bestscore', $score);
			}
		} else {
			$id = skillsoft_insert_track($userid, $skillsoftid, $attempt, '[SUMMARY]bestscore', $score);
		}
	}
	return $id;
}

/**
 * @param $skillsoftid
 * @param $userid
 * @param $attempt
 * @return object representing all values for user and skillsoft activity in skillsoft_au_track
 */
function skillsoft_get_tracks($skillsoftid,$userid,$attempt='') {
	/// Gets all tracks of specified sco and user
	global $CFG;

	$attempt = 1;

	$attemptsql = ' AND attempt=' . $attempt;
	if ($tracks = get_records_select('skillsoft_au_track',"userid=$userid AND skillsoftid=$skillsoftid".$attemptsql,'element ASC')) {
		$usertrack->userid = $userid;
		$usertrack->skillsoftid = $skillsoftid;
		$usertrack->score_raw = '';
		$usertrack->status = '';
		$usertrack->total_time = '00:00:00';
		$usertrack->session_time = '00:00:00';
		$usertrack->timemodified = 0;
		foreach ($tracks as $track) {
			$element = $track->element;
			$usertrack->{$element} = $track->value;
			if (isset($track->timemodified) && ($track->timemodified > $usertrack->timemodified)) {
				$usertrack->timemodified = $track->timemodified;
			}
		}
		if (is_array($usertrack)) {
			ksort($usertrack);
		}
		return $usertrack;
	} else {
		return false;
	}
}



/**
 * @param object $skillsoft
 * @param int $userid
 * @param int $attempt
 * @param bool $time
 * @return object
 */
function skillsoft_grade_user($skillsoft, $userid, $attempt=1, $time=false) {
    $result = new stdClass();
   	$result->score = 0;
    $result->time = 0;

	if ($userdata = skillsoft_get_tracks($skillsoft->id, $userid, $attempt)) {
		if ($time) {
	        $result->score = $userdata->{'[SUMMARY]bestscore'};
	        $result->time = $userdata->timemodified;
	    } else {
	        $result = $userdata->{'[SUMMARY]bestscore'};
	    }
	}
    return $result;
}


/*************************************************************
 * ODC Functions
 */

/**
 * Insert raw tdr into the skillsoft_tdr
 *
 * @param $tdr
 * @return bool true if succesful
 */
function skillsoft_insert_tdr($rawtdr) {
	global $CFG;
	
	//We get a raw SkillSoft TDR which we need to manipluate to fit into
	//Moodle database limits

	$tdr = new stdClass();
	//Set TDRID
	$tdr->tdrid = $rawtdr->id;

	//Convert TimeStamp
	sscanf($rawtdr->timestamp,"%u-%u-%uT%u:%u:%uZ",$year,$month,$day,$hour,$min,$sec);
	$tdr->timestamp = mktime($hour,$min,$sec,$month,$day,$year);

	//20110114-Use the new skilsoft_getusername_from_loginname() function
	//This allows us to centralise the "translation" from SkillPort Username
	//to Moodle USERID

	$tdr->userid = skilsoft_getusername_from_loginname($rawtdr->userid);

	$tdr->username = $rawtdr->userid;
	
	$tdr->assetid = $rawtdr->assetid;

	$tdr->reset = $rawtdr->reset;

	//Addslashes
	$tdr->format = addslashes($rawtdr->format);
	$tdr->data = addslashes($rawtdr->data);
	$tdr->context = addslashes($rawtdr->context);

	if ($updatetdr = get_record_select('skillsoft_tdr',"tdrid='$tdr->tdrid'")) {
		$id = update_record('skillsoft_tdr',$tdr);
	} else {
		$id = insert_record('skillsoft_tdr',$tdr);
	}
	return $id;
}



/**
 * Processes all the TDRs in the datbase updating skillsoft_au_track and gradebook
 *
 * @param $trace false default, flag to indicate if mtrace messages should be sent
 * @return unknown_type
 */
function skillsoft_process_received_tdrs($trace=false) {
	global $CFG;
	if ($trace) {
		mtrace(get_string('skillsoft_odcprocessinginit','skillsoft'));
	}

	if ($unmatchedtdrs = get_records_select('skillsoft_tdr','userid=0','tdrid ASC')) {
		foreach ($unmatchedtdrs as $tdr) {
			$tdr->userid = skilsoft_getusername_from_loginname($tdr->username);
			if ($tdr->userid != 0)
			{
				$id = update_record('skillsoft_tdr',$tdr);
			}
		}
	}


	//Select all the unprocessed TDR's
	//We do it this way so that if we create a new Moodle SkillSoft activity for an asset we
	//have TDR's for already we can "catch up"
	$sql  = "SELECT t.id as id, s.id AS skillsoftid, u.id AS userid, t.tdrid, t.timestamp, t.reset, t.format, t.data, t.context, t.processed ";
	$sql .= "FROM {$CFG->prefix}skillsoft_tdr t INNER JOIN {$CFG->prefix}user u ON u.id = t.userid INNER JOIN {$CFG->prefix}skillsoft s ON t.assetid = s.assetid ";
	$sql .= "WHERE t.processed=0 ";
	$sql .= "ORDER BY s.id,u.id,t.tdrid ";

	$attempt=1;
	$lasttdr = new stdClass();
	$lasttdr->skillsoftid = NULL;
	$lasttdr->userid = NULL;


	if ($rs = get_recordset_sql($sql)) {
		while ($processedtdr = rs_fetch_next_record($rs)) {
			if ($trace) {
				mtrace(get_string('skillsoft_odcprocessretrievedtdr','skillsoft',$processedtdr));
			}
			if ($processedtdr->skillsoftid != $lasttdr->skillsoftid || $processedtdr->userid != $lasttdr->userid) {
				$skillsoft = get_record('skillsoft','id',$processedtdr->skillsoftid);
				$user = get_record('user','id',$processedtdr->userid);
				$handler = new aicchandler($user,$skillsoft,$attempt);
			}

			//Process the TDR as AICC Data
			$handler->processtdr($processedtdr);
			$processedtdr->processed = 1;
			$id = update_record('skillsoft_tdr',$processedtdr);
			$lasttdr = $processedtdr;
		}
		rs_close($rs);
	}
	if ($trace) {
		mtrace(get_string('skillsoft_odcprocessingend','skillsoft'));
	}
}

/**
 * This function will convert numeric byte to KB, MB etc
 *
 * @param int $bytes - The numeric bytes
 * @return string Formatted String representation
 */
function byte_convert($bytes)
{
	$symbol = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	$exp = 0;
	$converted_value = 0;
	if( $bytes > 0 )
	{
		$exp = floor( log($bytes)/log(1024) );
		$converted_value = ( $bytes/pow(1024,floor($exp)) );
	}
	return sprintf( '%.2f '.$symbol[$exp], $converted_value );
}

/**
 * This function will use CURL to download a file
 *
 * @param string $url - The URL we want to download
 * @param string $folder - The folder where we will save it. DEFAULT = temp
 * @param bool $trace - Do we output tracing info.
 * @return string localpath or NULL on error
 */
function skillsoft_download_report($url, $folder=NULL, $trace=false) {
	global $CFG;

	$basefolder = str_replace('\\','/', $CFG->dataroot);


	if ($trace) {
		mtrace(get_string('skillsoft_reportdownloadstart', 'skillsoft',$url));
	}

	//skillsoft_reportdownloadstart

	if ($folder==NULL) {
		$folder='temp/reports';
	}

	/// Create temp directory if necesary
	if (!make_upload_directory($folder, false)) {
		//Couldn't create temp folder
		if ($trace) {
			mtrace(get_string('skillsoft_reportdownloadcreatedirectoryfailed', 'skillsoft', $basefolder.'/'.$folder));
		}
		return NULL;
	}

	$filename = basename($url);

	$fp = fopen($basefolder.'/'.$folder.'/'.$filename, 'wb');

	if (!extension_loaded('curl') or ($ch = curl_init($url)) === false) {
		//Error no CURL
		if ($trace) {
			mtrace(get_string('skillsoft_reportdownloadcurlnotavailable', 'skillsoft'));
		}
		return NULL;
	} else {
		$ch = curl_init($url);
		//Ignore SSL errors
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_FILE, $fp);

		//Setup Proxy Connection
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
		if (empty($CFG->proxyport)) {
			curl_setopt($ch, CURLOPT_PROXY, $CFG->proxyhost);
		} else {
			curl_setopt($ch, CURLOPT_PROXY, $CFG->proxyhost.':'.$CFG->proxyport);
		}

		if (!empty($CFG->proxyuser) and !empty($CFG->proxypassword)) {
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $CFG->proxyuser.':'.$CFG->proxypassword);
			if (defined('CURLOPT_PROXYAUTH')) {
				// any proxy authentication if PHP 5.1
				curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC | CURLAUTH_NTLM);
			}
		}
		curl_exec($ch);

		// Check if any error occured
		if(!curl_errno($ch))
		{
			$downloadresult = new object();
			$downloadresult->bytes = byte_convert(curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD));
			$downloadresult->total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
			$downloadresult->filepath = $basefolder.'/'.$folder.'/'.$filename;

			if ($trace) {
				mtrace(get_string('skillsoft_reportdownloadresult', 'skillsoft' , $downloadresult));
			}
			fclose($fp);
			return $downloadresult->filepath;
		} else {
			if ($trace) {
				mtrace(get_string('skillsoft_reportdownloaderror', 'skillsoft' , curl_error($ch)));
			}
			fclose($fp);
			return NULL;
		}
	}

}

/**
 * Insert values into the skillsoft_report_track table
 *
 * @return bool true if succesful
 */
function skillsoft_insert_report($handle,$startdate='',$enddate='') {
	$id = null;

	$report->handle = $handle;
	$report->startdate = $startdate;
	$report->enddate = $enddate;
	$report->timerequested = time();
	$id = insert_record('skillsoft_report_track',$report);
	return $id;
}

/**
 * Record report ready
 *
 * @return bool true if succesful
 */
function skillsoft_update_report_ready($handle,$url) {
	$id = null;

	if ($report = get_record_select('skillsoft_report_track',"handle='$handle'")) {
		$report->url = $url;
		$id = update_record('skillsoft_report_track',$report);
	}
	return $id;
}

/**
 * Record report downloaded
 *
 * @return bool true if succesful
 */
function skillsoft_update_report_downloaded($handle,$localpath) {
	$id = null;

	if ($report = get_record_select('skillsoft_report_track',"handle='$handle'")) {
		$report->localpath = addslashes($localpath);
		$report->downloaded = true;
		$report->timedownloaded = time();
		$id = update_record('skillsoft_report_track',$report);
	}
	return $id;
}

/**
 * Record report downloaded
 *
 * @return bool true if succesful
 */
function skillsoft_update_report_processed($handle) {
	$id = null;

	if ($report = get_record_select('skillsoft_report_track',"handle='$handle'")) {
		$report->processed = true;
		$report->timeprocessed = time();
		$id = update_record('skillsoft_report_track',$report);
	}
	return $id;
}

/**
 * This function is is the key to importing usage data from SkillPort
 * It will attempt to convert the SkillPort username to the equivalent
 * Moodle $user->id, if it fails the is is returned as 0
 *
 * @param $skillport_loginname
 * @return $moodle_userid
 */
function skilsoft_getusername_from_loginname($skillport_loginname) {
	global $CFG;

	//If the PREFIX is configured we strip this from the skillport loginname
	//Before we attempt to match it
	if ($CFG->skillsoft_accountprefix != '') {
		//Check we have the prefix in the username
		$pos = stripos($skillport_loginname, $CFG->skillsoft_accountprefix);
		if ($pos !== false && $pos == 0) {
			$skillport_loginname = substr($skillport_loginname,strlen($CFG->skillsoft_accountprefix));
		}
	}

	//We check if we are using the IDENTIFIER_USERID that the
	//SkillPort loginname is numeric before we attempt to match it
	if ($CFG->skillsoft_useridentifier == IDENTIFIER_USERID) {
		if (!is_numeric($skillport_loginname) ) {
			return 0;
			break;
		}
	}

	//Now we attempt to get the Moodle userid by looking up the user
	//We return the Moodle USERID or 0 if no match
	if ($user = get_record('user',$CFG->skillsoft_useridentifier,$skillport_loginname)) {
		return $user->id;
	} else {
		return 0;
	}
}


/**
 * Converts the CSV data from a custom report which is in array format into
 * an object for easy insert into database
 *
 * It also performs any necessary conversions, such as dates to timestamps
 *
 * @param $arraykey		Array of key names
 * @param $arrayvalue	Array of the values
 * @return $object
 */
function ConvertCSVRowToReportResults($arraykey, $arrayvalue) {
	$object = new stdClass();
	//Need to consider if using column position is better rather than heading
	$count = count($arraykey);
	for ($i = 0; $i < $count; $i++) {
		$cleankey = trim(strtolower($arraykey[$i]));

		//Do we have a value or is it null
		if ($arrayvalue[$i])
		{
			$hour=0;
			$min=0;
			$sec=0;
			$month=0;
			$day=0;
			$year=0;

			switch ($cleankey) {
				case 'courseid';
				$object->assetid = $arrayvalue[$i];
				break;
				case 'firstaccessdate';
				case 'lastaccessdate';
				//Convert TimeStamp 2010-01-29 18:09:18
				sscanf($arrayvalue[$i],"%u-%u-%u %u:%u:%u",$year,$month,$day,$hour,$min,$sec);
				$object->$cleankey = mktime($hour,$min,$sec,$month,$day,$year);
				break;
				case 'completiondate';
				//Convert TimeStamp 2010-01-29 18:09:18
				sscanf($arrayvalue[$i],"%u-%u-%u %u:%u:%u",$year,$month,$day,$hour,$min,$sec);
				$object->completeddate = mktime($hour,$min,$sec,$month,$day,$year);
				break;
				case 'timesaccessed';
				$object->accesscount = $arrayvalue[$i];
				break;
				case 'overallpreassess';
				$object->firstscore = $arrayvalue[$i];
				break;
				case 'overallhigh';
				$object->bestscore = $arrayvalue[$i];
				break;
				case 'overallcurrent';
				$object->currentscore = $arrayvalue[$i];
				break;
				case 'coursestatus';
				switch (strtolower($arrayvalue[$i]))
				{
					case 'started';
					$object->lessonstatus = 'incomplete';
					break;
					case 'completed';
					$object->lessonstatus = 'completed';
					break;
				}
				break;
				default:
					//Here we apply custom logic
					$object->$cleankey = addslashes($arrayvalue[$i]);
			}
		}
	}
	return $object;
}

/**
 * Insert report_results into the skillsoft_report_results
 *
 * @param $report_results
 * @return bool true if succesful
 */
function skillsoft_insert_report_results($report_results) {
	global $CFG;
	$id = null;

	//Need to determine the moodle userid based on loginname
	$report_results->userid = skilsoft_getusername_from_loginname($report_results->loginname);

	if ($update_results = get_record_select('skillsoft_report_results',"loginname='$report_results->loginname' and assetid='$report_results->assetid'")) {
		$report_results->id = $update_results->id;
		$report_results->processed = 0;
		$id = update_record('skillsoft_report_results',$report_results);
	} else {
		$id = insert_record('skillsoft_report_results',$report_results);
	}
	return $id;
}

/**
 * Processes all the entries from the custom report in the datbase updating skillsoft_au_track and gradebook
 *
 * @param $trace false default, flag to indicate if mtrace messages should be sent
 * @return unknown_type
 */
function skillsoft_process_received_customreport($trace=false) {
	global $CFG;

	if ($trace) {
		mtrace(get_string('skillsoft_reportprocessinginit','skillsoft'));
	}

	if ($unmatchedreportresults = get_records_select('skillsoft_report_results','userid=0','id ASC')) {
		foreach ($unmatchedreportresults as $reportresults) {
			$reportresults->userid = skilsoft_getusername_from_loginname($reportresults->loginname);
			if ($reportresults->userid != 0)
			{
				$id = update_record('skillsoft_report_results',$reportresults);
			}
		}
	}

	//Select all the unprocessed Custom Report Results's
	//We do it this way so that if we create a new Moodle SkillSoft activity for an asset we
	//have TDR's for already we can "catch up"
	$sql  = "SELECT t.id as id, s.id AS skillsoftid, u.id AS userid, t.firstaccessdate, t.lastaccessdate, t.completeddate, t.firstscore, t.currentscore, t.bestscore, t.lessonstatus, t.duration, t.accesscount, t.processed ";
	$sql .= "FROM {$CFG->prefix}skillsoft_report_results t ";
	$sql .= "INNER JOIN {$CFG->prefix}user u ON u.id = t.userid ";
	$sql .= "INNER JOIN {$CFG->prefix}skillsoft s ON t.assetid = s.assetid ";
	$sql .= "WHERE t.processed=0 ";
	$sql .= "ORDER BY s.id,u.id,t.id ";

	$attempt=1;
	$lastreportresults = new stdClass();
	$lastreportresults->skillsoftid = NULL;
	$lastreportresults->userid = NULL;

	if ($rs = get_recordset_sql($sql)) {
		while ($reportresults = rs_fetch_next_record($rs)) {
			if ($trace) {
				mtrace(get_string('skillsoft_reportprocessretrievedresults','skillsoft',$reportresults));
			}

			if ($reportresults->skillsoftid != $lastreportresults->skillsoftid || $reportresults->userid != $lastreportresults->userid) {
				$skillsoft = get_record('skillsoft','id',$reportresults->skillsoftid);
				$user = get_record('user','id',$reportresults->userid);
				$handler = new aicchandler($user,$skillsoft,$attempt);
			}

			//Process the ReportResults as AICC Data
			$handler->processreportresults($reportresults);
			$reportresults->processed = 1;
			$id = update_record('skillsoft_report_results',$reportresults);
			$lastreportresults = $reportresults;
		}
		rs_close($rs);
	}
	if ($trace) {
		mtrace(get_string('skillsoft_reportprocessingend','skillsoft'));
	}
}


?>

