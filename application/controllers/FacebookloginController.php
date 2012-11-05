<?php
require 'facebook-php-sdk/facebook.php';
require_once APPLICATION_PATH . '/models/Log.php';
require 'facebook-php-sdk/FacebookLoginAuthAdaptor.php';
include_once( 'facebook-php-sdk/facebookconfig.php' );

class FacebookloginController extends MyController {
	function indexAction() {
		$facebook = new Facebook(array(
		  'appId'  => FB_AKEY,
		  'secret' => FB_SKEY,
		  'authorizationRedirectUrl' => FB_CALLBACK_URL,
		));

		$code = $_REQUEST['code'];
  	if($code) {
	  	$token = $facebook->getAccessTokenFromCode($code, FB_CALLBACK_URL);
	  	if($token) {
	  		$facebook->setAccessToken($token);
	  		$user = $facebook->getUserInfoFromAccessToken($params = array('access_token' => $token));
//	  		var_dump($user);die();
	  		if(!$user){
//	  			$this->first();
				$this->_helper->redirector('index','index');
	  		}
	  		$uid = $user['id'];
	  		$uname = $user['name'];
	  		$email = $user['email'];
	  		$db = Zend_Registry :: get('db');
	  		//if state param is not null, then the value is invite code, get email from database by invite code
	  		$invitation_code_id = $_REQUEST['state'];
	  		if($invitation_code_id)
	  		{
	  			$invitation_code_id = intval($invitation_code_id);
	  			$signupAuthCodeModel = new SignupAuthCode();
	  			$invitation_code =	$signupAuthCodeModel->find($invitation_code_id);
	  			if(isset($invitation_code)) {
	  				$invitation_code = $invitation_code[0];
	  				if ($invitation_code->id) {
		  				$select1 = $db->select();
							$select1->from("invitation_email","to");
							$select1->where("invitation_email.signup_auth_code_id = ?",$invitation_code->id);
							$email = $db->fetchOne($select1);
		  			}
	  			}
	  		}
				
				$adapter = new FacebookLoginAuthAdaptor($uid, $uname,$email);
				$auth = Zend_Auth :: getInstance();
				$result = $auth->authenticate($adapter);
				$consumerModel = new Consumer();
				$consumer_id = $db->fetchOne("SELECT id FROM consumer WHERE facebookid = :temp and state='ACTIVE'", array (
								'temp' => $uid
				));
				$consumer = $consumerModel->find($consumer_id)->current();
				if($result->isValid()) {
					$authNamespace = new Zend_Session_Namespace('Zend_Auth');
					$authNamespace->user = $consumer;
					$authNamespace->role = 'consumer';
					$logModel = new Log();
					$logId = $logModel->insert(array (
									'consumer_id' => $consumer->id,
									'date' => date("Y-m-d H:i:s"),
									'event' => 'LOGIN'
					));
					$this->_helper->redirector('index','index');
				}else {
					$this->first();
				}
  		}
	  }
	}

	// function callbackAction() {
		
	// }
}
