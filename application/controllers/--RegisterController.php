<?php

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

		$form = new RegisterForm();
		$this->view->registerForm = $form;
		$signupAuthCodeModel = new SignupAuthCode();
		
		$auth_code = $this->_request->getParam('a');
		
		if ($auth_code){
			$form->auth_code->setValue($auth_code);			
			$code =	$signupAuthCodeModel->fetchRow("use_date is null and auth_code = '".$auth_code."'");
		}

		// auto-fill code and email address
		if (isset($code) && $code->id){
			$this->view->codeId = $code->id;
			$select1 = $db->select();
			$select1->from("invitation_email","to");
			$select1->where("invitation_email.signup_auth_code_id = ?",$code->id);
			$toEmail = $db->fetchOne($select1);
			$form->registerEmail->setValue($toEmail);

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
			$formData = $this->_request->getPost();
			if ($form->isValid($formData)) {

				$db = Zend_Registry::get('db');

				if ($form->getValue('registerPassword') == $form->getValue('repeat')) {
					// verify auth code
					$codeModel = new SignupAuthCode();
					$code = $codeModel->fetchRow(
		    		"auth_code='".$form->getValue('auth_code')."' and use_date is null"
		    		);
		    		
		    		if ($code){
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
		    				// save new consumer
		    				$consumerModel = new Consumer();
		    				$row = $consumerModel->createRow();
		    				$row->name = $form->getValue('name');
		    				$row->email = $form->getValue('registerEmail');
		    				$row->login_phone = $form->getValue('loginPhone');
		    				$row->password = md5($form->getValue('registerPassword'));
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
				} else {
					$this->view->errMessage = $this->view->translate('Register_err') . $this->view->translate('Register_password_is_invalid');
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
	
	
	function activateAction(){
		
	}
}