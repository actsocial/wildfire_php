<?php
include_once 'sms.inc.php';
include_once 'Indicate2Connect.php';
class ForgetpasswordController extends MyController
{	
	function indexAction(){
		$this->_helper->layout->disableLayout();
//		$this->viewresetPasswordLink = Zend_Controller_Front::getInstance()->getBaseUrl();
	
	}
	
	function sendsmsAction() {
		$currentTime = date("Y-m-d H:i:s");
		$this->_helper->layout->disableLayout();
		
		if ($this->_request->isPost() && $this->_request->getParam("login_phone") != null) {
			try{
				$login_phone = $this->_request->getParam("login_phone");
				//verify email
				$consumerModel = new Consumer();
				$consumer = $consumerModel->fetchRow("login_phone = '".$login_phone."'");
				if($consumer == null){
					$this->view->phoneErr = $this->view->translate('The_phone_is_not_existed');
					return;
				}
				//generate reset password link
				$codePattern = '1234567890ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
				$signup_auth_code = '';
				for($codeCount=0; $codeCount<12; $codeCount++){
					$signup_auth_code = $signup_auth_code.$codePattern{mt_rand(0,35)};
				}	
				$resetPasswordLink = $this->view->home.'/public/forgetpassword/reset/p/'.$signup_auth_code;
						
				//save link into DB
				$tomorrow  = mktime(date("H"), date("i"), date("s"), date("m"), date("d")+1, date("Y"));
				$expire_date = date("Y-m-d H:i:s",$tomorrow); 
				
				$temporaryLinkModel = new TemporaryLink();
				$temporaryLink = array("link" => $resetPasswordLink,
										"login_phone" =>$login_phone,
										"expire_date" =>$expire_date);
				$temporaryLink_id = $temporaryLinkModel->insert($temporaryLink);
				
				//send sms
				$newclient=new SMS();
				$mobile = $login_phone;
				$message = $this->view->translate('Forget_Password_SMS').$signup_auth_code;
				$time = $currentTime;
				$apitype = 2; // $apitype 通道选择 0：默认通道； 2：通道2； 3：即时通道；
				$msg = iconv("UTF-8","GB2312",$message);
				
				$respxml=$newclient->sendSMS($mobile, $msg, $time, $apitype);

                // crypt the login_phone, added by ZHL on 2011-11-25
				$this->view->crypt_login_phone = substr($login_phone,0,3)."*****".substr($login_phone,8,3);
			}catch(Exception $e){
				//roll back...
				$this->view->phoneErr =  $this->view->translate('Send_fail_Try_Again');
			}		
		}else{
			$this->view->phoneErr = $this->view->translate('The_phone_is_not_existed');
		}

                // sms has been sent
		// $this->_helper->redirector('reset', 'forgetpassword');
	}
	
	function sendemailAction(){
		$this->_helper->layout->disableLayout();
		
		if ($this->_request->isPost() && $this->_request->getParam("email") != null) {
			try{
				$email = $this->_request->getParam("email");
				//verify email
				$consumerModel = new Consumer();
				$consumer = $consumerModel->fetchRow("email = '".$email."'");
				if($consumer == null){
					$this->view->emailErr = $this->view->translate('The_email_is_not_existed');
					return;
				}
				//generate reset password link
				$codePattern = '1234567890ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
				$signup_auth_code = '';
				for($codeCount=0; $codeCount<12; $codeCount++){
					$signup_auth_code = $signup_auth_code.$codePattern{mt_rand(0,35)};
				}	
//				$resetPasswordLink = Zend_Controller_Front::getInstance()->getBaseUrl().'/forgetpassword/reset/p/'.$signup_auth_code;
				$resetPasswordLink = $this->view->home.'/public/forgetpassword/reset/p/'.$signup_auth_code;
						
				//save link into DB
				$tomorrow  = mktime(date("H"), date("i"), date("s"), date("m"), date("d")+1, date("Y"));
				$expire_date = date("Y-m-d H:i:s",$tomorrow); 
				
				$temporaryLinkModel = new TemporaryLink();
				$temporaryLink = array("link" => $resetPasswordLink,
										"email" =>$email,
										"expire_date" =>$expire_date);
				$temporaryLink_id = $temporaryLinkModel->insert($temporaryLink);
				
				//send mail
				$emailSubject = $this->view->translate('Reset_Your_Wildfire_Password_subject');
				$emailBody = $this->view->translate('Reset_Your_Wildfire_Password_body');
				$stringChange = array(
					"?RESETPASSWORDLINK?" => $resetPasswordLink
					);
				$emailBody = strtr($emailBody,$stringChange);
	
				$config = Zend_Registry::get('config');
				$smtpSender = new Zend_Mail_Transport_Smtp(
								$config->smtp->forgetpassword->mail->server,
								array(
									'username'=> $config->smtp->forgetpassword->mail->username,
									'password'=> $config->smtp->forgetpassword->mail->password,
									'auth'=> $config->smtp->forgetpassword->mail->auth,
									'ssl' => $config->smtp->forgetpassword->mail->ssl,
			               			'port' => $config->smtp->forgetpassword->mail->port));
//												$smtpSender = new Zend_Mail_Transport_Smtp(
//																			'smtp.163.com',array(
//																			'username'=>'yun_simon@163.com',
//																			'password'=>'19990402',
//																			'auth'=>'login'));
				Zend_Mail::setDefaultTransport($smtpSender);
				$mail = new Zend_Mail('utf-8');
				$langNamespace = new Zend_Session_Namespace('Lang');
				if($langNamespace->lang == 'en' || $langNamespace->lang == 'EN'){
					$mail->setSubject($emailSubject);
				}else{
					$mail->setSubject("=?UTF-8?B?".base64_encode($emailSubject)."?=");					
				}
				$mail->setBodyText($emailBody);
				$mail->setFrom($config->smtp->forgetpassword->mail->username,$this->view->translate('Wildfire'));
//				$mail->setFrom('yun_simon@163.com','yun_simon');
				$mail->addTo($email);
				$mail->send();
				$this->view->email = $email;
			}catch(Exception $e){
				//roll back...
				Zend_Debug::dump($e);
				$this->view->emailErr =  $this->view->translate('Send_fail_Try_Again');
				
			}	
			
		}else{
			$this->view->emailErr = $this->view->translate('The_email_is_not_existed');
		}
	}
	
	
	function resetAction(){
                $p = $this->_request->getParam("p");
                $this->view->p = $p;
		$this->_helper->layout->disableLayout();
		
	}
	
	function resetpasswordAction(){
		$this->_helper->layout->disableLayout();
		$p = $this->_request->getParam("p");
		$this->view->p = $p;
		if ($this->_request->isPost()){
			$newpassword = $this->_request->getParam("newpassword");
			$confirm = $this->_request->getParam("confirm");
			if($newpassword != $confirm || strlen($newpassword) < 6 || strlen($confirm) < 6){
				$this->view->errMessage = $this->view->translate('Fail_New_password_is_incorrect!');
				$this->view->p = $p;
			}else{
				$temporaryLinkModel = new TemporaryLink();
				$currentTime = date("Y-m-d H:i:s");
				$temporaryLink = $temporaryLinkModel->fetchRow("link like '%".$p."%' and expire_date >= '".$currentTime."'");
				$email = $temporaryLink->email;
				$login_phone = $temporaryLink->login_phone;
				//not delete $temporaryLink in DB
				if($email != null) {
					$consumerModel = new Consumer();
					$consumer = $consumerModel->fetchRow("email = '".$email."'");
					$consumer->password = md5($newpassword);
					$consumer->save();
					$this->view->showMessage = $this->view->translate('You_have_reset_the_password!');
				} else if($login_phone != null) {
					$consumerModel = new Consumer();
					$consumer = $consumerModel->fetchRow("login_phone = '".$login_phone."'");
					$consumer->password = md5($newpassword);
					$consumer->save();
					$this->view->showMessage = $this->view->translate('You_have_reset_the_password!');
				} else {
					$this->view->errMessage = $this->view->translate('Your_reset_passwrod_link');
				}

			}
		}
//		Zend_Debug::dump($p);
	}
}