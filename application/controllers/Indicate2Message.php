<?php

abstract class Indicate2_Message {
	
	const NO_SERVICE = 'Caused by maintenance, the service is currently out of order';
	
	const NO_CONNECTION = 'No connection to indicate2';
	
	const NO_SURVEY_STATE = "No survey state sent by indicate2";
	
	const NO_SURVEY_STARTDATE = "No survey start date readable from indicate2";
	
	const NO_SURVEY = "No survey data sent by indicate2";
	
	const NO_SURVEY_START =  "Survey could not be started by indicate2";
	
	const NO_SURVEY_STOP = "Survey could not be stopped by indicate2";
	
	const NO_SURVEY_RESTART = "Survey could not be restarted by indicate2";
	
	const NO_UNSENT_EMAILS_REMOVED = "Unsent emails could not be removed by indicate2";
	
	const NO_EMAILS_SENT = "Emails could not be sent by indicate2";
	
	const NO_BLOCK_SURVEY = "No survey data in supervision";
	
	const NO_SURVEY_REPORT = "No report data sent by indicate2";
	
	const SURVEY_ALREADY_STARTED = "Survey was already started";
	
	const SURVEY_NOT_STARTED = "Survey was not startet yet";
	
	const SURVEY_NOT_ACTIVE = "Survey is not active";
	
	const SURVEY_NOT_CLOSED = "Survey is not closed";
	
	const SURVEY_NOT_EXISTING = "Survey does not exist";
	
	const EMAIL_ADDRESS_NOT_VALID = "No valid email address";
	
	const PARTICIPANT_ALREADY_IN_SURVEY = "Participant already takes part in survey";

	 
}

?>