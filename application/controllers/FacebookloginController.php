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
				$db = Zend_Registry :: get('db');
				$adapter = new FacebookLoginAuthAdapter($uid, $uname);
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
