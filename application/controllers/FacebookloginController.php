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
  			$user = $facebook->getUser();
				$user_profile = $facebook->api('/me');
        
		  	if($user_profile) {
		  		
		  		$user = $user_profile["id"];
		  		if(!$user){
						$this->_helper->redirector('loginfailed','index');
		  		}

		  		$uid = $user_profile["id"];
		  		$uname = $user_profile['name'];
		  		$email = $user_profile['email'];
		  		$db = Zend_Registry :: get('db');
		  		//if state param is not null, then the value is invite code, get email from database by invite code
		  		
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
						$this->_helper->redirector('loginfailed','index');
					}
	  		}
		  }else{
		  	$this->_helper->redirector('loginfailed','index');
		  }
	}

	private	function create_password($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

	function registerAction() {

		$facebook = new Facebook(array(
		  'appId'  => FB_AKEY,
		  'secret' => FB_SKEY,
		  'authorizationRedirectUrl' => FB_REGISTER_CALLBACK_URL,
		));
		$code = $_REQUEST['code'];

  	if($code) {
			$user = $facebook->getUser();
			$user_profile = $facebook->api('/me');

	  	if($user_profile) {

	  		$user = $user_profile["id"];
	  		if(!$user){
					$this->_helper->redirector('index','index');
	  		}
	  		$uid = $user_profile['id'];
	  		$uname = $user_profile['name'];
	  		$email = $user_profile['email'];
	  		$is_invitation_code_valid = False;
	  		$db = Zend_Registry :: get('db');
	  		//if state param is not null, then the value is invite code, get email from database by invite code
	  		$invitation_code_id = $_REQUEST['state'];
	  		//邀请码ID，在菲律宾的活动中不需验证
	  		if($invitation_code_id)
	  		{
	  			$invitation_code_id = intval($invitation_code_id);
	  			$signupAuthCodeModel = new SignupAuthCode();
	  			$invitation_code =	$signupAuthCodeModel->find($invitation_code_id);
	  			if(isset($invitation_code)) {
	  				// $invitation_code = $invitation_code[0];
	  				// if ($invitation_code->id) {
		  			// 	$select1 = $db->select();
							// $select1->from("invitation_email","to");
							// $select1->where("invitation_email.signup_auth_code_id = ?",$invitation_code->id);
							// $email = $db->fetchOne($select1);
							// if(isset($email)) {
							// 	$is_invitation_code_valid = True;
							// }
		  			// }
		  			$is_invitation_code_valid = True;
	  			}
	  		}

	  		if($is_invitation_code_valid) {
	  			$consumer = $db->fetchOne("SELECT *  FROM consumer WHERE email=:email", array('email'=>$email));
	  			if($consumer) {
	  				if($consumer['facebookid'] == $uid) {

	  				}else {
	  					$consumerModel = new Consumer();
							$consumerModel.update(array("facebookid"=>$uid), array('id'=>$consumer['id']));
	  				}
	  			}else {
	  				$pass = $this->create_password();
	  				$consumerModel = new Consumer();
    				$row = $consumerModel->createRow();
    				$row->name = $uname;
    				$row->email = $email;
    				$row->password = md5($pass);
						$row->state ="ACTIVE";
						$row->facebookid = $uid;
		    		$row->save();
		    		

		    		$currentTime = date("Y-m-d H:i:s");
		  			$invitation_code_id = intval($invitation_code_id);
		  			$signupAuthCodeModel = new SignupAuthCode();
		  			$invitation_code =	$signupAuthCodeModel->find($invitation_code_id);
		  			$invitation_code = $invitation_code[0];
						$invitation_code->receiver = $row->id;
    				$invitation_code->use_date= (string)$currentTime;
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

							$stringChange = array(
										'?username?' => $uname,
										'?password?'=>$pass
										);

							$emailBody = "Dear ?username?
														<br><br>Since you signed up on the Wildfire Community Platform with your Facebook account, did you know that you can also login with your Facebook email address?
														<br><br>Follow the steps below to access your Wildfire Community Platform profile through your Facebook email address:
														<br>1. Go to http://ph.wildfire.asia/public
														<br>2. Enter your Facebook email address.
														<br>3. Enter your password. Your default password is ?password?
														<br>4. Click Login.
														<br><br>To change your password:
														<br>1. Click Edit your Profile
														<br>2. Click Change Password
														<br>3. Key in your account's default password
														<br>4. Enter your desired password and click Save
														<br><br>Please do not hesitate to contact us if you need any assistance in the future. Thank you very much and have a great day!
														<br><br>Best regards,
														<br>Wildfire Community";
														
							$emailSubject ="Your default password ";

							$emailBody = strtr($emailBody,$stringChange);
							$mail->setBodyHtml((string)$emailBody);
							$mail->setSubject($emailSubject);
							$mail->setFrom($config->smtp->friend->mail->username, "Wildfire");
							$mail->addTo($email,$uname);
							$mail->send();
	  			}
	  		}else {
	  			$this->_helper->redirector('loginfailed','index');
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
				$this->_helper->redirector('index','home');
  		}else {
  			$this->_helper->redirector('loginfailed','index');
  		}
	  }else {
	  	$this->_helper->redirector('loginfailed','index');
	  }
	}
	/*function testAction(){
		// when you sign up with facebook eamil and authcode . we launch default password  and send to you .2012-11-08
		  				
		  				$config = Zend_Registry::get('config');
							$smtpSender = new Zend_Mail_Transport_Smtp(
								$config->smtp->friend->mail->server,
								array(
									'username'=> $config->smtp->friend->mail->username,
									'password'=> $config->smtp->friend->mail->password,
									'auth'=> $config->smtp->friend->mail->auth,
									'ssl' => $config->smtp->friend->mail->ssl,
		         			'port' => $config->smtp->friend->mail->port
		         			)
							);
		  				Zend_Mail::setDefaultTransport($smtpSender);
							$mail = new Zend_Mail('utf-8');

							$stringChange = array(
										'?username?' => "testAction",
										'?password?'=>"aaaaaa"
										);

							$emailBody = "Hi ?username?
														You can login this community by your facebook login-email and default password ?password? 
														Thank You! ";
							$emailSubject ="Your default password ";

							$emailBody = strtr($emailBody,$stringChange);
							
							// $mail->addHeader('Reply-To',"liuhuazeng@gmail.com");
							$mail->setBodyText((string)$emailBody);
							$mail->setSubject($emailSubject);
							$mail->setFrom($config->smtp->friend->mail->username, "Wildfire");
							$mail->addTo("liuhuazeng@xingxinghuo.com","liuhuazeng");
// var_dump($mail);die();
							$mail->send();
							echo "he";
							$this->_helper->viewRenderer->setNoRender(true);
	}*/
}
