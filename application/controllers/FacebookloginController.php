<?php
require 'facebook-php-sdk/facebook.php';
require_once APPLICATION_PATH . '/models/Log.php';
require 'facebook-php-sdk/FacebookLoginAuthAdaptor.php';
include_once( 'facebook-php-sdk/facebookconfig.php' );
require_once 'Zend/Mail/Transport/Smtp.php';
require_once 'Zend/Mail.php';

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
		  		// var_dump($user);die();
		  		if(!$user){
	//	  			$this->first();
					$this->_helper->redirector('loginfailed','index');
		  		}
		  		$uid = $user['id'];
		  		$uname = $user['name'];
		  		$email = $user['email'];
		  		$db = Zend_Registry :: get('db');
		  		//if state param is not null, then the value is invite code, get email from database by invite code
		  		/*$invitation_code_id = $_REQUEST['state'];
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
		  		}*/
					
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
						// $this->first();
						$this->_helper->redirector('loginfailed','index');
					}
	  		}
		  }else{
		  	$this->_helper->redirector('loginfailed','index');
		  }
	}

	function create_password()
	{
	    $randpwd = '';
	    for ($i = 0; $i < 6; $i++)
	    {
	        $randpwd .= chr(mt_rand(0, 9));
	    }
	    return $randpwd;
	}

	function registerAction() {
		$facebook = new Facebook(array(
		  'appId'  => FB_AKEY,
		  'secret' => FB_SKEY,
		  'authorizationRedirectUrl' => FB_REGISTER_CALLBACK_URL,
		));

		$code = $_REQUEST['code'];
  	if($code) {
	  	$token = $facebook->getAccessTokenFromCode($code, FB_REGISTER_CALLBACK_URL);
	  	if($token) {
	  		$facebook->setAccessToken($token);
	  		$user = $facebook->getUserInfoFromAccessToken($params = array('access_token' => $token));
	  		if(!$user){
					$this->_helper->redirector('index','index');
	  		}
	  		$uid = $user['id'];
	  		$uname = $user['name'];
	  		$email = '';
	  		$is_invitation_code_valid = False;
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
							if(isset($email)) {
								$is_invitation_code_valid = True;
							}
		  			}
	  			}
	  		}

	  		if($is_invitation_code_valid) {
	  			$consumer = $db->fetchOne("SELECT *  FROM consumer WHERE email=:email", array('email'=>$email));
	  			if($consumer) {
	  				if($consumer['facebookid'] == $uid) {

	  				}else {
	  					$consumerModel = new Consumer();
							$consumerModel.update(array("facebookid"=>$this->facebookid), array('id'=>$consumer['id']));
	  				}
	  			}else {
	  				$pass = $this->create_password();
	  				$consumerModel = new Consumer();

						// $consumerModel->insert(array('name'=>$this->_facebookname,'password'=>md5($pass),'email'=>$this->_facebookemail,'facebookid'=>$this->_facebookid,'state'=>'ACTIVE'));		
    				$row = $consumerModel->createRow();
    				$row->name = $this->_facebookname;
    				$row->email = $this->_facebookemail;
    				// $row->login_phone = $form->getValue('loginPhone');
    				$row->password = md5($pass);
						$row->state ="ACTIVE";
						$row->facebookid = $this->_facebookid ;
		    		$row->save();

		    		$currentTime = date("Y-m-d H:i:s");

		  			$invitation_code_id = intval($invitation_code_id);
		  			$signupAuthCodeModel = new SignupAuthCode();
		  			$invitation_code =	$signupAuthCodeModel->find($invitation_code_id);
						$invitation_code->receiver = $row->id;
    				$invitation_code->use_date= $currentTime;
    				$invitation_code->save();

		  			if (!empty($invitation_code->auto_invitation) && $invitation_code->auto_invitation!=0){
		    					$campaignInvitationModel = new CampaignInvitation();
		    					$ci = $campaignInvitationModel->createRow();
		    					$ci->consumer_id = $row->id;
		    					$ci->campaign_id = $invitation_code->auto_invitation;
		    					$ci->create_date = $currentTime;
		    					$ci->state = "NEW";
		    					$ci->save();
		    				}

		    		// when you sign up with facebook eamil and authcode . we launch default password  and send to you .2012-11-08
		  				$config = Zend_Registry::get('config');
							$smtpSender = new Zend_Mail_Transport_Smtp(
							$config->smtp->friend->mail->server,
							array(
								'username'=> $config->smtp->friend->mail->username,
								'password'=> $config->smtp->friend->mail->password,
								'auth'=> $config->smtp->friend->mail->auth,
								'ssl' => $config->smtp->friend->mail->ssl,
	         			'port' => $config->smtp->friend->mail->port));
		  				Zend_Mail::setDefaultTransport($smtpSender);
							$mail = new Zend_Mail('utf-8');
							// $langNamespace = new Zend_Session_Namespace('Lang');
							$stringChange = array(
										'?USERNAME?' => $this->_facebookname,
										// '?EMAIL?' =>$this->_facebookemail,
										'?password?'=>$pass
										// '?MESSAGE?' => $form->getValue('message'),
										// '?AUTHCODE?' => (string)$signup_auth_code
										);

							$emailBody = "Hi ?username?
														You can login this community by your facebook login-email and default password ?password? 
														Thank You! ";
							$emailSubject ="Your default password ";

							$emailBody = strtr($emailBody,$stringChange);
							$mail->addHeader('Reply-To', $consumer->email);
							$mail->setBodyText((string)$emailBody);
							$mail->setSubject($emailSubject);
							$mail->setFrom($config->smtp->friend->mail->username, "Wildfire");
							//	$mail->addHeader('Reply-To', $consumer->email);
							//	$mail->setFrom('yun_simon@163.com',$this->view->translate('Wildfire'));
							$mail->addTo($this->_facebookemail);
							$mail->send();
	  			}
	  		}else {
	  			$this->_helper->redirector('index','index');
	  		}
				
				$consumerModel = new Consumer();
				$consumer_id = $db->fetchOne("SELECT id FROM consumer WHERE facebookid = :temp", array (
								'temp' => $uid
				));
				$consumer = $consumerModel->find($consumer_id)->current();
				$adapter = new FacebookLoginAuthAdaptor($uid,$uname,$email);
				$auth = Zend_Auth :: getInstance();
				$result = $auth->authenticate($adapter);
				if($result->isValid()){
					$authNamespace = new Zend_Session_Namespace('Zend_Auth');
					$authNamespace->user = $consumer;
					$authNamespace->role = 'consumer';
					$logModel = new Log();
					$logId = $logModel->insert(array (
									'consumer_id' => $consumer->id,
									'date' => date("Y-m-d H:i:s"),
									'event' => 'LOGIN'
					));
				}
				$this->_helper->redirector('home','index');
  		}else {
  			$this->_helper->redirector('index','index');
  		}
	  }else {
	  	$this->_helper->redirector('index','index');
	  }
	}
}
