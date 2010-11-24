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
require_once(dirname(__FILE__).'/aiccmodel.php');
require_once(dirname(__FILE__).'/aicclib.php');

defined('MOODLE_INTERNAL') || die();

/// Constants and settings for module skillsoft
define('TRACK_TO_LMS', '0');
define('TRACK_TO_OLSA', '1');

/// Constants and settings for module skillsoft
define('IDENTIFIER_USERID', 'id');
define('IDENTIFIER_USERNAME', 'username');

/**
 * Returns an array of the array of what grade options
 *
 * @return array an array of OLSA Tracking Options
 */
function skillsoft_get_tracking_method_array(){
    return array (TRACK_TO_LMS => get_string('skillsoft_tracktolms', 'skillsoft'),
                  TRACK_TO_OLSA => get_string('skillsoft_tracktoolsa', 'skillsoft'),
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
			
	if (strtolower($skillsoft->assetid) != 'sso') {

	
		$newkey = skillsoft_create_sessionid($user->id, $skillsoft->id);
	
		$launcher = $skillsoft->launch.$connector.'aicc_sid='.$newkey.'&aicc_url='.$CFG->wwwroot.'/mod/skillsoft/aicchandler.php';
	
		//Should look at making this call a JavaScript, that we include in the page
		$element = "<input type=\"button\" value=\"". get_string('skillsoft_enter','skillsoft') ."\" onclick=\"return openAICCWindow('$launcher', 'courseWindow','width=800,height=600', false);\" />";
	} else {
		$launcher = $skillsoft->launch.$connector.'a='.$skillsoft->id;
		//Should look at making this call a JavaScript, that we include in the page
		$element = "<input type=\"button\" value=\"". get_string('skillsoft_enter','skillsoft') ."\" onclick=\"return openAICCWindow('$launcher', 'ssoWindow','', true);\" />";
	}
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

	//We need to get the Moodle USERID based on the $tdr->userid
	//Now if we are already using id, avoid database roundtrip
	
	if ($CFG->skillsoft_useridentifier == IDENTIFIER_USERID) {
		$tdr->userid = $rawtdr->userid;
	} else {
		//Get userid from username if we fail set to 0
		if ($user = get_record('user',$CFG->skillsoft_useridentifier,$rawtdr->userid)) {
			$tdr->userid = $user->id;
		} else {
			$tdr->userid = 0;
		}
	}
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
	
	//Update the skillsoft_tdr table updating any userid values with correct values using $CFG->skillsoft_useridentifier match
	$sqlupdate = "UPDATE {$CFG->prefix}skillsoft_tdr t ";
	$sqlupdate .="SET t.userid = ";
	$sqlupdate .="(SELECT id FROM {$CFG->prefix}user WHERE {$CFG->skillsoft_useridentifier} = t.username) ";
	$sqlupdate .="WHERE t.processed = 0 ";
	$sqlupdate .="AND t.userid = 0 ";
	$sqlupdate .="AND EXISTS (SELECT id FROM {$CFG->prefix}user WHERE {$CFG->skillsoft_useridentifier} = t.username)";
	$result = execute_sql($sqlupdate,false);

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


?>

