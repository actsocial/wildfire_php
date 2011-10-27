<?php
/*
 * Common Controller is for follwing features:
 *  - common tools used across the site
 */

include_once 'Indicate2Connect.php';

class CommonController extends MyController
{

	function getaccesscodeAction() {
		$surveyId = (int)$this->_request->getParam('surveyId');
		$consumer = $this->_currentUser;
//		$email = $this->_request->getParam('email');
		$indicate2Connect = new Indicate2_Connect();
		$accesscode = $indicate2Connect->createParticipation($consumer->email,$surveyId);
		$this->_helper->layout->disableLayout();
		$this->_helper->json($accesscode);
	}
	
	function admingetaccesscodeAction() {
		$surveyId = (int)$this->_request->getParam('surveyId');
		//$consumer = $this->_currentUser;
		$email = $this->_request->getParam('email');
		$indicate2Connect = new Indicate2_Connect();
		$accesscode = $indicate2Connect->createParticipation($email,$surveyId);
		$this->_helper->layout->disableLayout();
		$this->_helper->json($accesscode);
	}
}
