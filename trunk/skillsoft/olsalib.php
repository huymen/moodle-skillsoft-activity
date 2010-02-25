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
 * OLSA Library Functions
 *
 * @package   mod-skillsoft
 * @author    Martin Holden
 * @copyright 2009 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extendes the PHP SOAPCLIENT to incorporate the USERNAMETOKEN with PasswordDigest WS-Security standard
 * http://www.oasis-open.org/committees/download.php/16782/wss-v1.1-spec-os-UsernameTokenProfile.pdf
 *
 *
 * @author	  Martin Holden
 * @copyright 2009 Martin Holden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class olsa_soapclient extends SoapClient{

	/* ---------------------------------------------------------------------------------------------- */
	/* Constants and Private Variables                                                                */

	//Constants for use in code.
	const WSSE_NS  = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
	const WSSE_PFX = 'wsse';
	const WSU_NS   = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
	const WSU_PFX  = 'wsu';
	const PASSWORD_TYPE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest';

	//Private variables
	private $username;
	private $password;

	/* ---------------------------------------------------------------------------------------------- */
	/* Helper Functions                                                                               */

	/* Generate a GUID */
	private function guid(){
		mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45);// "-"
		$uuid = substr($charid, 0, 8).$hyphen
		.substr($charid, 8, 4).$hyphen
		.substr($charid,12, 4).$hyphen
		.substr($charid,16, 4).$hyphen
		.substr($charid,20,12);
		return $uuid;
	}


	private function generate_header() {

		//Get the current time
		$currentTime = time();
		//Create the ISO8601 formatted timestamp
		$timestamp=gmdate('Y-m-d\TH:i:s', $currentTime).'Z';
		//Create the expiry timestamp 5 minutes later (60*5)
		$expiretimestamp=gmdate('Y-m-d\TH:i:s', $currentTime + 300).'Z';
		//Generate the random Nonce. The use of rand() may repeat the word if the server is very loaded.
		$nonce=mt_rand();
		//Create the PasswordDigest for the usernametoken
		$passdigest=base64_encode(pack('H*',sha1(pack('H*',$nonce).pack('a*',$timestamp).pack('a*',$this->password))));

		//Build the header text
		$header='
			<wsse:Security env:mustUnderstand="1" xmlns:wsse="'.self::WSSE_NS.'" xmlns:wsu="'.self::WSU_NS.'">
				<wsu:Timestamp wsu:Id="Timestamp-'.$this->guid().'">
					<wsu:Created>'.$timestamp.'</wsu:Created>
					<wsu:Expires>'.$expiretimestamp.'</wsu:Expires>
				</wsu:Timestamp>
				<wsse:UsernameToken xmlns:wsu="'.self::WSU_NS.'">
					<wsse:Username>'.$this->username.'</wsse:Username>
					<wsse:Password Type="'.self::PASSWORD_TYPE.'">'.$passdigest.'</wsse:Password>
					<wsse:Nonce>'.base64_encode(pack('H*',$nonce)).'</wsse:Nonce>
					<wsu:Created>'.$timestamp.'</wsu:Created>
				</wsse:UsernameToken>
			</wsse:Security>
			';

		$headerSoapVar=new SoapVar($header,XSD_ANYXML); //XSD_ANYXML (or 147) is the code to add xml directly into a SoapVar. Using other codes such as SOAP_ENC, it's really difficult to set the correct namespace for the variables, so the axis server rejects the xml.
		$soapheader=new SoapHeader(self::WSSE_NS, "Security" , $headerSoapVar , true);
		return $soapheader;
	}

	/*It's necessary to call it if you want to set a different user and password*/
	public function __setUsernameToken($username,$password){
		$this->username=$username;
		$this->password=$password;
	}

	/*Overload the original method, and add the WS-Security Header */
	public function __soapCall($function_name,$arguments,$options=null,$input_headers=null,$output_headers=null){
		$result = parent::__soapCall($function_name,$arguments,$options,$this->generate_header());
		return $result;
	}

}

/**
 * Standard object for an OLSA response
 *
 * @author	  Martin Holden
 * @copyright 2009 Martin Holden
 */
class olsaresponse implements IteratorAggregate {
	private $success; //true/false
	private $errormessage; //null or the olsa error message;
	private $result; //the object

	/**
	 * @param bool $success indicates if OLSA call was successful
	 * @param string $errormessage error message or NULL
	 * @param object $result the OLSA response object
	 */
	public function __construct($success,$errormessage,$result)
	{
		$this->success = $success;
		$this->errormessage = $errormessage;
		$this->result = $result;
	}

	public function __set($var, $value) {
		$this->$var = $value;
	}

	public function __get($var) {
		return $this->$var;
	}

	// Create an iterator because private/protected vars can't
    // be seen by json_encode().
    public function getIterator() {
        $iArray['success'] = $this->success;
        $iArray['errormessage'] = $this->errormessage;
        $iArray['results'] = $this->result;
        return new ArrayIterator($iArray);
    }


}

/**
 * Format a string from OLSA so it can be output
 *
 * This function replaces any linefeeds with <br /> and
 * processes the string using addslashes
 *
 * @param string $text
 * @return string
 */
function olsadatatohtml($text) {
   return addslashes(strtr($text, array("\r\n" => '<br />', "\r" => '<br />', "\n" => '<br />')));
}

/**
 * Helper function to confirm OLSA settings configured and valid
 *
 * TO DO: Add URl check to confirm WSDL present
 * @return book
 */
function isolsaconfigurationset() {
	global $CFG;
	if (!isset($CFG->skillsoft_olsaendpoint, $CFG->skillsoft_olsacustomerid, $CFG->skillsoft_olsasharedsecret)) {
		return false;
	} else {
		//They are set BUT are they empty
		if (empty($CFG->skillsoft_olsaendpoint) || empty($CFG->skillsoft_olsacustomerid) || empty($CFG->skillsoft_olsasharedsecret)) {
			return false;
		}
	}
	return true;
}



/**
 * Retrieves the metadata for the supplied SkillSoft assetid
 *
 * @param string $assetid the SkillSoft assetid
 * @return olsasoapresponse olsasoapresponse->result is an object representing the deserialised XML response
 */
function AI_GetXmlAssetMetaData($assetid) {
	global $CFG;

	if (!isolsaconfigurationset()) {
		$response = new olsaresponse(false,get_string('skillsoft_olsasettingsmissing','skillsoft'),NULL);
	} else {

		//Set local OLSA Variables
		$endpoint = $CFG->skillsoft_olsaendpoint;
		$customerId = $CFG->skillsoft_olsacustomerid;
		$sharedsecret = $CFG->skillsoft_olsasharedsecret;


		//Specify the WSDL using the EndPoint
		$wsdlurl = $endpoint.'?WSDL';

		//Specify the SOAP Client Options
		$options = array(
			"trace"      => 0,
			"exceptions" => 0,
			"soap_version"   => SOAP_1_2,
			"cache_wsdl" => WSDL_CACHE_BOTH,
			"encoding"=> "UTF-8"
			);

			//Create a new instance of the OLSA Soap Client
			$client = new olsa_soapclient($wsdlurl,$options);

			//Create the USERNAMETOKEN
			$client->__setUsernameToken($customerId,$sharedsecret);

			//Create the Request
			$GetXmlAssetMetaDataRequest =  array(
	"customerId" => $customerId,
	"assetId" => $assetid,
			);

			//Call the WebService and stored result in $result
			$result=$client->__soapCall('AI_GetXmlAssetMetaData',array('parameters'=>$GetXmlAssetMetaDataRequest));

			if (is_soap_fault($result)) {
				if (stripos($result->getmessage(),'security token could not be authenticated or authorized')) {
					//Authentication Failure
					//print_error('olsassoapauthentication','skillsoft');
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapauthentication','skillsoft'),NULL);
				} elseif (stripos($result->getmessage(), 'does not exist.')){
					//Asset ID is invalid
					//print_error('olsassoapinvalidassetid','skillsoft','',$id);
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapinvalidassetid','skillsoft',$assetid),NULL);
				} else {
					//General SOAP Fault
					//print_error('olsassoapfault','skillsoft','',$result->getmessage());
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapfault','skillsoft',$result->getmessage()),NULL);
				}
			} else {
				$asset = $result->metadata->asset;
				$response = new olsaresponse(true,'',$asset);
			}
	}
	return $response;
}

/**
 * Retrieves the usage data for the supplied SkillSoft assetid
 * for the specified user
 *
 * @param string $userid the userid
 * @param string $assetid the SkillSoft assetid
 * @param bool $summarylevel return only summary details
 * @return olsasoapresponse olsasoapresponse->result is an object representing the deserialised XML response
 */
function UD_GetAssetResults($userid,$assetid,$summarylevel=true) {
	global $CFG;

	if (!isolsaconfigurationset()) {
		$response = new olsaresponse(false,get_string('skillsoft_olsasettingsmissing','skillsoft'),NULL);
	} else {

		//Set local OLSA Variables
		$endpoint = $CFG->skillsoft_olsaendpoint;
		$customerId = $CFG->skillsoft_olsacustomerid;
		$sharedsecret = $CFG->skillsoft_olsasharedsecret;


		//Specify the WSDL using the EndPoint
		$wsdlurl = $endpoint.'?WSDL';

		//Specify the SOAP Client Options
		$options = array(
			"trace"      => 0,
			"exceptions" => 0,
			"soap_version"   => SOAP_1_2,
			"cache_wsdl" => WSDL_CACHE_BOTH,
			"encoding"=> "UTF-8"
			);

			//Create a new instance of the OLSA Soap Client
			$client = new olsa_soapclient($wsdlurl,$options);

			//Create the USERNAMETOKEN
			$client->__setUsernameToken($customerId,$sharedsecret);

			//Create the Request
			if (empty($assetid)) {
				$GetAssetResultsRequest =  array(
					"customerId" => $customerId,
					"userName" => $userid,
					"summaryLevel" => $summarylevel,
				);
			} else {
				$GetAssetResultsRequest =  array(
					"customerId" => $customerId,
					"userName" => $userid,
					"assetId" => $assetid,
					"summaryLevel" => $summarylevel,
				);
			}
			//Call the WebService and stored result in $result
			$result=$client->__soapCall('UD_GetAssetResults',array('parameters'=>$GetAssetResultsRequest));

			if (is_soap_fault($result)) {

				if (!stripos($result->getmessage(),'security token could not be authenticated or authorized') == false) {
					//Authentication Failure
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapauthentication','skillsoft'),NULL);
				} elseif (!stripos($result->getmessage(), 'The specified course could not be found') == false){
					//Asset ID is invalid
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapinvalidassetid','skillsoft',$assetid),NULL);
				} elseif (!stripos($result->getmessage(), 'does not exist, or is not in Source Users Scope') == false){
					//User ID is invalid
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapinvaliduserid','skillsoft',$userid),NULL);
				} elseif (!stripos($result->getmessage(), 'are no results for') == false){
					//No results repond as OK with NULL object
					$response = new olsaresponse(true,'',NULL);
				} else {
					//General SOAP Fault
					//print_error('olsassoapfault','skillsoft','',$result->getmessage());
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapfault','skillsoft',$result->getmessage()),NULL);
				}
			} else {
				$results = $result->RESULTS;
				$response = new olsaresponse(true,'',$results);
			}
	}
	return $response;
}

/**
 * Initialise the OnDemandCommunications
 *
 * @return olsasoapresponse olsasoapresponse->result is a NULL object
 */
function OC_InitializeTrackingData() {
	global $CFG;

	if (!isolsaconfigurationset()) {
		$response = new olsaresponse(false,get_string('skillsoft_olsasettingsmissing','skillsoft'),NULL);
	} else {

		//Set local OLSA Variables
		$endpoint = $CFG->skillsoft_olsaendpoint;
		$customerId = $CFG->skillsoft_olsacustomerid;
		$sharedsecret = $CFG->skillsoft_olsasharedsecret;


		//Specify the WSDL using the EndPoint
		$wsdlurl = $endpoint.'?WSDL';

		//Specify the SOAP Client Options
		$options = array(
			"trace"      => 0,
			"exceptions" => 0,
			"soap_version"   => SOAP_1_2,
			"cache_wsdl" => WSDL_CACHE_BOTH,
			"encoding"=> "UTF-8"
			);

			//Create a new instance of the OLSA Soap Client
			$client = new olsa_soapclient($wsdlurl,$options);

			//Create the USERNAMETOKEN
			$client->__setUsernameToken($customerId,$sharedsecret);

			//Create the Request
			$InitializeTrackingDataRequest =  array(
				"customerId" => $customerId,
			);

				//Call the WebService and stored result in $result
			$result=$client->__soapCall('OC_InitializeTrackingData',array('parameters'=>$InitializeTrackingDataRequest));

			if (is_soap_fault($result)) {

				if (!stripos($result->getmessage(),'security token could not be authenticated or authorized') == false) {
					//Authentication Failure
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapauthentication','skillsoft'),NULL);
				} else {
					//General SOAP Fault
					//print_error('olsassoapfault','skillsoft','',$result->getmessage());
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapfault','skillsoft',$result->getmessage()),NULL);
				}
			} else {
				$response = new olsaresponse(true,'',NULL);
			}
	}
	return $response;
}

/**
 * Acknowledge the TDRs received the OnDemandCommunications
 * Only use this call after the associated TDRs have been truly processed
 * and persisted on the caller's side and if OC_GetTrackingData returned
 * a non-empty result.
 *
 * @param string $handle the ODC handle to acknowledge
 * @return olsasoapresponse olsasoapresponse->result is a NULL object
 */
function OC_AcknowledgeTrackingData($handle) {
	global $CFG;

	if (!isolsaconfigurationset()) {
		$response = new olsaresponse(false,get_string('skillsoft_olsasettingsmissing','skillsoft'),NULL);
	} else {

		//Set local OLSA Variables
		$endpoint = $CFG->skillsoft_olsaendpoint;
		$customerId = $CFG->skillsoft_olsacustomerid;
		$sharedsecret = $CFG->skillsoft_olsasharedsecret;


		//Specify the WSDL using the EndPoint
		$wsdlurl = $endpoint.'?WSDL';

		//Specify the SOAP Client Options
		$options = array(
			"trace"      => 0,
			"exceptions" => 0,
			"soap_version"   => SOAP_1_2,
			"cache_wsdl" => WSDL_CACHE_BOTH,
			"encoding"=> "UTF-8"
			);

			//Create a new instance of the OLSA Soap Client
			$client = new olsa_soapclient($wsdlurl,$options);

			//Create the USERNAMETOKEN
			$client->__setUsernameToken($customerId,$sharedsecret);

			//Create the Request
			$AcknowledgeTrackingDataRequest =  array(
				"customerId" => $customerId,
				"handle" => $handle,
			);

				//Call the WebService and stored result in $result
			$result=$client->__soapCall('OC_AcknowledgeTrackingData',array('parameters'=>$AcknowledgeTrackingDataRequest));

			if (is_soap_fault($result)) {

				if (!stripos($result->getmessage(),'security token could not be authenticated or authorized') == false) {
					//Authentication Failure
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapauthentication','skillsoft'),NULL);
				} elseif (!stripos($result->getmessage(), 'The specified course could not be found') == false){
					//TODO: Need check here for INVALID HANDLE and add appropriate Lanaguge Tag
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapinvalidassetid','skillsoft',$assetid),NULL);
				} else {
					//General SOAP Fault
					//print_error('olsassoapfault','skillsoft','',$result->getmessage());
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapfault','skillsoft',$result->getmessage()),NULL);
				}
			} else {
				$response = new olsaresponse(true,'',NULL);
			}
	}
	return $response;
}

/**
 * Retrieve the TrackingData
 *
 * @return olsasoapresponse olsasoapresponse->result is GetTrackingDataResponse object
 */
function OC_GetTrackingData() {
	global $CFG;

	if (!isolsaconfigurationset()) {
		$response = new olsaresponse(false,get_string('skillsoft_olsasettingsmissing','skillsoft'),NULL);
	} else {

		//Set local OLSA Variables
		$endpoint = $CFG->skillsoft_olsaendpoint;
		$customerId = $CFG->skillsoft_olsacustomerid;
		$sharedsecret = $CFG->skillsoft_olsasharedsecret;


		//Specify the WSDL using the EndPoint
		$wsdlurl = $endpoint.'?WSDL';

		//Specify the SOAP Client Options
		$options = array(
			"trace"      => 0,
			"exceptions" => 0,
			"soap_version"   => SOAP_1_2,
			"cache_wsdl" => WSDL_CACHE_BOTH,
			"encoding"=> "UTF-8"
			);

			//Create a new instance of the OLSA Soap Client
			$client = new olsa_soapclient($wsdlurl,$options);

			//Create the USERNAMETOKEN
			$client->__setUsernameToken($customerId,$sharedsecret);

			//Create the Request
			$GetTrackingDataRequest =  array(
				"customerId" => $customerId,
			);

				//Call the WebService and stored result in $result
			$result=$client->__soapCall('OC_GetTrackingData',array('parameters'=>$GetTrackingDataRequest));

			if (is_soap_fault($result)) {

				if (!stripos($result->getmessage(),'security token could not be authenticated or authorized') == false) {
					//Authentication Failure
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapauthentication','skillsoft'),NULL);
				} elseif (isset($result->detail->NoResultsAvailableFault)) {
					$response = new olsaresponse(false,get_string('skillsoft_odcnoresultsavailable','skillsoft'),NULL);
				} else {
					//General SOAP Fault
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapfault','skillsoft',$result->getmessage()),NULL);
				}
			} else {
				$response = new olsaresponse(true,'',$result);
			}
	}
	return $response;
}
