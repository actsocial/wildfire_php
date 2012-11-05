<?php
require 'facebook-php-sdk/facebook.php';
include_once( 'facebook-php-sdk/facebookconfig.php' );
include_once('models/SignupAuthCode.php');

class RegisterController extends MyController
{
	function registerAction()
	{
		$this->view->title = "Register New Account";
		$this->view->messages = $this->_flashMessenger->getMessages();
		$this->_helper->layout->disableLayout();
				
		$lang = $this->_request->getParam('lang');
		if (isset($lang)){
			$langNamespace = new Zend_Session_Namespace('Lang');
			$langNamespace->lang =$lang;
			$this->_helper->redirector->gotoSimple('register','register',null,
													array('a'=>$this->_request->getParam('a'),
													'i'=>$this->_request->getParam('i')
													));
		}
		
		$db = Zend_Registry::get('db');
		$currentTime = date("Y-m-d H:i:s");

		$loginform = new LoginForm();
		$this->view->form =$loginform;

		$facebook = new Facebook(array(
		  'appId'  => FB_AKEY,
		  'secret' => FB_SKEY,
		  'authorizationRedirectUrl' => FB_CALLBACK_URL,
		));
		$this->view->facebook_login_url = $facebook->getLoginUrl(array('scope' => 'email'));

		$form = new RegisterForm();
		$form->setAttrib('id', 'registerForm');
		$this->view->registerForm = $form;
		$signupAuthCodeModel = new SignupAuthCode();
		
		$auth_code = $this->_request->getParam('a');
		// var_dump($auth_code);die();
		if ($auth_code){
			$form->auth_code->setValue($auth_code);			
			$code =	$signupAuthCodeModel->fetchRow("use_date is null and auth_code = '".$auth_code."'");
		}
		$this->view->auto_code = $auth_code ;

		// auto-fill code and email address
		if (isset($code) && $code->id){
			$this->view->codeId = $code->id;
			$select1 = $db->select();
			$select1->from("invitation_email","to");
			$select1->where("invitation_email.signup_auth_code_id = ?",$code->id);
			$toEmail = $db->fetchOne($select1);
			$form->registerEmail->setValue($toEmail);

			$this->view->facebook_login_url = $this->view->facebook_login_url."&state=".$code->id

			$code->view_date = $currentTime;
			$code->save();
		}

		//public link
		$invite_code = $this->_request->getParam('i');
		if ($invite_code){
			$code2 = $signupAuthCodeModel->fetchRow("public_signup_link = true and auth_code = '".$invite_code."'");
			$publicLinkValid = false;
			if (isset($code2)){
				$select2 = $db->select();
				$select2->from('signup_auth_code','count(*)')
				->where('use_date>date_sub(now(),interval 1 day)')
				->where('sender ='.$code2->sender)
				->where('source = "PUBLIC_LINK"')
				->where('receiver is not null');
				$registered = $db->fetchOne($select2);
				if (intval($registered)<100){
					$publicLinkValid = true;
				}
			}
		}
		
		if (isset($code2)){
			if ($publicLinkValid){
				$codePattern = '1234567890ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
				$generatedCode = '';
				for($codeCount=0; $codeCount<12; $codeCount++){
					$generatedCode = $generatedCode.$codePattern{mt_rand(0,35)};
				}
				$signupAuthCode = $signupAuthCodeModel->createRow();
				$signupAuthCode->auth_code = $generatedCode;
				$signupAuthCode->create_date = $currentTime;
				$signupAuthCode->sender = $code2->sender;
				$signupAuthCode->source = 'PUBLIC_LINK';
				$signupAuthCode->auto_invitation = $code2->auto_invitation;
				$signupAuthCode->save();
				
				$form->auth_code->setValue($generatedCode);
			}else{
				$this->_flashMessenger->addMessage($this->view->translate('Sorry_This_register_link_has_been_overused'));
                                $this->_helper->redirector('register','register');
			}
		}
		
		if ($this->_request->isPost()) {
			$this->view->registered = 0;
			$formData = $this->_request->getPost();
			if ($form->isValid($formData)) {

				$db = Zend_Registry::get('db');

				if ($form->getValue('registerPassword') == $form->getValue('repeat')) {
					//2011-04-01 ham register modification
					
		    		if(trim($form->getValue('auth_code')) == ''){
		    			//check duplicated email
		    			
		    			$result = $db->fetchOne(
	    				"SELECT COUNT(*) FROM consumer WHERE email = :temp",
		    			array('temp' => $form->getValue('registerEmail'))
		    			);
		    			
		    			//check duplicated phone
		    			$phone_result = $db->fetchOne(
	    				"SELECT COUNT(*) FROM consumer WHERE login_phone = :temp",
		    			                      array('temp' => $form->getValue('loginPhone'))
		    			);
                        //var_dump($result);die;
		    			if($result > 0) {
		    				$this->view->errMessage = $this->view->translate('Register_err') . $form->getValue('registerEmail') . $this->view->translate('Register_email_is_invalid');
		    			} else if($phone_result > 0){
		    			    $this->view->errMessage = $this->view->translate('Register_err') . $form->getValue('loginPhone') . $this->view->translate('Register_phone_is_invalid');
		    			} else {
		    				$currentTime = date("Y-m-d H:i:s");
	    				
		    				
		    				$email = $form->getValue('registerEmail');
							//generate enable account  link
							$codePattern = '1234567890ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
							$active_code = '';
							for($codeCount=0; $codeCount<12; $codeCount++){
								$active_code = $active_code.$codePattern{mt_rand(0,35)};
							}							
							$activeLink = $this->view->home.'/public/register/activate/p/'.$active_code;
							//save link into DB
							$tomorrow  = mktime(date("H"), date("i"), date("s"), date("m"), date("d")+1, date("Y"));
							$expire_date = date("Y-m-d H:i:s",$tomorrow); 
							$temporaryLinkModel = new TemporaryLink();
							$temporaryLink = array("link" => $activeLink,
													"email" =>$email,
													"expire_date" =>$expire_date);
							$temporaryLink_id = $temporaryLinkModel->insert($temporaryLink);
							//send mail
							$emailSubject = $this->view->translate('ENABLE_ACCOUNT_subject');
							$emailBody    = $this->view->translate('ENABLE_ACCOUNT_body');
							$stringChange = array(
											       "?ENABLEACCOUNTLINK?" => $activeLink
							                );              
							                
                                                        $emailBody = strtr($emailBody,$stringChange);
							$config = Zend_Registry::get ( 'config' );
							$smtpSender = new Zend_Mail_Transport_Smtp ( $config->smtp->invitation->mail->server, array ('username' => $config->smtp->invitation->mail->username, 'password' => $config->smtp->invitation->mail->password, 'auth' => $config->smtp->invitation->mail->auth, 'ssl' => $config->smtp->invitation->mail->ssl, 'port' => $config->smtp->invitation->mail->port ) );
							//				$smtpSender = new Zend_Mail_Transport_Smtp(
							//							'smtp.163.com',array(
							//							'username'=>'yun_simon@163.com',
							//							'password'=>'19990402',
							//							'auth'=>'login'));
							Zend_Mail::setDefaultTransport ( $smtpSender );
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

		    				// save new consumer
		    				$consumerModel = new Consumer();
		    				$row = $consumerModel->createRow();
		    				$row->name = $form->getValue('name');
		    				$row->email = $form->getValue('registerEmail');
		    				$row->login_phone = $form->getValue('loginPhone');
		    				$row->password = md5($form->getValue('registerPassword'));
		    				$row->save();
		    				
		    				$this->view->registered = 1;
		    			}	
		    		//2011-04-01 ham register modification		    			
		    		}else{
		    		// verify auth code
					$codeModel = new SignupAuthCode();
					$code = $codeModel->fetchRow(
		    					"auth_code='".$form->getValue('auth_code')."' and use_date is null"
		    		);
		    		
		    		if ($code!= NULL){
		    			//check pest
		    			if($code->sender != null){
		    				$consumerModel = new Consumer();
		    				$consumer = $consumerModel->fetchRow("id = ".$code->sender);
		    				if($consumer != null && $consumer->pest == '1'){
		    					return;
		    				}
		    			}
		    			//check duplicated email
		    			$result = $db->fetchOne(
	    				"SELECT COUNT(*) FROM consumer WHERE email = :temp",
		    			array('temp' => $form->getValue('registerEmail'))
		    			);
		    			
		    			//check duplicated phone
		    			$phone_result = $db->fetchOne(
	    				"SELECT COUNT(*) FROM consumer WHERE login_phone = :temp",
		    			array('temp' => $form->getValue('loginPhone'))
		    			);

		    			if($result > 0) {
		    				$this->view->errMessage = $this->view->translate('Register_err') . $form->getValue('registerEmail') . $this->view->translate('Register_email_is_invalid');
		    			} else if($phone_result > 0){
		    			    $this->view->errMessage = $this->view->translate('Register_err') . $form->getValue('loginPhone') . $this->view->translate('Register_phone_is_invalid');
		    			} else {
		    				$currentTime = date("Y-m-d H:i:s");
		    				//2011-04-02 ham.bao add the logic of activating the account
		    				$email = $form->getValue('registerEmail');
							//generate enable account  link
							$codePattern = '1234567890ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
							$active_code = '';
							for($codeCount=0; $codeCount<12; $codeCount++){
								$active_code = $active_code.$codePattern{mt_rand(0,35)};
							}							
							$activeLink = $this->view->home.'/public/register/activate/p/'.$active_code;
							//save link into DB
							$tomorrow  = mktime(date("H"), date("i"), date("s"), date("m"), date("d")+1, date("Y"));
							$expire_date = date("Y-m-d H:i:s",$tomorrow); 
							$temporaryLinkModel = new TemporaryLink();
							$temporaryLink = array("link" => $activeLink,
													"email" =>$email,
													"expire_date" =>$expire_date);
							$temporaryLink_id = $temporaryLinkModel->insert($temporaryLink);
							//send mail
								
		    				//2011-04-02 ham.bao add the logic of activating the account
		    				
		    				// save new consumer
		    				$consumerModel = new Consumer();
		    				$row = $consumerModel->createRow();
		    				$row->name = $form->getValue('name');
		    				$row->email = $form->getValue('registerEmail');
		    				$row->login_phone = $form->getValue('loginPhone');
		    				$row->password = md5($form->getValue('registerPassword'));
							$row->state ="ACTIVE";
		    				$row->save();

		    				//expire the auth_code
		    				$code->receiver = $row->id;
		    				$code->use_date= $currentTime;
		    				$code->save();
		    				 
		    				//add points for code sender
//		    				if (!empty($code->sender)&& $code->sender!=""){
//			    				$pointRecordModel = new RewardPointTransactionRecord();
//			    				$point = $pointRecordModel->createRow();
//			    				$point->consumer_id = $code->sender;
//			    				$point->transaction_id = 2;
//			    				$point->date = $currentTime;
//			    				$point->point_amount = 5;
//			    				$point->save();
//		    				}
		    				// send auto intivitaion
		    				if (!empty($code->auto_invitation) && $code->auto_invitation!=0){
		    					$campaignInvitationModel = new CampaignInvitation();
		    					$ci = $campaignInvitationModel->createRow();
		    					$ci->consumer_id = $row->id;
		    					$ci->campaign_id = $code->auto_invitation;
		    					$ci->create_date = $currentTime;
		    					$ci->state = "NEW";
		    					$ci->save();
		    				}
		    				
		    				$this->view->registered = 1;

		    				// Login Automatically
		    				$authAdapter = new Zend_Auth_Adapter_DbTable($db);
		    				$authAdapter->setTableName('consumer');
		    				$authAdapter->setIdentityColumn('email');
		    				$authAdapter->setCredentialColumn('password');

		    				$authAdapter->setIdentity($form->getValue('registerEmail'));
		    				$authAdapter->setCredential(md5($form->getValue('registerPassword')));
		    				$auth = Zend_Auth::getInstance();
		    				$auth->authenticate($authAdapter);
		    				 
		    				$authNamespace = new Zend_Session_Namespace('Zend_Auth');
		    				$authNamespace->user =$row;
		    				$this->_flashMessenger->addMessage('Welcome!');
		    				$this->_helper->redirector('index','home');
		    			}
		    		}else{	    			
		    			$this->view->errMessage = $this->view->translate('Register_err') . $this->view->translate('Register_authcode_is_invalid');
		    		}		    			
		    	  }
				} else {
					$this->view->errMessage = $this->view->translate('Register_err') . $this->view->translate('Register_repeat_password_is_error');
				}
			} else {
				$form->populate($formData);
			}
		}

	}

	function verifyAction(){
		$currentTime = date("Y-m-d H:i:s");

		$request = $this->getRequest();
		$auth_code = $this->_request->getParam('auth_code');
		$this->_helper->layout->disableLayout();

		$db = Zend_Registry::get('db');
		$signupAuthCodeModel = new SignupAuthCode();
		$code =	$signupAuthCodeModel->fetchRow("use_date is null and auth_code = '".$auth_code."'");

		if ($code && $code->id){
			$this->view->codeId = $code->id;

			$code->view_date = $currentTime;
			$code->save();
		}

	}
	
	function sendenableemailAction(){
		$this->_helper->layout->disableLayout();
		if ($this->_request->isPost()){
    			$currentTime = date("Y-m-d H:i:s");
    			$email = $this->_request->getParam('email');
    			$temporaryLinkModel = new TemporaryLink();
    			$consumerModel = new Consumer();
    			$consumer = $consumerModel->fetchRow("email ='$email' and state ='ACTIVE'");
		    	if(count($consumer)){
    				$this->view->message = $this->view->translate('Active_Email_hint');
    				return;
    			}
    			$enableLink = $temporaryLinkModel->fetchRow(" email like '%$email%' and link like '%activate%'");
    			if(!count($enableLink)){
    				$this->view->message = $this->view->translate('No_Registered_email');
    				return;
    			}

				//save link into DB
                        $tomorrow  = mktime(date("H"), date("i"), date("s"), date("m"), date("d")+1, date("Y"));
                        $expire_date = date("Y-m-d H:i:s",$tomorrow);
                        $enableLink->expire_date = $expire_date;
                        $temporaryLinkModel->update(array('expire_date'=> $expire_date), 'id = '.$enableLink->id);

                        //send mail
                        $emailSubject = $this->view->translate('ENABLE_ACCOUNT_subject');
                        $emailBody    = $this->view->translate('ENABLE_ACCOUNT_body');
                        $stringChange = array(
                                                               "?ENABLEACCOUNTLINK?" => $enableLink->link
                                        );
				                
                        $emailBody = strtr($emailBody,$stringChange);
                        $config = Zend_Registry::get ( 'config' );
                        $smtpSender = new Zend_Mail_Transport_Smtp ( $config->smtp->invitation->mail->server, array ('username' => $config->smtp->invitation->mail->username, 'password' => $config->smtp->invitation->mail->password, 'auth' => $config->smtp->invitation->mail->auth, 'ssl' => $config->smtp->invitation->mail->ssl, 'port' => $config->smtp->invitation->mail->port ) );
				//											 $smtpSender = new Zend_Mail_Transport_Smtp(
				//											 'smtp.163.com',array(
				//											 'username'=>'yun_simon@163.com',
				//											 'password'=>'19990402',
				//											 'auth'=>'login'));
                        Zend_Mail::setDefaultTransport ( $smtpSender );
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
                //Zend_Debug::dump($emailBody);
                        $this->view->message = $this->view->translate('Active_your_email');
		}

		
		
	}

	function activateAction(){
		$this->_helper->layout->disableLayout();
		$activateCode = $this->_request->getParam('p');
		$message = '';
		
		$activeLink = $this->view->home.'/public/register/activate/p/'.$activateCode;
		$temporaryLink = new TemporaryLink();
                $temporaryLinkData = $temporaryLink->fetchRow('link like "%'.$activeLink.'%"');
        
                $conumserModel = new Consumer();
                $consumerData  = $conumserModel->fetchRow('email like "%'.$temporaryLinkData->email.'%"');

                if($consumerData->state == 'ACTIVE'){
                        $message = $this->view->translate('Has_actived');
                }elseif($temporaryLinkData->expire_date < date("Y-m-d H:i:s")){
                        $message = $this->view->translate('OutOfDate_register');
                }else{
                        $consumerData->state= 'ACTIVE';
                        $consumerData->save();
                }
                $this->view->message = $message;
	
	}
	
	
}