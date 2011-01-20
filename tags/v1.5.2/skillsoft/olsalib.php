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

/**
 * Perform a SignOn
 *
 * @param string $userName the SkillPort username
 * @param string $firstName the first name
 * @param string $lastName the last name
 * @param string $email the email
 * @param string $password the password
 * @param string $groupCode the definitive list of groups
 * @param string $actionType the action to perform
 * @param string $assetId the assetid to perform action with
 * @param bool $enable508 enable section 508 support
 * @param string $authType the type of account
 * @param string $newUserName the name to rename username to
 * @param bool $active is the account active
 * @param string $address1 optional parameter
 * @param string $address2 optional parameter
 * @param string $city  optional parameter
 * @param string $state optional parameter
 * @param string $zip optional parameter
 * @param string $country optional parameter
 * @param string $phone optional parameter
 * @param string $sex optional parameter
 * @param string $ccExpr optional parameter
 * @param string $ccNumber optional parameter
 * @param string $ccType optional parameter
 * @param string $free1 optional parameter
 * @param string $birthDate optional parameter
 * @param string $language the UI language must be one of SkillSoft supported values
 * @param string $manager the users managers skillport username (manager account must already exist and be manager level in skillport)
 * @return olsasoapresponse olsasoapresponse->result. result->olsaURL is the time/user scoped URL to redirect the user to
 */
function SO_GetMultiActionSignOnUrl(
$userName,
$firstName = '',
$lastName = '',
$email = '',
$password = '',
$groupCode = '',
$actionType = 'home',
$assetId = '',
$enable508 = false,
$authType = 'End-User',
$newUserName = '',
$active = true,
$address1 = '',
$address2 = '',
$city = '',
$state = '',
$zip = '',
$country = '',
$phone = '',
$sex = '',
$ccExpr = '',
$ccNumber = '',
$ccType = '',
$free1 = '',
$birthDate = '',
$language = '',
$manager = ''
) {
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
			"trace"      => 1,
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
			$GetMultiActionSignOnUrlRequest =  array(
				"customerId" => $customerId,
				"userName" => $userName,
				"firstName" => $firstName,
				"lastName" => $lastName,
				"email" => $email,
				"password" => $password,
				"groupCode" => $groupCode,
				"actionType" => $actionType,
				"assetId" => $assetId,
				"enable508" => $enable508,
				"authType" => $authType,
				"newUserName" => $newUserName,
				"active" => $active,
 			    "address1" => $address1,
				"address2" => $address2,
				"city" => $city,
				"state" => $state,
				"zip" => $zip,
				"country" => $country,
				"phone" => $phone,
				"sex" => $sex,
				"ccExpr" => $ccExpr,
				"ccNumber" => $ccNumber,
				"ccType" => $ccType,
				"free1" => $free1,
//				"birthDate" => $birthDate,
				"language" => $language,
				"manager" => $manager,
			);

			if (!empty($birthDate)){
				if ($birthTimestamp = strtotime($birthDate)) {
					$GetMultiActionSignOnUrlRequest["birthDate"] = date('Y-m-d', $birthTimestamp);
				}
			}
			
			
			//Call the WebService and stored result in $result
			$result=$client->__soapCall('SO_GetMultiActionSignOnUrl',array('parameters'=>$GetMultiActionSignOnUrlRequest));

			if (is_soap_fault($result)) {
				if (!stripos($result->getmessage(),'security token could not be authenticated or authorized') == false) {
					//Authentication Failure
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapauthentication','skillsoft'),NULL);
				}
				elseif (!stripos($result->getmessage(), "the property '_pathid_' or '_orgcode_' must be specified") == false)
				{
					//Captures if the USER does not exist and we have NOT SENT the _req.groupCode value.
					//This is a good methodology when the SSO process will not be aware of all groups a
					//user belongs to. This way capturing this exception means that we only need to send
					//an orgcode when we know we have to create the user.
					//This avoids the issue of overwriting existing group membership for user already in
					//SkillPort.
					//You would capture this exception and resubmit the request now including the "default"
					//orgcode.
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapfault','skillsoft',$result->getmessage()),NULL);
				}
				elseif (!stripos($result->getmessage(), "invalid new username") == false)
				{
					//The username specified is not valid
					//Supported Characters: abcdefghijklmnopqrstuvwxyz0123456789@$_.~'-
					//Cannot start with apostrophe (') or dash (-)
					//Non-breaking white spaces (space, tab, new line) are not allowed in login names
					//No double-byte characters are allowed (e.g. Japanese or Chinese characters)
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapfault','skillsoft',$result->getmessage()),NULL);
				}
				elseif (!stripos($result->getmessage(), "invalid password") == false)
				{
					//The password specified is not valid
					//All single-byte characters are allowed except back slash (\)
					//Non-breaking white spaces (space, tab, new line) are not allowed
					//No double-byte characters are allowed (e.g. Japanese or Chinese characters)
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapfault','skillsoft',$result->getmessage()),NULL);
				}
				elseif (!stripos($result->getmessage(), "enter a valid email address") == false)
				{
					//The email address specified is not a valid SMTP email address
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapfault','skillsoft',$result->getmessage()),NULL);
				}
				elseif (!stripos($result->getmessage(), "error: org code") == false)
				{
					//The single orgcode specified in the _req.groupCode is not valid
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapfault','skillsoft',$result->getmessage()),NULL);
				}
				elseif (!stripos($result->getmessage(), "user group with orgcode") == false)
				{
					//One of the multiple orgcodes specified in the _req.groupCode is not valid
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapfault','skillsoft',$result->getmessage()),NULL);
				}
				elseif (!stripos($result->getmessage(), "field is too long") == false)
				{
					//One of the fields specified, see full faultstring for which, is too large
					//Generally text fields can be 255 characters in length
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapfault','skillsoft',$result->getmessage()),NULL);
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


/**
 * Create a Custom Report
 *
 * @param string $group the SkillPort User Group
 * @param string $startDate the Startdate for the scope of the report
 * @param string $endDate the Enddate for the scope of the report
 * @param string $dateToUse the date field used for the scope, valid values are any (default), first - First Access Date, most - Most Recent Access Date, completion - Completion Date
 * @param string $listBy the order to list users by, valid values are user, course
 * @param bool $includeSubgroups return all sub groups to $group
 * @param bool $includeDeactivatedUsers include users deactivated in SkillPort
 * @param string $reportFormat format for the report, valid values are CSV, CSV16 and HTML
 * @return olsasoapresponse olsasoapresponse->result. result->handle is the report handle used for polling
 */
function UD_InitiateCustomReportByUserGroups($group,$startDate='',$endDate='',$dateToUse='any',$listBy='user',$includeSubgroups=true,$includeDeactivatedUsers=true,$reportFormat='CSV') {
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
			"trace"      => 1,
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
			$InitiateCustomReportByUserGroupsRequest =  array(
				"customerId" => $customerId,
				"includeDeactivatedUsers" => $includeDeactivatedUsers,
				"includeSubgroups" => $includeSubgroups,
				"reportFormat" => $reportFormat,
				"listBy" => $listBy,
				"dateToUse" => $dateToUse,
				"group" => $group,
			);

			//If we have BOTH dates specified then we use them
			if (!empty($startDate)){
				if ($starttimestamp = strtotime($startDate)) {
					$InitiateCustomReportByUserGroupsRequest["startDate"] = date('Y-m-d', $starttimestamp);
				}
			}
			if (!empty($endDate)){
				if ($endtimestamp = strtotime($endDate)) {
					$InitiateCustomReportByUserGroupsRequest["endDate"] = date('Y-m-d', $endtimestamp);
				}
			}




			//Call the WebService and stored result in $result
			$result=$client->__soapCall('UD_InitiateCustomReportByUserGroups',array('parameters'=>$InitiateCustomReportByUserGroupsRequest));

			if (is_soap_fault($result)) {
				echo $client->__getLastRequest();
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

/**
 * Polls for the specified report handle
 *
 * @param string $handle the report handle to poll for
 * @return olsasoapresponse olsasoapresponse->result is a NULL object
 */
function UTIL_PollForReport($handle) {
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
			$PollForReportRequest =  array(
				"customerId" => $customerId,
				"reportId" => $handle,
			);

			//Call the WebService and stored result in $result
			$result=$client->__soapCall('UTIL_PollForReport',array('parameters'=>$PollForReportRequest));

			if (is_soap_fault($result)) {

				if (!stripos($result->getmessage(),'security token could not be authenticated or authorized') == false) {
					//Authentication Failure
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapauthentication','skillsoft'),NULL);
				} elseif (!stripos($result->detail->exceptionName, 'DataNotReadyYetFault') == false){
					//Report not ready yet
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapreportnotready','skillsoft'),NULL);
				} elseif (!stripos($result->detail->exceptionName, 'ReportDoesNotExistFault') == false){
					//Report not ready yet
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapreportnotvalid','skillsoft',$handle),NULL);
				} else {
					//General SOAP Fault
					//print_error('olsassoapfault','skillsoft','',$result->getmessage());
					$response = new olsaresponse(false,get_string('skillsoft_olsassoapfault','skillsoft',$result->getmessage()),NULL);
				}
			} else {
				$response = new olsaresponse(true,'',$result);
			}
	}
	return $response;
}
