<?php
error_reporting(E_ALL ^ E_WARNING);

// set caching of wsdl

//ini_set('soap.wsdl_cache_enabled', getIndicate2Caching());
//ini_set('soap.wsdl_cache_ttl', getIndicate2CacheTTL());

include_once 'SecureSoapClient.php';
include_once 'Indicate2Message.php';
class Indicate2_Connect {
	
	/**
     * Secure Soap Client
     *
     * @var SecureSoapClient
	*/
	protected $_client = null;
	
	/**
     * WSDL host
     *
     * @var string
	*/
	protected $_host = null;
	
	/**
     * WSDL namespace
     *
     * @var string
	*/
	protected $_ns = null;	

	/**
     * WSSE username
     *
     * @var string
	*/
	protected $_username = null;
	
	/**
     * WSSE password
     *
     * @var string
	*/
	protected $_password = null;
	
	/**
	 * PHP time limit in seconds (local)
     *
     * @var int
	*/
	protected $_timeLimit = null;
	
	/**
	 * PHP time limit in seconds (global)
     *
     * @var int
	*/
	const TIMELIMIT = 45;
	
	/**
     * Identifier for survey start event
     *
     * @var string
	*/
	const EVENT_START = 'START';
	/**
     * Identifier for survey stop event
     *
     * @var string
	*/
	const EVENT_STOP = 'STOP';
	/**
     * Identifier for survey resend event
     *
     * @var string
	*/
	const EVENT_RESEND = 'RESEND';
	/**
     * Identifier for remind event
     *
     * @var string
	*/
	const EVENT_REMIND = 'REMIND';
	
	/**
	* Creates Indicate2_Connect instance
	*
	* @param string $host=null Host(WSDL) of SOAP Service
	* @param string $ns=null Namespace of SOAP Service
	* @param string $username=null Username
	* @param string $password=null Password
	*  
	* @return void
	*/
	public function __construct($host = null, $ns = null, $username = null, $password = null) {
		
		// get configuration 
		$config =Zend_Registry::get('config');
		
		// set connection parameters 
		$this->_host = empty($host) ? $config->indicate2->connect->endpoint : $host;
		$this->_ns = empty($ns) ? $config->indicate2->connect->namespace : $ns;
		$this->_username = empty($username) ? $config->indicate2->connect->user : $username;
		$this->_password = empty($password) ? $config->indicate2->connect->password  : $password;
	}
	
	/**
	* Checks connection to Indicate2
	*
	* @param string $host=null Host(WSDL) of SOAP Service
	* @param string $ns=null Namespace of SOAP Service
	* @param string $username=null Username
	* @param string $password=null Password
	*  
	* @return boolean
	* @throws Indicate2_Exception
	*/
	public function connect($host = null, $ns = null, $username = null, $password = null, $timeLimit = null) {
		
		// get configuration 
//		if (!($cache = getCache()) || ($cache instanceof Zend_Cache_Core && !$cache->test('indicate2connect'))) {
			$config = Zend_Registry::get('config');
			
			// set connection parameters 
			$this->_host = empty($host) ? (empty($this->_host) ? $config->indicate2->connect->endpoint : $this->_host) : $host;
			$this->_ns = empty($ns) ? (empty($this->_ns) ? $config->indicate2->connect->namespace : $this->_ns) : $ns;
			$this->_username = empty($username) ? (empty($this->_username) ? $config->indicate2->connect->user : $this->_username) : $username;
			$this->_password = empty($password) ? (empty($this->_password) ? $config->indicate2->connect->password : $this->_password) : $password;
			
			try {
			
			if (empty($config->indicate2->enable)) 
				throw new Indicate2_Exception(Indicate2_Message::NO_SERVICE);	
				
			// set connection time limits
			$timeLimit = (empty($timeLimit) ?+ (empty($this->_timeLimit) ? self::TIMELIMIT : $this->_timeLimit) : $timeLimit); 
			set_time_limit($timeLimit);
			ini_set('default_socket_timeout', $timeLimit);
			
			$this->_client = new SecureSoapClient($this->_host, array(
				'encoding' => 'UTF-8',
				'trace' => 1,
				'connection_timeout'=>$timeLimit
			), $this->_username, $this->_password); 
			
//			if ($cache)
//				$cache->save($this->_client, 'indicate2connect');
//			
			return true;
				
			}
			catch (Exception $e) {
				//return $e->getMessage();
				//Zend_Debug::dump($e->getMessage());exit();
				throw new Indicate2_Exception(Indicate2_Message::NO_CONNECTION . '<br/>' . $e->getMessage());
			}
		/*}
		else {
			$this->_client = $cache->load('indicate2connect');
			return true;
		}*/
	}

	/**
	* Sets time limit of PHP script
	*
	* @param int $timeLimit Timelimit of PHP script in seconds
	* 
	* @return void
	*/
	public function setTimeLimit($timeLimit) {
		$this->_timeLimit = intval($timeLimit);
	}
	
	// SURVEY FUNCTIONS
 	
	/**
	* Returns Indicate2 survey ID of created survey
	*
	* @param 	int		$questionnaireId	ID of Indicate2 questionnaire
	* @param 	string	$surveyTitle		Title of survey
	* @param 	string	$surveyDescription	Description of survey
	*
	* @return 	int 	Survey ID
	* @throws Indicate2_Exception
	*/
	public function createSurvey($questionnaireId, $surveyTitle, $surveyDescription) {
		
		try {
		
			// connect
			if (empty($this->_client)) if (empty($this->_client)) $this->connect();
			
			// create request
			$createSurveyRequest = array(
				'CreateSurveyRequest' => array(
					'QuestionnaireId' => $questionnaireId,
					'Title' => $surveyTitle,
					'Description' => $surveyDescription
			));
		
			// call function
			$response = $this->_client->__soapCall('CreateSurvey', $createSurveyRequest);
			
			return $response->SurveyId;
		}
		catch (Exception $e) {
			// function call failed
			throw new Indicate2_Exception($e->getMessage());
		}
	}
		
	/**
	* Deletes Indicate2 survey
	*
	* @param int $surveyId Indicate2 ID of survey
	*
	* @return boolean
	* @throws Indicate2_Exception
	*/
	public function deleteSurvey($surveyId) {
		
		try {
			
			// connect connection
			if (empty($this->_client)) $this->connect();
		
		
			// create request
			$deleteSurveyRequest = array(
				'DeleteSurveyRequest' => array(
					'SurveyId' => $surveyId,
			));
		
			// call function
			$response = $this->_client->__soapCall('DeleteSurvey', $deleteSurveyRequest);
			return $response->Result;
		}
		catch (Exception $e) {
			// function call failed
			throw new Indicate2_Exception($e->getMessage());
			}
	}
	
	/**
	* Deletes Indicate2 survey
	*
	* @param int $surveyId Indicate2 ID of survey
	*
	* @return boolean
	* @throws Indicate2_Exception
	*/
	public function closeSurvey($surveyId) {
		
		try {
			
			// connect
			if (empty($this->_client)) $this->connect();
		
			
			// create request
			$closeSurveyRequest = array(
				'CloseSurveyRequest' => array(
					'SurveyId' => $surveyId,
			));
		
			// call function
			$response = $this->_client->__soapCall('CloseSurvey', $closeSurveyRequest);
			return $response->Result;
		}
		catch (Exception $e) {
			// function call failed
			throw new Indicate2_Exception($e->getMessage);
		}
	}

	/**
	* Returns state of survey
	*
	* @param int $surveyId Indicate2 ID of survey
	*
	* @return string
	* @throws Indicate2_Exception
	*/
	public function getSurveyState($surveyId) {
		
		try {
			
			// connect
			if (empty($this->_client)) $this->connect();
		
			// create request
			$getSurveyStateRequest = array(
				'GetSurveyStateRequest' => array(
					'SurveyId' => $surveyId,
			));
		
			// call function
			$response = $this->_client->__soapCall('GetSurveyState', $getSurveyStateRequest);
			
			//Zend_Debug::dump($this->_client->__getLastRequest());
			//Zend_Debug::dump($this->_client->__getLastResponse());
			//exit();
			
			
			return $response->State;
		}
		catch (SoapFault $se) {
			throw new Indicate2_Exception('SOAPFEHLER:' . $se->getMessage());
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Returns email-state of Indicate2 survey
	*
	* @param int $surveyId Indicate2 ID of survey
	*
	* @return object
	* @throws Indicate2_Exception
	*/
	public function getSurveyEmailState($surveyId) {
		
		try {
			
			// connect
			if (empty($this->_client)) $this->connect();
		
			// create request
			$getSurveyEmailStateRequest = array(
				'GetSurveyEmailStateRequest' => array(
					'SurveyId' => $surveyId,
			));
		
			//Zend_Debug::dump($getSurveyEmailStateRequest);exit();
			
			// call function
			$response = $this->_client->__soapCall('GetSurveyEmailState', $getSurveyEmailStateRequest);
			
			//Zend_Debug::dump($response);exit();
			
			return $response->State;
		}
		catch (Exception $e) {
			// function call failed
			throw new Indicate2_Exception($e->getMessage());
		}
	}

	/**
	* Returns survey object of Indicate2 survey
	*
	* @param int $surveyId Indicate2 ID of survey
	*
	* @return object
	* @throws Indicate2_Exception
	*/
	public function getSurvey($surveyId) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
		
			// create request
			$getSurveyRequest = array(
				'GetSurveyRequest' => array(
					'SurveyId' => $surveyId,
			));
		
			// call function
			$response = $this->_client->__soapCall('GetSurvey', $getSurveyRequest);
			
			return $response->Survey;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}		
	}

	/**
	* Starts Indicate2 survey and sends all emails
	*
	* @param int 	$surveyId 			Indicate2 ID of survey
	* @param string $emailFromName 		Sending email name
	* @param string $emailFromAddress 	Sending email address
	* @param string $emailSubject 		Sending email subject
	* @param string $emailText 			Sending email text
	*
	* @return boolean
	* @throws Indicate2_Exception
	*/
	public function sendInvitationEmailsForSurvey($surveyId, $emailFromName, $emailFromAddress, $emailSubject, $emailText) {
	
		try { 
			// connect
			if (empty($this->_client)) $this->connect();
	
			$setupModel = new Setup();
			
			// create request
			$sendInvitationEmailsForSurveyRequest = array(
				'SendInvitationEmailsForSurveyRequest' => array(
					'SurveyId' => intval($surveyId),
					'FromEmail' => $emailFromAddress,
					'FromName' => $emailFromName,
					'EmailSubjectWithPlaceholder' => $emailSubject,
					'EmailContentWithPlaceholder' => $emailText,
			));
		
			// call function
			$response = $this->_client->__soapCall('SendInvitationEmailsForSurvey', $sendInvitationEmailsForSurveyRequest);
			return $response->Result;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}

	/**
	* Sends reminder emails for Indicate2 survey 
	*
	* @param int 	$surveyId 			Indicate2 ID of survey
	* @param string $emailFromName 		Sending email name
	* @param string $emailFromAddress 	Sending email address
	* @param string $emailSubject 		Sending email subject
	* @param string $emailText 			Sending email text
	*
	* @return boolean
	* @throws Indicate2_Exception
	*/
	public function sendReminderEmailsForSurvey($surveyId, $emailFromName, $emailFromAddress, $emailSubject, $emailText) {
		try {
			// connect
			if (empty($this->_client)) $this->connect();
			
			// create request
			$sendReminderEmailsForSurveyRequest = array(
				'SendReminderEmailsForSurveyRequest' => array(
					'SurveyId' => intval($surveyId),
					'FromEmail' => $emailFromAddress,
					'FromName' => $emailFromName,
					'EmailSubjectWithPlaceholder' => $emailSubject,
					'EmailContentWithPlaceholder' => $emailText,
			));
		
			// call function
			$response = $this->_client->__soapCall('SendReminderEmailsForSurvey', $sendReminderEmailsForSurveyRequest);
			return $response->Result;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}

	/**
	* Sends invitation emails to fail-sended participants for Indicate2 survey 
	*
	* @param int $surveyId Indicate2 ID of survey
	* @param string $emailFromName 		Sending email name
	* @param string $emailFromAddress 	Sending email address
	* @param string $emailSubject 		Sending email subject
	* @param string $emailText 			Sending email text
	*
	* @return boolean
	* @throws Indicate2_Exception
	*/
	public function sendInvitationEmailsForSurveyToFailed($surveyId, $emailFromName, $emailFromAddress, $emailSubject, $emailText) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
		
			$setupModel = new Setup();
			
			// create request
			$sendInvitationEmailsForSurveyToFailedRequest = array(
				'SendInvitationEmailsForSurveyToFailedRequest' => array(
					'SurveyId' => intval($surveyId),
					'FromEmail' => $emailFromAddress,
					'FromName' => $emailFromName,
					'EmailSubjectWithPlaceholder' => $emailSubject,
					'EmailContentWithPlaceholder' => $emailText,
			));
		
			// call function
			$response = $this->_client->__soapCall('SendInvitationEmailsForSurveyToFailed', $sendInvitationEmailsForSurveyToFailedRequest);
			return $response->Result;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}

	/**
	* Removes unsent email addresses from Indicate2 survey
	*
	* @param int $surveyId Indicate2 ID of survey
	*
	* @return boolean
	* @throws Indicate2_Exception
	*/
	public function removeUnsentEmailsFromSurvey($surveyId) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
		
			// create request
			$removeUnsentEmailsFromSurveyRequest = array(
				'RemoveUnsentEmailsFromSurveyRequest' => array(
					'SurveyId' => intval($surveyId),
				));
		
			// call function
			$response = $this->_client->__soapCall('RemoveUnsentEmailsFromSurvey', $removeUnsentEmailsFromSurveyRequest);
			return $response->Result;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}

	/**
	* Returns report from Indicate2 survey 
	*
	* @param int 	$surveyId 			Indicate2 ID of survey
	* @param string $type 				Type of report (DOC,ODT,PDF,CSV)
	* @param array 	$contextQuestions 	OPTIONAL Indicate2 Context ID's and Indicate2 question ID's
	* 
	* @return string Base64-bytestream
	* @throws Indicate2_Exception
	*/
	public function getSurveyReport($surveyId, $type, $contextQuestions = null) {
		
		try {
			
			$indicate2ContextQuestions = array();
			$n = 0;
			foreach ($contextQuestions as $contextId => $questions) {
				// fill in Indicate2 context ID
				$indicate2ContextQuestions[$n]['ContextId'] = $contextId;
				
				// empty question ID's
				if (count($questions) == 0) 
					$indicate2ContextQuestions[$n]['QuestionnaireQuestions']['QuestionnaireQuestion'] = array();
				else
					foreach ($questions as $questionId) {
						$indicate2ContextQuestions[$n]['QuestionnaireQuestions']['QuestionnaireQuestion'][] =  array('QuestionId' => $questionId, 'InfoText' => null, 'IntroText' => null);	
					}
				$n++;
			}
			
			// connect
			if (empty($this->_client)) $this->connect();
	
			// create request
			$getSurveyReportRequest = array(
				'GetSurveyReportRequest' => array(
					'SurveyId' => intval($surveyId),
					'Format' => strtoupper($type),
					'ExcludeContextsAndQuestions' => array(
						'QuestionnaireQuestionContext' => $indicate2ContextQuestions			
			)));
		
			//Zend_Debug::dump($getSurveyReportRequest);exit();
			
			// call function
			$response = $this->_client->__soapCall('GetSurveyReport', $getSurveyReportRequest);
			
			//Zend_Debug::dump($response);exit();
			
			if (isset($response->Report)) 
				return $response->Report;
			else 
				// no report var
				throw new Indicate2_Exception(Indicate2_Message::NO_SURVEY_REPORT);
		}
		catch (SoapFault $sf) {
			throw new Indicate2_Exception($sf->getMessage());
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	/**
	* Reopens Indicate2 survey
	*
	* @param int 	$surveyId 	Indicate2 ID of survey
	*
	* @return boolean
	* @throws Indicate2_Exception
	*/
	public function reOpenSurvey($surveyId) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
		
			// create request
			$reopenSurveyRequest = array(
				'ReopenSurveyRequest' => array(
					'SurveyId' => $surveyId,
					
			));
		
			// call function
			$response = $this->_client->__soapCall('ReopenSurvey', $reopenSurveyRequest);
			return $response->Result;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());	
		}
	}

	/**
	* Checks if survey is existing in Indicate2
	*
	* @param int $surveyId 	Indicate2 survey ID
	*
	* @return boolean
	* @throws Indicate2_Exception
	*/
	public function isSurveyExisting($surveyId) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
		
			// create request
			$isSurveyExistingRequest = array(
				'IsSurveyExistingRequest' => array(
					'SurveyId' => $surveyId,
			));
		
			// call function
			$response = $this->_client->__soapCall('IsSurveyExisting', $isSurveyExistingRequest);
			
			return $response->Result;
		}
		catch (SoapFault $e) {
			throw new Indicate2_Exception($e->getMessage() . '@' . __FUNCTION__ . ' (' . $e->getLine() . ')');	
		}	
		catch (Exception $e) {
			throw new Exception($e->getMessage());	
		}		
	}
	
	// GET INDICATE2-ID FUNCTIONS
	
	/**
	* Returns Indicate2 question ID from intern question ID
	*
	* @param int $questionId Question ID (intern)
	*
	* @return int
	* @throws Indicate2_Exception
	*/
	public function getIndicate2QuestionId($questionId) {
		
		try {
			$questionPrefix = getQuestionPrefix();
			$questionModel = new Question();
			
			// data exists?
			if ($questionData = $questionModel->getData($questionId)) {
			
				// check question type and get indicate2 id
				if ($questionModel->isComment($questionData[$questionPrefix . 'type']))
					return $this->createCommentQuestion($questionData); 
				elseif ($questionModel->isRating($questionData[$questionPrefix . 'type'])) {
					return $this->createRatingQuestion($questionData);
				}
			 	elseif ($questionModel->isSelection($questionData[$questionPrefix . 'type']))
					return $this->createSelectionQuestion($questionData);
				else
					// no exact type
					throw new Exception("Fragetyp von ID " . $questionId . " unbekannt");
			}
			else {
				// no data found
				throw new Exception("Keine Daten zur Frage-ID " . $questionId . "gefunden");
			}
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Returns Indicate2 context ID from intern context ID
	*
	* @param int $contextId Context ID (intern)
	*
	* @return int
	* @throws Indicate2_Exception
	*/
	public function getIndicate2ContextId($contextId) {
		
		try {
			$contextPrefix = getContextPrefix();
			$contextModel = new Context();
			
			// data exists?
			if ($contextData = $contextModel->getData($contextId)) {
				//Zend_Debug::dump($contextData);
				// data found, get context id from indicate2
				return $this->createHeader($contextData);
			}
			else {
				// no data found
				throw new Exception("Keine Kontext-Daten zur ID " . $contextId . " vorhanden.");
			}
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Returns Indicate2 personcontext ID from intern teacher ID
	*
	* @param int $teacherId Teacher ID (intern)
	*
	* @return int
	* @throws Indicate2_Exception
	*/
	public function getIndicate2PersonContextId($teacherId) {
	
		try {
			$teacherPrefix = getTeacherPrefix();
			
			$teacherModel =  new Teacher();
			$dbUpdate = false;
			//return ($teacherIndicate2PersonContextId = $teacherModel->getIndicate2PersonContextId($teacherId, $teacherData)) ? $teacherIndicate2PersonContextId : $this->createPersonContext($teacherData) ;
			
			if ($teacherData = $teacherModel->getData($teacherId)) {
				$personContextId = $this->createPersonContext($teacherData);
				if ($personContextId != $teacherModel->getIndicate2PersonContextId($teacherId)) {
					
					$dbUpdate = true;
					$teacherModel->getAdapter()->beginTransaction();
					$teacherModel->update(array(
						$teacherPrefix . 'indicate2personcontextid' => $personContextId
					), $teacherPrefix . 'id = ' . $teacherId);
					$teacherModel->getAdapter()->commit();
					
				}
			}
			else 
				throw new Exception('Keine Dozentendaten zu ID ' . $teacherId . ' vorhanden');
				
			return $personContextId;
		}
		catch (Exception $e) {
			$dbUpdate ? $teacherModel->getAdapter()->rollBack(): null;
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	// PARTICIPANT FUNCTIONS

	/**
	* Reopen Indicate2 survey and returns failed email-addresses
	*
	* @param int 			$surveyId 			Indicate2 survey ID
	* @param string|array 	$mails				Email addresses
	* @param string 		$emailFromName 		Sending email name
	* @param string 		$emailFromAddress 	Sending email address
	* @param string 		$emailSubject 		Sending email subject
	* @param string 		$emailText 			Sending email text
	*
	* @return object Failed email addresses
	* @throws Indicate2_Exception
	*/
	public function addParticipantsToActiveSurvey($surveyId, $mails, $emailFromName, $emailFromAddress, $emailSubject, $emailText) {
		
		try {
		
			if (!is_array($mails))
				$mails = array($mails);
			
			// connect
			if (empty($this->_client)) $this->connect();
				
			$setupModel = new Setup();
				
			$emailArray = array();
			
			// create mail array
			foreach ($mails as $mail) 
				if (isEmail(trim($mail)))
					$emailArray[]['Address'] = trim($mail);
			
			// create request
			$addParticipantsToActiveSurveyRequest = array(
				'AddParticipantsToActiveSurveyRequest' => array(
					'SurveyId' => intval($surveyId),
					'FromEmail' => $emailFromAddress,
					'FromName' => $emailFromName,
					'EmailSubjectWithPlaceholder' => $emailSubject,
					'EmailContentWithPlaceholder' => $emailText,
					'Emails' => array(
						'Email' => $emailArray
			)));
			
			// call function
			$response = $this->_client->__soapCall('AddParticipantsToActiveSurvey', $addParticipantsToActiveSurveyRequest);
			return $response->EmailsFailedToAdd;
		}
		catch (Exception $e) {
			// function call failed
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Adds particpants email addresses to pending Indicate2 survey and returns failed email-addresses
	*
	* @param int 			$surveyId 	Indicate2 survey ID
	* @param string|array 	$mails		Email addresses
	*
	* @return object Failed email addresses
	* @throws Indicate2_Exception
	*/
	public function addParticipantsToPendingSurvey($surveyId, $mails) {
		
		try {
			
			if (!is_array($mails))
				$mails = array($mails);
		
			// connect
			if (empty($this->_client)) $this->connect();
		
			$emailArray = array();
			
			// create mail array
			foreach ($mails as $mail) 
				if (isEmail(trim($mail)))
					$emailArray[]['Address'] = trim($mail);
			
			// create request
			$addParticipantsToPendingSurveyRequest = array(
				'AddParticipantsToPendingSurveyRequest' => array(
					'SurveyId' => intval($surveyId),
					'Emails' => array(
						'Email' => $emailArray
			)));
		
			// call public function
			$response = $this->_client->__soapCall('AddParticipantsToPendingSurvey', $addParticipantsToPendingSurveyRequest);
			return $response->EmailsFailedToAdd;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Removes particpants email addresses from Indicate2 survey and returns failed email-addresses
	*
	* @param int 			$surveyId 	Indicate2 survey ID
	* @param string|array 	$mails		Email addresses
	*
	* @return object Failed email addresses
	* @throws Indicate2_Exception
	*/
	public function removeParticipantsFromSurvey($surveyId, $mails) {
		
		try {
			if (!is_array($mails))
				$mails = array($mails);
	
			// connect
			if (empty($this->_client)) $this->connect();
	
			$emailArray = array();
			
			// create mail array
			foreach ($mails as $mail) 
				if (isEmail(trim($mail)))
					$emailArray[]['Address'] = trim($mail);
			
			// create request
			$removeParticipantsFromSurveyRequest = array(
				'RemoveParticipantsFromSurveyRequest' => array(
					'SurveyId' => intval($surveyId),
					'Emails' => array(
						'Email' => $emailArray
			)));
		
			// call public function
			$response = $this->_client->__soapCall('RemoveParticipantsFromSurvey', $removeParticipantsFromSurveyRequest);
			return $response->EmailsFailedToRemove;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}

	/**
	* Removes particpants email addresses from all Indicate2 surveys and returns failed email-addresses
	*
	* @param string|array $emails		Email addresses
	*
	* @return object Failed email addresses
	* 
	* @throws Indicate2_Exception
	* @throws Exception
	*/
	public function removeParticipantsFromAllSurveys($emails) {
		
		try {
			if (!is_array($emails))
				$emails = array($emails);
	
			// connect
			if (empty($this->_client)) $this->connect();
	
			$emailArray = array();
			
			// create mail array
			foreach ($emails as $email) 
				if (isEmail(trim($email)))
					$emailArray[]['Address'] = trim($email);
			
			// create request
			$removeParticipantsFromAllSurveysRequest = array(
				'RemoveParticipantsFromAllSurveysRequest' => array(
					'Emails' => array(
						'Email' => $emailArray
			)));
		
			// call public function
			$response = $this->_client->__soapCall('RemoveParticipantsFromAllSurveys', $removeParticipantsFromAllSurveysRequest);
			
			return $response->EmailsFailedToRemove;
		}
		catch (SoapFault  $se) {
			throw new Indicate2_Exception($se->getMessage());
		}
		catch (Exception $e) {
			throw $e;
		}
	}
	
	
	/**
	* Replaces email address of particpant with new email address
	*
	* @param string	$emailOld	Old email addresses
	* @param string	$emailNew	New email addresses
	*
	* @return boolean
	* @throws Indicate2_Exception
	*/
	public function changeParticipantEmailAddressGlobally($emailOld, $emailNew) {
		
		try {
				// connect
			if (empty($this->_client)) $this->connect();
	
			$setupModel = new Setup();
			
			// create request
			$changeParticipantEmailAddressGloballyRequest = array(
				'ChangeParticipantEmailAddressGloballyRequest' => array(
					'EmailAddressOld' => $emailOld,
					'EmailAddressNew' => $emailNew,
					'FromEmail' => $setupModel->getEmailFrom(),
					'FromName' => $setupModel->getEmailFromName()
			));
		
			// call public function
			$response = $this->_client->__soapCall('ChangeParticipantEmailAddressGlobally', $changeParticipantEmailAddressGloballyRequest);
			return isset($response->AffectedSurveysIds) ? $response->AffectedSurveysIds : array();
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Checks if email address is particpant in survey
	*
	* @param int	$surveyId 	Indicate2 survey ID
	* @param string	$mailN		Email addresses
	*
	* @return boolean
	* @throws Indicate2_Exception
	*/
	public function isParticipantInSurvey($surveyId, $emailAddress) {
		try {
				// connect
			if (empty($this->_client)) $this->connect();
	
			$setupModel = new Setup();
			
			// create request
			$isParticipantInSurveyRequest = array(
				'IsParticipantInSurveyRequest' => array(
					'SurveyId' => $surveyId,
					'EmailAddress' => $emailAddress
			));
		
			// call public function
			$response = $this->_client->__soapCall('IsParticipantInSurvey', $isParticipantInSurveyRequest);
			return $response->Result;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	// QUESTIONNAIRE FUNCTIONS
 
	/**
	* Returns PDF preview of Indicate2 questionnaire
	*
	* @param int $questionaireId Indicate2 questionnaire ID
	*
	* @return string Base64 - bytestream of PDF
	* @throws Indicate2_Exception
	*/
	public function getQuestionnairePreview($questionaireId) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
		
			// create request
			$getQuestionnairePreviewRequest = array(
				'GetQuestionnairePreviewRequest' => array(
					'QuestionnaireId' => intval($questionaireId),
			));
		
			//Zend_Debug::dump($getQuestionnairePreviewRequest);
			
			// call public function
			$response = $this->_client->__soapCall('GetQuestionnairePreview', $getQuestionnairePreviewRequest);
			return $response->Pdf;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());	
		}
	}
	
	/**
	* Deletes Indicate2 questionnaire
	*
	* @param int $questionaireId Indicate2 questionnaire ID
	*
	* @return boolean
	* @throws Indicate2_Exception
	*/
	public function deleteQuestionnaire($questionaireId) {
		try {
			// connect
			if (empty($this->_client)) $this->connect();
		
			// create request
			$deleteQuestionnaireRequest = array(
				'DeleteQuestionnaireRequest' => array(
					'QuestionnaireId' => $questionaireId,
					
			));
		
			// call public function
			$response = $this->_client->__soapCall('DeleteQuestionnaire', $deleteQuestionnaireRequest);
			return $response->Result;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	// CREATE QUESTION FUNCTIONS
 	
	/**
	* Creates Indicate2 comment question
	*
	* @param array $questionData Question data (intern) 
	*
	* @return int
	* @throws Indicate2_Exception
	*/
	public function createCommentQuestion($questionData) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
			
			$questionPrefix = getQuestionPrefix();
			$questionCommentPrefix = getQuestionCommentPrefix();
	
			$optional = intval($questionData[$questionPrefix . 'optional']);
			
			// create soap request
			$createCommentQuestionRequest = array(
				'CreateCommentQuestionRequest' => array(
					'CommentQuestion' => array(
						'Title' => $questionData[$questionPrefix . 'title'], 
						'InfoText' => $questionData[$questionPrefix . 'infotext'],
						'Optional' => !empty($optional),
						'DisplaySize' => $questionData[$questionCommentPrefix . 'displaysize'],
						'RegExpRestriction' => ''
				)));
	
			//Zend_Debug::dump($createCommentQuestionRequest);
	
			$response = $this->_client->__soapCall('CreateCommentQuestion', $createCommentQuestionRequest);
			return $response->QuestionId;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Creates Indicate2 rating question
	*
	* @param array $questionData Question data (intern) 
	*
	* @return int
	* @throws Indicate2_Exception
	*/
	public function createRatingQuestion($questionData) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
		
			
			$questionPrefix = getQuestionPrefix();
			$questionRatingPrefix = getQuestionRatingPrefix();
		
			$optional = intval($questionData[$questionPrefix . 'optional']);
			
			$createRatingQuestionRequest = array(
				'CreateRatingQuestionRequest' => array(
					'RatingQuestion' => array(
						'Title' => $questionData[$questionPrefix . 'title'], 
						'InfoText' => $questionData[$questionPrefix . 'infotext'],
						'Optional' => !empty($optional),
						'baseValue' => $questionData[$questionRatingPrefix . 'basevalue'],
						'leftLabel' => $questionData[$questionRatingPrefix . 'leftlabel'],
						'rightLabel' =>  $questionData[$questionRatingPrefix . 'rightlabel'],
						'greenShading' =>  $questionData[$questionRatingPrefix . 'greenshading'],
						'ordering' =>  $questionData[$questionRatingPrefix . 'ordering'],
						'range' =>  $questionData[$questionRatingPrefix . 'range']
				)));
		
			//Zend_Debug::dump($createRatingQuestionRequest);
	
			$response = $this->_client->__soapCall('CreateRatingQuestion', $createRatingQuestionRequest);
			return $response->QuestionId;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Creates Indicate2 selection question
	*
	* @param array $questionData Question data (intern) 
	*
	* @return int
	* @throws Indicate2_Exception
	*/
	public function createSelectionQuestion($questionData) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
		
			$questionPrefix = getQuestionPrefix();
			$questionSelectionPrefix = getQuestionSelectionPrefix();
			
			$optional = intval($questionData[$questionPrefix . 'optional']);
			
			$createSelectionQuestionRequest = array(
				'CreateSelectionQuestionRequest' => array(
					'SelectionQuestion' => array(
						'Title' => $questionData[$questionPrefix . 'title'],
						'InfoText' => $questionData[$questionPrefix . 'infotext'],
						'Optional' => !empty($optional),
						'maxSelectable' => intval($questionData[$questionSelectionPrefix . 'maxselectable']), 
						'minSelectable' => intval($questionData[$questionSelectionPrefix . 'minselectable']),
						'selectionType' => $questionData[$questionSelectionPrefix . 'type'], 
						'selectSize' => intval($questionData[$questionSelectionPrefix . 'size']),
						'SelectionQuestionOptions' => array(
							'SelectionQuestionOption' => getQuestionSelectionOptions($questionData[$questionSelectionPrefix . 'options'])
			))));
			
			//Zend_Debug::dump($createSelectionQuestionRequest);
			
			$response = $this->_client->__soapCall('CreateSelectionQuestion', $createSelectionQuestionRequest);
			return $response->QuestionId;
		}
	 	catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	// ANALYZE QUESTION FUNCTIONS
 	
	/**
	* Analyses Indicate2 comment question
	*
	* @param int 		$questionId 		Indicate2 question ID 
	* @param int|array 	$surveyIds 			Indicate2 survey ID 
	* @param int|array 	$questionContextIds	Indicate2 question-context ID's 
	*
	* @return stdClass Analyse result
	* 
	* @throws Indicate2_Exception
	*/
	public function analyseCommentQuestion($questionId, $surveyIds, $questionContextIds) {
		
		try {
			if (!is_array($surveyIds)) 
				$surveyIds = array($surveyIds);
		
			// connect
			if (empty($this->_client)) $this->connect();
				
			// create soap request
			$analyseCommentQuestionRequest = array(
				'AnalyseCommentQuestionRequest' => array(
					'QuestionId' => $questionId,
					'SurveyIds' => array(
						'SurveyId' => $surveyIds), 
					'QuestionContextIds' => array(
						'QuestionContextId' => $questionContextIds
			)));
	
			//Zend_Debug::dump($analyseCommentQuestionRequest);exit();
	
			$response = $this->_client->__soapCall('AnalyseCommentQuestion', $analyseCommentQuestionRequest);
			return isset($response->Context) ? $response->Context : array();
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
		
	/**
	* Analyses multiple Indicate2 comment questions 
	*
	* @param array $list Combinated list of questions, contexts and surveys
	*
	* @return stdClass Analyse result
	* 
	* @throws Indicate2_Exception
	*/
	public function analyseMultipleCommentQuestions($list, $debug = false) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
			
			// create soap request
			$analyseMultipleCommentQuestionsRequest = array(
				'AnalyseMultipleCommentQuestionsRequest' => array(
					'CommentQuestionsToAnalyse' => $list
			));
	
			//Zend_Debug::dump($analyseMultipleCommentQuestionsRequest);exit();
				
			$response = $this->_client->__soapCall('AnalyseMultipleCommentQuestions', $analyseMultipleCommentQuestionsRequest);
			
			//Zend_Debug::dump($response);exit();
			
			return $response;
		}
		catch (SoapFault $sfe) {
			if ($debug) Zend_Debug::dump($sfe);
			throw new Indicate2_Exception('SoapFault: ' . $sfe->getMessage());
		}
		catch (Exception $e) {
			throw $e;
		}
	}
	
	/**
	* Analyses Indicate2 selection question
	*
	* @param int 		$questionId 		Indicate2 question ID 
	* @param int|array 	$surveyIds 			Indicate2 survey ID 
	* @param int|array 	$questionContextIds	Indicate2 question-context ID's 
	*
	* @return object Analyse result
	* 
	* @throws Indicate2_Exception
	*/
	public function analyseSelectionQuestion($questionId, $surveyIds, $questionContextIds) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
			
			// create soap request
			$analyseSelectionQuestionRequest = array(
				'AnalyseSelectionQuestionRequest' => array(
					'QuestionId' => $questionId,
					'SurveyIds' => array(
						'SurveyId' => $surveyIds), 
					'QuestionContextIds' => array(
						'QuestionContextId' => $questionContextIds,
				)));
	
			//Zend_Debug::dump($analyseSelectionQuestionRequest);
				
			$response = $this->_client->__soapCall('AnalyseSelectionQuestion', $analyseSelectionQuestionRequest);
			return $response;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Analyses multiple Indicate2 rating questions 
	*
	* @param array $list Combinated list of questions, contexts and surveys
	*
	* @return object Analyse result
	* 
	* @throws Indicate2_Exception
	*/
	public function analyseMultipleRatingQuestions($list, $debug = false) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
			
			// create soap request
			$analyseMultipleRatingQuestionRequest = array(
				'AnalyseMultipleRatingQuestionsRequest' => array(
					'RatingQuestionsToAnalyse' => $list
			));
	
			if ($debug) { 
				Zend_Debug::dump($analyseMultipleRatingQuestionRequest);
				exit();
			}
				
			$response = $this->_client->__soapCall('AnalyseMultipleRatingQuestions', $analyseMultipleRatingQuestionRequest);
			
			//Zend_Debug::dump($response);exit();
			
			return $response;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Analyses a Indicate2 rating question
	*
	* @param int 		$questionId 		Indicate2 question ID 
	* @param int|array 	$surveyIds 			Indicate2 survey ID's
	* @param int|array 	$questionContextIds	Indicate2 question-context ID's 
	*
	* @return object Analyse result
	* 
	* @throws Indicate2_Exception
	*/
	public function analyseRatingQuestion($questionId, $surveyIds, $questionContextIds) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
			
			// create soap request
			$analyseRatingQuestionRequest = array(
				'AnalyseRatingQuestionRequest' => array(
					'QuestionId' => $questionId,
					'SurveyIds' => array(
						'SurveyId' => $surveyIds), 
					'QuestionContextIds' => array(
						'QuestionContextId' => $questionContextIds,
				)));
	
			//Zend_Debug::dump($analyseRatingQuestionRequest);exit();
				
			$response = $this->_client->__soapCall('AnalyseRatingQuestion', $analyseRatingQuestionRequest);
			
			//Zend_Debug::dump($response);exit();
			
			return $response;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	// SCHEDULER FUNCTIONS
	
	/**
	* Adds an scheduler event for a survey
	*
	* @param int 		$surveyId 		Indicate2 survey ID 
	* @param string 	$eventType 		Type of event 
	* @param string 	$date			Date of event 
	*
	* @return int Event ID
	* 
	* @throws Indicate2_Exception
	*/
	public function addSchedulerEvent($surveyId, $eventType, $date) {
	
		try {
			// connect
			if (empty($this->_client)) $this->connect();
			
			// create soap request
			$addSchedulerEventRequest = array(
				'AddSchedulerEventRequest' => array(
					'SurveyId' => $surveyId,
					'EventType' => $eventType,
					'Date' => $date
			));
	
			//Zend_Debug::dump($addSchedulerEventRequest);exit();
			
			$response = $this->_client->__soapCall('AddSchedulerEvent', $addSchedulerEventRequest);
			return $response->EventId;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Removes an scheduler event from a survey
	*
	* @param int $eventId Indicate2 event ID 
	*
	* @return bool Result
	* 
	* @throws Indicate2_Exception
	*/
	public function deleteSchedulerEvent($eventId) {
	
		try {
			// connect
			if (empty($this->_client)) $this->connect();
			
			// create soap request
			$deleteSchedulerEventRequest = array(
				'DeleteSchedulerEventRequest' => array(
					'EventId' => $eventId,
			));
	
			$response = $this->_client->__soapCall('DeleteSchedulerEvent', $deleteSchedulerEventRequest);
			return $response;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
/**
	* Gets the schedule from a survey
	*
	* @param int $surveyId Indicate2 survey ID 
	*
	* @return stdClass SchedulerEvents
	* 
	* @throws Indicate2_Exception
	*/
	public function getSchedulerEvents($surveyId) {
	
		try {
			// connect
			if (empty($this->_client)) $this->connect();
			
			// create soap request
			$getSchedulerEventsRequest = array(
				'GetSchedulerEventsRequest' => array(
					'SurveyId' => $surveyId,
			));
	
			$response = $this->_client->__soapCall('GetSchedulerEvents', $getSchedulerEventsRequest);
			
			if (isset( $response->SchedulerEvent)) {
				$schedulerEvent = $response->SchedulerEvent;
				if (!is_array($schedulerEvent)) $schedulerEvent = array($response->SchedulerEvent);
				return $schedulerEvent;
			}
			else 
				return null;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	// CREATE CONTEXT FUNCTIONS
	
	/**
	* Creates context-question relations for create-questionnaire request
	*
	* @param int 		$questionnaireId 	Questionnaire ID (intern) 
	* @param array 	$teacherArray		Array of teacher ID's (intern)
	*
	* @return array
	* 
	* @throws Indicate2_Exception
	*/
	public function createQuestionnaireQuestionContexts($questionnaireId, $teacherArray, $blockId) {
	
		try {
		
			$questionPrefix = getQuestionPrefix();
			$contextPrefix = getContextPrefix();
			$questionnairePrefix = getQuestionnairePrefix();
			$teacherPrefix = getTeacherPrefix();
			
			$questionnaireQuestionContexts = array();
			
			// get models
			$questionnaireModel = new Questionnaire();
			$questionnaireContextQuestionModel = new Questionnaire_Context_Question();
			$contextModel = new Context();
			$questionModel = new Question();
			$teacherBlock = new Teacher_Block();
			
			$questionnaireData = $questionnaireContextQuestionModel->fetchAll($questionnairePrefix . 'id = ' . $questionnaireId, array($contextPrefix . 'order ASC', $questionPrefix . 'order ASC'));
			if ($questionnaireData->count() != 0) $questionnaireData = $questionnaireData->toArray();
			else $questionnaireData = array();
			
			$addIndex4PersonContext = 0;
			$isPersonContextAdd = false;
			
			foreach ($questionnaireData as $value) {
				
				$questionData = $questionModel->getData($value[$questionPrefix . 'id']);
				
				// set context
				if ($contextModel->isPersonContext(intval($value[$contextPrefix . 'id']))) {
					
					if (!$isPersonContextAdd) {
						
						// create personcontext from teacher
						foreach ($teacherArray as $teacherId) {
							// increase index for context order
							$questionnaireQuestionContexts[intval(intval($value[$contextPrefix . 'order']) - 1 + $addIndex4PersonContext)]['ContextId'] = $this->getIndicate2PersonContextId($teacherId);
							$teacherIds[$addIndex4PersonContext] = $teacherId;
							$addIndex4PersonContext++;
						}
						$addIndex4PersonContext--;
						// set personcontext-created flag
						$isPersonContextAdd = true;
					}
		
					for ($i = 0; $i <= $addIndex4PersonContext; $i++) {
						
						$introText = $questionModel->isPersonRatingByData($questionData) ? $teacherBlock->getTeacherNote($teacherIds[$i], $blockId) : null;
						// add questions for all personcontexts
						$questionnaireQuestionContexts[intval(intval($value[$contextPrefix . 'order']) - 1 + $i)]['QuestionnaireQuestions']['QuestionnaireQuestion'][] = array('QuestionId' => $this->getIndicate2QuestionId($value[$questionPrefix . 'id']), 'InfoText' => $questionData[$questionPrefix . 'infotext'], 'IntroText' => $introText);
					
					}
				}
				
				else {
					$questionnaireQuestionContexts[intval(intval($value[$contextPrefix . 'order']) - 1 + $addIndex4PersonContext)]['ContextId'] = $this->getIndicate2ContextId($value[$contextPrefix . 'id']);
					$questionnaireQuestionContexts[intval(intval($value[$contextPrefix . 'order']) - 1 + $addIndex4PersonContext)]['QuestionnaireQuestions']['QuestionnaireQuestion'][]  = array('QuestionId' => $this->getIndicate2QuestionId($value[$questionPrefix . 'id']), 'InfoText' => $questionData[$questionPrefix . 'infotext'], 'IntroText' => null);
				}
				
			}	
			return $questionnaireQuestionContexts;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Creates Indicate2 person context by teacher data
	*
	* @param array 			$teacherData 	Teacher data (intern)
	* @param boolean=true 	$dbAction 		Update person context id
	*
	* @return int
	* 
	* @throws Indicate2_Exception
	*/
	public function createPersonContext($teacherData, $dbAction = true) {
		
		try {
			
			if (empty($this->_client)) $this->connect();
		
			$teacherPrefix = getTeacherPrefix();
			$teacherModel = new Teacher();
			
			// create request
			$createPersonContextRequest = array(
				'CreatePersonContextRequest' => array(
					'PersonContext' => array(
						'Name' => $teacherModel->getNameByData($teacherData),
						'Description' => ($divisionLongname = $teacherModel->getDivisionByData($teacherData)) ? $divisionLongname : '',
						'Image' => $teacherData[$teacherPrefix . 'photo'],
						'PageBreak' => false
			)));
	
			// call public function to create person context
			$response = $this->_client->__soapCall('CreatePersonContext', $createPersonContextRequest);
			
			if ($dbAction) {
				// write new person context id from indicate2 to teacher in tuevalon2
				$teacherModel->update(
					array(
						$teacherPrefix . 'indicate2personcontextid' => intval($response->ContextId)
					), 
					array(
						$teacherPrefix . 'id = ' . intval($teacherData[$teacherPrefix . 'id'])
						,$teacherPrefix . 'indicate2ws = ' . $teacherModel->getAdapter()->quote(getIndicate2WSDL())
						,$teacherPrefix . 'indicate2user = ' . $teacherModel->getAdapter()->quote(getIndicate2User())
					)
				);
			}
			
			return $response->ContextId;
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Creates Indicate2 context by context data
	*
	* @param array $contextData Context data (intern)
	*
	* @return int
	* 
	* @throws Indicate2_Exception
	*/
	public function createHeader($contextData) {
		
		try {
			// connect
			if (empty($this->_client)) $this->connect();
		
			// create request
			$createHeaderRequest = array(
				'CreateHeaderRequest' => array(
					'Header' => array(
						'Name' => $contextData[getContextPrefix() . 'name'],
						'PageBreak' => false
				)));
			
			//Zend_Debug::dump($createHeaderRequest);
				
			// call public function
			$response = $this->_client->__soapCall('CreateHeader', $createHeaderRequest);

			return $response->ContextId;
		}
		catch (Exception $e) {
			//Zend_Debug::dump($e);exit();
			throw new Indicate2_Exception($e->getMessage());
		}
	}
	
	/**
	* Creates Indicate2 questionnaire and returns Indicate2 questionnaire ID
	*
	* @param int 	$questionnaireId 	Questionnaire ID (from Tuevalon2) 
	* @param array 	$teacherArray		Array of teacher ID's (intern)
	* @param string	$title				Title of Indicate2 questionnaire
	* @param int	$blockId			ID of block
	*
	* @return int Indicate2 questionnaire ID
	* 
	* @throws Indicate2_Exception
	*/
	public function createIndicate2Questionnaire($questionnaireId, $teacherArray, $title, $blockId) {
		
		try {
		
			if (empty($this->_client)) $this->connect();
		
			$questionnairePrefix = getQuestionnairePrefix();
			$questionnaireModel = new Questionnaire();
			
			if (!$questionnaireData = $questionnaireModel->getData($questionnaireId)) 
				throw new Indicate2_Exception('Keine Daten für Fragebogen vorhanden');
			
			// create request
			$createQuestionnaireRequest = array(
				'CreateQuestionnaireRequest' => array(
					'Questionnaire' => array(
						'Name' => $title,
						'QuestionnaireQuestionContexts' => array(
							'QuestionnaireQuestionContext' => $this->createQuestionnaireQuestionContexts($questionnaireId, $teacherArray, $blockId)
				))));
	
			//Zend_Debug::dump($createQuestionnaireRequest);exit();	
				
			// call create public function
			$response = $this->_client->__soapCall('CreateQuestionnaire', $createQuestionnaireRequest);
			return $response->QuestionnaireId;
			
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());	
		}
	}
	
	/**
	* Modyfies Indicate2 person context
	*
	* @param int 	$contextId 		Indicate2 person-context ID
	* @param array 	$teacherData	Teacher data (intern)
	*
	* @return boolean
	* 
	* @throws Indicate2_Exception
	*/
	public function modifyPersonContext($contextId, $teacherData) {
		
		try {
			
			if (empty($this->_client)) $this->connect();
		
			$teacherPrefix = getTeacherPrefix();
			$teacherModel = new Teacher();
			
			// create request
			$modifyPersonContextRequest = array(
				'ModifyPersonContextRequest' => array(
					'ContextId' => intval($contextId),
					'PersonContext' => array(
						'Name' => $teacherModel->getNameByData($teacherData),
						'Description' => ($divisionLongName = $teacherModel->getDivisionByData($teacherData)) ? $divisionLongName : '',
						'Image' => $teacherData[$teacherPrefix . 'photo'],
						'PageBreak' => false
			)));
			
			//Zend_Debug::dump($modifyPersonContextRequest);exit();
			
			// call public function to create person context
			$response = $this->_client->__soapCall('ModifyPersonContext', $modifyPersonContextRequest);
			
			return $response->Result;
		}
		catch (Exception $e) {
				throw new Indicate2_Exception($e->getMessage());
			}
		}

	// GET FUNCTIONS

	/**
	* Returns the questionnaire id of a survey
	*
	* @param stdClass $object
	*  
	* @return int
	*/
	public function getSurveyQuestionnaireId($object) {
		
		if (!empty($object->QuestionnaireId)) {
			return $object->QuestionnaireId;
		}
		else 
			throw new Indicate2_Exception("Keine Fragebogen-ID im Umfrage-Objekt von Indicate2 gesendet");
		
	}

	// IS SURVEY UNCTIONS
 
	/**
	* Checks if Indicate2 survey is pending
	*
	* @param string $state State of Indicate2 survey
	*
	* @return boolean
	*/
	public function isSurveyPending($state) {
		return strtolower(trim($state)) == "pending" ? true : false;
	}
	
	/**
	* Checks if Indicate2 survey is opened
	*
	* @param string $state State of Indicate2 survey
	*
	* @return boolean
	*/
	public function isSurveyOpen($state) {
		return strtolower(trim($state)) == "open" ? true : false;
	}
	
	/**
	* Checks if Indicate2 survey is closed
	*
	* @param string $state State of Indicate2 survey
	*
	* @return boolean
	*/
	public function isSurveyClose($state) {
		return strtolower(trim($state)) == "closed" ? true : false;
	}


	// EMAIL STATE FUNCTIONS

	/**
	* Checks if an email is sending
	*
	* @param string $state
	*  
	* @return boolean
	*/
	public function isEmailSending($state) {
		return strtolower(trim($state)) == "sending" ? true : false;
	}
	
	/**
	* Checks if an email is pending
	*
	* @param string $state
	*  
	* @return boolean
	*/
	public function isEmailPending($state) {
		return strtolower(trim($state)) == "pending" ? true : false;
		
	}
	
	/**
	* Checks if an email was sent
	*
	* @param string $state
	*  
	* @return boolean
	*/
	public function isEmailSent($state) {
		return strtolower(trim($state)) == "sent" ? true : false;
	}
	
	/**
	* Checks if an email is failed
	*
	* @param string $state
	*  
	* @return boolean
	*/
	public function isEmailFailed($state) {
		return strtolower(trim($state)) == "failed" ? true : false;
	}
	
	/**
	* Checks if an email is filled
	*
	* @param string $state
	*  
	* @return boolean
	*/
	public function isEmailFilled($state) {
		return strtolower(trim($state)) == "filled" ? true : false;
	}
	
	/**
	* Checks if an email is suspended
	*
	* @param string $state
	*  
	* @return boolean
	*/
	public function isEmailSuspended($state) {
		return strtolower(trim($state)) == "suspended" ? true : false;
	}
	
	/**
	* Checks if an email is deleted
	*
	* @param string $state
	*  
	* @return boolean
	*/
	public function isEmailDeleted($state) {
		return strtolower(trim($state)) == "deleted" ? true : false;
	}
	
	// GET SURVEY FUNCTIONS
	
	/**
	* Returns the ammount of unsent emails of a survey response
	*
	* @param stdClass $surveyEmailState
	*  
	* @return int
	*/
	public function getSurveyUnsentEmails($surveyEmailState) {
		return $surveyEmailState->UnsentEmails;
	}
	
	/**
	* Returns the ammount of sending emails of a survey response
	*
	* @param stdClass $surveyEmailState
	*  
	* @return int
	*/
	public function getSurveySendingEmails($surveyEmailState) {
		return $surveyEmailState->SendingEmails;
	}
	
	/**
	* Returns the ammount of sent emails of a survey response
	*
	* @param stdClass $surveyEmailState
	*  
	* @return int
	*/
	public function getSurveySentEmails($surveyEmailState) {
		return $surveyEmailState->SentEmails;
	}
	
	/**
	* Returns the ammount of failed emails of a survey response
	*
	* @param stdClass $surveyEmailState
	*  
	* @return int
	*/
	public function getSurveyFailedEmails($surveyEmailState) {
		return $surveyEmailState->FailedEmails;
	}
	
	/**
	* Returns the ammount of filled emails of a survey response
	*
	* @param stdClass $surveyEmailState
	*  
	* @return int
	*/
	public function getSurveyFilledEmails($surveyEmailState) {
		return $surveyEmailState->FilledEmails;
	}
	
	/**
	* Returns the ammount of total emails of a survey response
	*
	* @param stdClass $surveyEmailState
	*  
	* @return int
	*/
	public function getSurveyTotalEmails($surveyEmailState) {
		return $surveyEmailState->TotalEmails;
	}
	
	/**
	* Returns the return rate of emails of a survey response in rounded percentage
	*
	* @param stdClass $surveyEmailState
	*  
	* @return int
	*/
	public function getSurveyReturnRate($surveyEmailState) {
		return $surveyEmailState->ReturnRate;
	}
	
	/**
	* Returns the participant state of emails of a survey response
	*
	* @param stdClass $object
	*  
	* @return array
	*/
	public function getSurveyParticipantEmailStates($object) {
		if (isset($object->ParticipantEmailStates->ParticipantEmailState)) {
			if (!is_array($object->ParticipantEmailStates->ParticipantEmailState))
				return array($object->ParticipantEmailStates->ParticipantEmailState);
			else 
				return $object->ParticipantEmailStates->ParticipantEmailState; 
		}
		else 
			return array(); 
			
	}
	
	/**
	* Returns the start date of a survey
	*
	* @param stdClass $object
	*  
	* @return string
	*/
	public function getSurveyStartDate($object) {
		if (!empty($object->StartDate))
			return $object->StartDate;
		else
			throw new Indicate2_Exception(Indicate2_Message::NO_SURVEY_STARTDATE);
	}
	
	/**
	* Returns the creation date of a survey
	*
	* @param stdClass $object
	*  
	* @return string
	*/
	public function getSurveyCreationDate($object) {
		return $object->CreationDate;
	}
	
	/**
	* Returns the last reminder date of a survey
	*
	* @param stdClass $object
	*  
	* @return string
	*/
	public function getSurveyLastReminderDate($object) {
		return !isset($object->LastReminderDate) ? '' : $object->LastReminderDate;
	}
	
	/**
	* Returns the number of reminders of a survey
	*
	* @param stdClass $object
	*  
	* @return string
	*/
	public function getSurveyReminderCount($object) {
		return !isset($object->ReminderCount) ? '' : $object->ReminderCount;
	}
	
	/**
	* Returns the last fillout date of a survey
	*
	* @param stdClass $object
	*  
	* @return string
	*/
	public function getSurveyLastFilloutDate($object) {
		return !isset($object->LastFilloutDate) ? '' : $object->LastFilloutDate;
	}
	
	// COMMENT ANALYISIS FUNCTIONS
	
	/**
	* Returns survey ID from multiple-comment-question-analysis
	*
	* @param stcClass $analysisResult Analysis result
	*
	* @return int Survey ID	
	* 
	* @throws Indicate2_Exception
	*/
	public function getSurveyIdFromMultipleCommentAnalysisResult($analysisResult) {
		if (isset($analysisResult->Request->SurveyIds->SurveyId)) 
			return $analysisResult->Request->SurveyIds->SurveyId;
		elseif (isset($analysisResult->SurveyIds->SurveyId))
			return $analysisResult->SurveyIds->SurveyId;
		else 
			throw new Indicate2_Exception("No survey ID available");
	}
	
	/**
	* Returns context ID from multiple-comment-question-analysis
	*
	* @param stdClass $analysisResult Analysis result
	*
	* @return int Context ID	
	* 
	* @throws Indicate2_Exception
	*/
	public function getContextIdFromMultipleCommentAnalysisResult($analysisResult) {
		if (isset($analysisResult->Request->QuestionContextIds->QuestionContextId)) 
			return $analysisResult->Request->QuestionContextIds->QuestionContextId;
		elseif (isset($analysisResult->QuestionContextIds->QuestionContextId))
			return $analysisResult->QuestionContextIds->QuestionContextId;
		else 
			throw new Indicate2_Exception("No context ID available");
	}
	
	/**
	* Returns context from multiple-comment-question-analysis
	*
	* @param stcClass $analysisResult Analysis result
	*
	* @return int Context ID	
	* 
	* @throws Indicate2_Exception
	*/
	public function getContextAnswersFromMultipleCommentAnalysisResult($analysisResult) {
		if (isset($analysisResult->Context)) 
			return self::getCommentAnswers($analysisResult->Context);
		else 
			throw new Indicate2_Exception("No context available");
	}
	
	/**
	* Returns comment answer from comment analyse-result
	*
	* @param string|object $commentAnswer 
	*
	* @return string|boolean
	*/
	public function getCommentAnswer($commentAnswer) {
		
		return (is_string($commentAnswer) && !empty($commentAnswer)) ? $commentAnswer : ((isset($commentAnswer->Text)) ? $commentAnswer->Text : false); 
		
	}
	
	/**
	* Returns comment answers from comment analyse-result
	*
	* @param stdClass $commentAnswer Analyse result
	*
	* @return array
	*/
	public function getCommentAnswers($analyseResult) {
		
		$commentsArray =  array();
		if (isset($analyseResult->CommentAnswer))
			foreach ($analyseResult->CommentAnswer as $comment) {
				$comment = self::getCommentAnswer($comment);
				if ($comment) $commentsArray[] = $comment;
			}
		
		return $commentsArray;
		
	}
	
	/**
	* Returns analyse image from selection or rating analyse-result
	*
	* @param stdClass $analyseResult Analyse result 
	*
	* @return string|boolean Base64-bytesteam of PNG image	
	* 
	* @throws Indicate2_Exception
*/
	public function getAnalyseImage($analyseResult) {
		if (!empty($analyseResult->Image))
			return $analyseResult->Image;
		else 
			throw new Indicate2_Exception("Keine Bilddaten vorhanden");
	}
	
	// RATING ANALYSIS FUNCTIONS
	
	/**
	* Returns rating result from rating-question analyse-result
	*
	* @param stdClass $analyseResult Analyse result 
	*
	* @return stdClass Rating result	
	* 
	* @throws Indicate2_Exception
	*/
	public function getRatingResult($analyseResult) {
		if (!empty($analyseResult->RatingResult)) 
			return $analyseResult->RatingResult;
		else 
			throw new Indicate2_Exception("No rating result available");
	}
	
	/**
	* Returns results from multiple-rating-question-analyses
	*
	* @param stcClass $multipeAnalyseResult Analyses results 
	*
	* @return array Analysis results	
	* 
	* @throws Indicate2_Exception
	*/
	public function getMultipleRatingResults($multipeAnalyseResult) {
		if (isset($multipeAnalyseResult->AnalysisResults)) 
			return $multipeAnalyseResult->AnalysisResults;
		else 
			throw new Indicate2_Exception("No rating analysis available");
	}
	
	/**
	* Returns results from multiple-comment-question-analyses
	*
	* @param stcClass $multipeAnalyseResult Analyses results 
	*
	* @return array Analysis results	
	* 
	* @throws Indicate2_Exception
	*/
	public function getMultipleCommentResults($multipeAnalyseResult) {
		if (isset($multipeAnalyseResult->AnalysisResults)) { 
			return $multipeAnalyseResult->AnalysisResults;
		}
		else 
			throw new Indicate2_Exception("No comment results available");
	}
	
	/**
	* Returns survey ID from multiple-rating-question-analysis
	*
	* @param stcClass $analysisResult Analysis result
	*
	* @return int Survey ID	
	* 
	* @throws Indicate2_Exception
	*/
	public function getSurveyIdFromMultipleRatingAnalysisResult($analysisResult) {
		if (isset($analysisResult->Request->SurveyIds->SurveyId)) 
			return $analysisResult->Request->SurveyIds->SurveyId;
		elseif (isset($analysisResult->SurveyIds->SurveyId))
			return $analysisResult->SurveyIds->SurveyId;
		else 
			throw new Indicate2_Exception("No survey ID available");
	}
	
	/**
	* Returns context ID from multiple-rating-question-analysis
	*
	* @param stdClass $analysisResult Analysis result
	*
	* @return int Context ID	
	* 
	* @throws Indicate2_Exception
	*/
	public function getContextIdFromMultipleRatingAnalysisResult($analysisResult) {
		if (isset($analysisResult->Request->QuestionContextIds->QuestionContextId)) 
			return $analysisResult->Request->QuestionContextIds->QuestionContextId;
		elseif (isset($analysisResult->QuestionContextIds->QuestionContextId))
			return $analysisResult->QuestionContextIds->QuestionContextId;
		else 
			throw new Indicate2_Exception("No context ID available");
	}
	
	
	
	/**
	* Returns rating-meanvalue of rating result
	*
	* @param stdClass	$ratingResult 				Rating result 
	* @param int	$decimalPlaces = 2 OPTIONAL	Decimal places 
	*
	* @return double
	* 
	* @throws Indicate2_Exception
	*/
	public function getRatingMeanValue($ratingResult, $decimalPlaces = 2) {
		if (isset($ratingResult->Mean))
			return round($ratingResult->Mean, $decimalPlaces);
		else 
			throw new Indicate2_Exception("No mean value available");
	}
	
	/**
	* Returns number of ratings of rating result
	*
	* @param stdClass $ratingResult Rating result 
	*
	* @return int
	*/
	public function getRatingCount($ratingResult) {
		
		$count = 0;
		
		if (isset($ratingResult->OptionCount)) {  

			foreach ($ratingResult->OptionCount as $option) {
				$count+= $option->Count;
			}
		
		}
		return $count;
	}
	
	// TEACHER FUNCTIONS
	
	/**
	* Returns Indicate2 teacher comment-question ID
	*
	* @return int Indicate2 comment question ID
	*
	* @throws Indicate2_Exception 
	*/
	public function getTeacherCommentIndicate2QuestionId() {
	
		try {
			
			$questionPrefix = getQuestionPrefix();
			$questionModel = new Question();
		
			if ($questionData = $questionModel->getData(Question::TEACHER_COMMENT_ID)) 
				return intval($this->createCommentQuestion($questionData));
			else
				throw new Exception("Keine korrekte Kommentar-Frage angegeben");
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());	
		}
			 
	}

	/**
	* Returns Indicate2 teacher rating-question ID
	*
	* @return int
	*
	* @throws Indicate2_Exception 
	*/
	public function getTeacherRatingIndicate2QuestionId() {
	
		try {
			
			$questionPrefix = getQuestionPrefix();
			$questionModel = new Question();
		
			if ($questionData = $questionModel->getData(Question::TEACHER_RATING_ID)) 
				return intval($this->createRatingQuestion($questionData));
			else
				throw new Exception("Keine korrekte Bewertungs-Frage angegeben");
		}
		catch (Exception $e) {
			throw new Indicate2_Exception($e->getMessage());	
		}
			 
	}
	
	/**
	* Returns Indicate2 teacher comments
	*
	* @param int $teacherId	Teacher ID (intern)	
	* @param int $surveyId 	Indicate2 survey ID
	* 
	* @return array
	*
	* @throws Indicate2_Exception 
	*/
	public function getTeacherCommentsFromSurvey($teacherId, $surveyId) {
	
		try {
			$teacherModel = new Teacher();
			
			$personContextId = $teacherModel->getIndicate2PersonContextId($teacherId);
			$questionId = $this->getTeacherCommentIndicate2QuestionId();
			
			$analyseRsult = $this->analyseCommentQuestion($questionId, $surveyId, $personContextId);
			
			return $this->getCommentAnswers($analyseRsult);
			
			throw new Exception("Keine Kommentare auslesbar");
			
		}
			catch (Exception $e) {
				throw new Exception($e->getMessage());
		}
	}
	
	/**
	* Returns Indicate2 teacher rating meanvalue
	*
	* @param int $teacherId	Teacher ID (intern)	
	* @param int $surveyId 	Indicate2 survey ID
	*
	* @return int|double
	* 
	* @throws Indicate2_Exception 
	*/
	public function getTeacherRatingFromSurvey($teacherId, $surveyId) {
	
		try {
			$teacherModel = new Teacher();
			
			$personContextId = $teacherModel->getIndicate2PersonContextId($teacherId);
			$questionId = $this->getTeacherRatingIndicate2QuestionId();
			
			$analyseResult = $this->analyseRatingQuestion($questionId, $surveyId, $personContextId);
			
			//Zend_Debug::dump($analyseResult, 'Analyse');exit();
			
			return $this->getRatingMeanValue($this->getRatingResult($analyseResult));
			
		}
			catch (Exception $e) {
				throw new Exception($e->getMessage());
		}
	}
	
	/**
	* Returns number of ratings from Indicate2 teacher rating question
	*
	* @param int $teacherId	Teacher ID (intern)	
	* @param int $surveyId 	Indicate2 survey ID
	*
	* @return int
	* 
	* @throws Indicate2_Exception 
	*/
	public function getTeacherRatingCountFromSurvey($teacherId, $surveyId) {
	
		try {
			$teacherModel = new Teacher();
			
			$personContextId = $teacherModel->getIndicate2PersonContextId($teacherId);
			$questionId = $this->getTeacherRatingIndicate2QuestionId();
			
			$analyseResult = $this->analyseRatingQuestion($questionId, $surveyId, $personContextId);
			
			return $this->getRatingCount($this->getRatingResult($analyseResult));
			
			throw new Exception("Keine Bewertung auslesbar");
			
		}
			catch (Exception $e) {
				throw new Exception($e->getMessage());
		}
	}
	
	
	// for wildfire
	public function createParticipation($email,$surveyId){
			try {
			// connect
			if (empty($this->_client)) $this->connect();
		
			// create request
			$createParticipationRequest = array(
				'CreateParticipationRequest' => array(
					'SurveyId' => $surveyId,
					'EmailAddress' => $email,
			));
		
			// call function
			$response = $this->_client->__soapCall('createSurveyParticipation', $createParticipationRequest);
			
			//Zend_Debug::dump($this->_client->__getLastRequest());
			//Zend_Debug::dump($this->_client->__getLastResponse());
			//exit();
			
//			Zend_Debug::dump($response);
			return $response->Code;
			
			throw new Exception("Problem...");
			
		}
			catch (Exception $e) {
				throw new Exception($e->getMessage());
		}
	}
	
	public function getAnswerSetCount($email,$surveyIds){
			try {
			// connect
			if (empty($this->_client)) $this->connect();
		
			// create request
			$GetAnswerSetCountRequest = array(
				'GetAnswerSetCountRequest' => array(
					'SurveyIds' => $surveyIds,
					'EmailAddress' => $email,
			));
		
			// call function
			$response = $this->_client->__soapCall('getAnswerSetCount', $GetAnswerSetCountRequest);
			
			return $response->Count;
			
			throw new Exception("Problem...");
			
		}
			catch (Exception $e) {
				throw new Exception($e->getMessage());
		}
	}
	
	public function getAnswerSetForSurvey($surveyIdList, $accessCodeList, $orderList, $limit){
        try {
            // connect
            if (empty($this->_client)) $this->connect();
        
            // create request
            $getAnswerSetForSurveyRequest = array(
                'GetAnswerSetForSurveyRequest' => array(
                    'SurveyIdList' => $surveyIdList,
                    'AccessCodeList' => $accessCodeList,
                    'ConditionList' => $orderList,
                    'Limit' => $limit,
            ));
//            Zend_Debug::dump($getAnswerSetForSurveyRequest);
            // call function
            $response = $this->_client->__soapCall('getAnswerSetForSurvey', $getAnswerSetForSurveyRequest);
            
            return $response;
//            Zend_Debug::dump($response);
            
            throw new Exception("Problem...");
            
        } catch (Exception $e) {
                throw new Exception($e->getMessage());
        }
    }
    
    public function getAnswerSetForAccessCode($accessCodeList){
        try {
            // connect
            if (empty($this->_client)) $this->connect();
        
            // create request
            $getAnswerSetForAccessCodeRequest = array(
                'GetAnswerSetForAccessCodeRequest' => array(
                    'AccessCode' => $accessCodeList,
            ));
            // call function
            $response = $this->_client->__soapCall('getAnswerSetForAccessCode', $getAnswerSetForAccessCodeRequest);
            
            return $response;
            
            throw new Exception("Problem...");
            
        } catch (Exception $e) {
                throw new Exception($e->getMessage());
        }
    }
    
    public function getAnswerSetForParticipant($surveyId, $emailList){
        try {
            // connect
            if (empty($this->_client)) $this->connect();
        
            // create request
            $getAnswerSetForParticipantRequest = array(
                'GetAnswerSetForParticipantRequest' => array(
                    'SurveyId' => $surveyId,
                    'EmailList' => $emailList,
            ));
            // call function
            $response = $this->_client->__soapCall('getAnswerSetForParticipant', $getAnswerSetForParticipantRequest);
            
            return $response;
            
            throw new Exception("Problem...");
            
        } catch (Exception $e) {
                throw new Exception($e->getMessage());
        }
    }
	

}
	
class Indicate2_Exception extends Exception {
	
	public function getMessageTraced() {
		$traceArray = $this->getTrace();
		return $this->getMessage() . '(class"' . $traceArray[0]['class'] . '"@function"' . $traceArray[0]['function'] . '"@line"' . $this->getLine() . '"';
	}
}

?>