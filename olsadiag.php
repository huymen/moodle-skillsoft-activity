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
 * Retrieve the Asset metadata from the SkillSoft OLSA server
 * and update the create/edit form using Javascript.
 *
 * @package   mod-skillsoft
 * @author    Martin Holden
 * @copyright 2009 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/olsalib.php');

global $CFG;

//Report all PHP errors
error_reporting(E_ALL);

$br = '<br/>';
$pass ='<font style="color: #008000; font-weight: bold;">Test PASSED<br />';
$fail ='<font style="color: #800000; font-weight: bold;">Test FAILED<br />';
$fontend ='</font>';

$continue = true;

echo ('<h1>Perfoming Basic OLSA Diagnostics</h1>'.$br);
echo ('<p>This page will perform some basic tests to confirm Moodle Module is able to access SkillSoft OLSA Servers</p>');


if ($continue) {
	echo ('<h2>Test 1 of 5: Checking SOAP Extension is loaded</h2>'.$br);
	if (!extension_loaded('soap')) {
		echo($fail);
		echo('SOAP Extension is not enabled in PHP.INI. To enable please see <a target="_blank" href="http://www.php.net/manual/en/book.soap.php">http://www.php.net/manual/en/book.soap.php</a>'.$br);
		echo('DIAGNOSTICS HALTED'.$br);
		echo($fontend);
		$continue = false;
	} else {
		echo($pass);
		echo('SOAP Extension is loaded.'.$br);
		echo($fontend);
	}
	echo ('<hr/>'.$br);
	echo ($br);
}

if ($continue) {
	echo ('<h2>Test 2 of 5: Checking OLSA Settings are configured</h2>'.$br);
	if (!isolsaconfigurationset()) {
		echo($fail);
		echo('OLSA Settings are Not Configured. Please ensure you enter the OLSA settings in the module configuration settings'.$br);
		echo('DIAGNOSTICS HALTED');
		echo($fontend);
		$continue = false;
	} else {
		echo($pass);
		echo('OLSA Settings Configured.'.$br);
		echo($fontend);
		//Set local OLSA Variables
		$endpoint = $CFG->skillsoft_olsaendpoint;
		$customerId = $CFG->skillsoft_olsacustomerid;
		$sharedsecret = $CFG->skillsoft_olsasharedsecret;
	}
	echo ('<hr/>'.$br);
	echo ($br);
}

if ($continue) {
	echo ('<h2>Test 3 of 5: Is OLSA EndPoint Accessible</h2>'.$br);
	$http_headers = @get_headers($endpoint.'?WSDL');

	//Check for HTTP 200 response
	if (!preg_match("|200|", $http_headers[0])) {
		echo($fail);
		echo('OLSA WSDL Can Not Be Accessed'.$br);
		echo('Current Value: <a target="_blank" href ="'.$endpoint.'?WSDL">'.$endpoint.'</a>'.$br);
		if ($http_headers == false) {
			if (!extension_loaded('openssl') && stripos($endpoint, 'https') === 0) {
				echo('OLSA EndPoint uses SSL'.$br);
				echo('OPENSSL Extension is not enabled in PHP.INI. To enable please see <a target="_blank" href="http://uk.php.net/manual/en/book.openssl.php">http://uk.php.net/manual/en/book.openssl.php</a>'.$br);
			} else {
				echo('No Headers Returned, this typically indicates a networking or DNS resolution issue. Please confirm connectivity and the correct URL is specified.'.$br);
			}
		} else {
			echo('Please ensure you entered the correct URL'.$br.$br);
			echo('Headers Returned'.$br);
			foreach ($http_headers as $header) {
				echo('&nbsp;&nbsp;'.$header.$br);
			}
		}
		echo('DIAGNOSTICS HALTED'.$br);
		echo($fontend);
		$continue = false;
	}
	else {
		echo($pass);
		echo('OLSA WSDL Can Be Opened.'.$br);
		echo($fontend);
	}
	echo ('<hr/>'.$br);
	echo ($br);
}

if ($continue) {
	echo ('<h2>Test 4 of 5: Create OLSA SOAP Client</h2>'.$br);
	
	//Specify the SOAP Client Options
	$options = array(
		"trace"      => 1,
		"exceptions" => true,
		"soap_version"   => SOAP_1_2,
		"cache_wsdl" => WSDL_CACHE_BOTH,
		"encoding"=> "UTF-8"
	);
	try {
		//Create a new instance of the OLSA Soap Client
		$client = new olsa_soapclient($endpoint.'?WSDL',$options);
		//Create the USERNAMETOKEN
		$client->__setUsernameToken($customerId,$sharedsecret);
		echo($pass);
		echo('OLSA SOAP Client Created'.$br);
		echo($fontend);
	} catch (Exception $e) { 
		echo($fail);
		echo('Exception while creating OLSA SOAP Client'.$br);
		echo('Exception Details'.$br);
        echo($e->getMessage());
        echo('DIAGNOSTICS HALTED');
        echo($fontend);
		$continue = false;
	}
	echo ('<hr/>'.$br);
	echo ($br);
}


if ($continue) {
	echo ('<h2>Test 5 of 5: Checking OLSA Credentials</h2>'.$br);
	$pollresponse = UTIL_PollForReport('0');
	if ($pollresponse->errormessage == get_string('skillsoft_olsassoapauthentication','skillsoft')) {
		echo($fail);
		echo('OLSA Credentials are incorrect. Please ensure you entered the correct values.'.$br);
        echo('DIAGNOSTICS HALTED');
        echo($fontend);
		$continue = false;
	} else {
		echo($pass);
        echo('OLSA Credentials are correct.'.$br);
        echo($fontend);
	}
	echo ('<hr/>'.$br);
	echo ($br);
}

	

?>
