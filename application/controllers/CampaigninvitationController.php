<?php
include_once 'Indicate2Connect.php';
include_once 'fckeditor_php5.php';
class CampaignInvitationController extends MyController {
	function indexAction() {
		
		$this->view->title = $this->view->title = $this->view
			->translate ( "Wildfire" ) . " - " . $this->view
			->translate ( "CAMPAIGNS" );
		$this->view->activeTab = 'Campaigns';
		
		$consumerModel = new Consumer ();
		$consumer = $consumerModel->find ( $this->_currentUser->id )
			->current ();
		$currentTime = date ( "Y-m-d H:i:s" );
		
		$db = Zend_Registry::get ( 'db' );
		$select = $db->select ();
		
		$select->from ( 'campaign', '*' );
		$select->where ( 'campaign_invitation.consumer_id = ?', $consumer->id );
		$select->where ( 'campaign.expire_date > ?', $currentTime );
		$select->join ( 'campaign_invitation', 'campaign.id = campaign_invitation.campaign_id' );
		$select->join ( 'campaign_participation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id', 'accept_date' );
		$select->where("campaign_participation.state != 'COMPLETED'");
		$this->view->activeCampaigns = $db->fetchAll ( $select );
		
		$select2 = $db->select ();
		$select2->from ( 'campaign_invitation', array('campaign_invitation.*','campaign.*') );
		$select2->join ( 'campaign', 'campaign.id = campaign_invitation.campaign_id and campaign.type="campaign"', 'name' );
		$select2->where ( 'campaign_invitation.consumer_id = ?', $this->_currentUser->id );
		$select2->where ( 'campaign.expire_date > ?', $currentTime );
		$select2->where ( 'campaign_invitation.state = ?', 'NEW' );
		$select2->order ( 'campaign_invitation.create_date DESC' );
		$this->view->campaignInvitations = $db->fetchAll ( $select2 );
	
		//	Zend_Debug::dump($this->view->campaignInvitations);
	

	}
	
	function admincreateAction() {
		$this->view->activeTab = 'Campaigns';
		$this->_helper->layout->setLayout ( "layout_admin" );
		$request = $this->getRequest ();
		
		if (! $request->isPost ()) {
		
		} else {
			$formData = $request->getPost ();
			$this->view->sqlstr = $formData ['sql'];
			if ($this->view->sqlstr == '' || $this->view->sqlstr == null) {
				return;
			}
			try {
				$db = Zend_Registry::get ( 'db' );
				$sql = $db->quoteInto ( $this->view->sqlstr );
				$result = $db->query ( $sql );
				$this->view->rows = $result->fetchAll ();
			} catch ( Exception $e ) {
				$this->view->errMessage = "An err in sql!";
				return;
			}
		}
	
	}
	function adminselectedinvitationAction() {
		$this->view->activeTab = 'Campaigns';
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		$formData = $this->getRequest ()->getPost ();
		$consumeremails = substr ( $formData ['consumeremails'], 0, strlen ( $formData ['consumeremails'] ) - 1 );
		
		$this->view->activeTab = 'Mails';
		$fc = Zend_Controller_Front::getInstance ();
		$mailForm = new CampaignInvitationMailForm ();
		
		$this->view->mailForm = $mailForm;
		$this->view->mailForm->emailList
			->setValue ( $consumeremails );
		
		$fc = Zend_Controller_Front::getInstance ();
		$this->view->oFCKeditor = new FCKeditor ( 'htmlmessage' );
		$this->view->oFCKeditor->BasePath = $fc->getBaseUrl () . "/js/fckeditor/";
		$this->view->oFCKeditor->Height = "500px";
	
		//		Zend_Debug::dump($formData);
	}
	function adminsendselectedinvitationAction() {
		$this->view->activeTab = 'Campaigns';
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		$form = new CampaignInvitationMailForm ();
		$consumer = $this->_currentUser;
		$isSentSuccessfully = false;
		$currentTime = date ( "Y-m-d H:i:s" );
		
		if ($this->_request->isPost ()) { //POST
			$formData = $this->_request->getPost ();
			if ($form->isValid ( $formData )) {
				$subjectmessage = $formData ["subject"];
				$message = $formData ["message"];
				if ($message == null || $message == '') {
					$message = $formData ["htmlmessage"];
					$useHtmlEmail = true;
				} else {
					$useHtmlEmail = false;
				}
				$campaignId = $formData ["campaignId"];
				$emailListString = $formData ["emailList"];
				$emailArray = explode ( ';', $emailListString );
				//				Zend_Debug::dump($emailArray);
				

				$sentList = "Sent List:";
				$total = 0;
				$config = Zend_Registry::get ( 'config' );
				$smtpSender = new Zend_Mail_Transport_Smtp ( $config->smtp->invitation->mail->server, array ('username' => $config->smtp->invitation->mail->username, 'password' => $config->smtp->invitation->mail->password, 'auth' => $config->smtp->invitation->mail->auth, 'ssl' => $config->smtp->invitation->mail->ssl, 'port' => $config->smtp->invitation->mail->port ) );
				Zend_Mail::setDefaultTransport ( $smtpSender );
				foreach ( $emailArray as $emailAddress ) {
					$emailAddress = trim ( $emailAddress );
					if ($emailAddress == null || $emailAddress == '') {
						continue;
					}
					
					$db = Zend_Registry::get ( 'db' );
					$select = $db->select ();
					$select->from ( 'consumer', '*' )
						->where ( 'email = ?', $emailAddress )
						->where ( 'pest != 1 or pest is null' );
					$user = $db->fetchRow ( $select );
					//ignore the pest!
					if ($user == null) {
						continue;
					}
					//ignore those have been invited
					$campaignInvitationModel = new CampaignInvitation ();
					$isExist = $campaignInvitationModel->fetchRow ( 'consumer_id = ' . $user ['id'] . ' and campaign_id = ' . $campaignId );
					if ($isExist != null) {
						continue;
					}
					
					$stringChange = array ('?USERNAME?' => $user ['name'] );
					$subject = strtr ( $subjectmessage, $stringChange );
					$body = strtr ( $message, $stringChange );
					
					$mail = new Zend_Mail ( 'utf-8' );
					$langNamespace = new Zend_Session_Namespace ( 'Lang' );
					if ($langNamespace->lang == 'en' || $langNamespace->lang == 'EN') {
						$mail->setSubject ( $subject );
					} else {
						$mail->setSubject ( "=?UTF-8?B?" . base64_encode ( $subject ) . "?=" );
					}
					if ($useHtmlEmail != null && $useHtmlEmail) {
						$mail->setBodyHtml ( $body );
					} else {
						$mail->setBodyText ( $body );
					}
					$mail->setFrom ( $config->smtp->invitation->mail->username, $this->view
						->translate ( 'Wildfire_bi_lang' ) );
					$mail->addTo ( $emailAddress );
					//save into DB			
					try {
						//save into spark email
						$sparkEmailModel = new SparkEmail ();
						$sparkEmail = $sparkEmailModel->createRow ();
						$sparkEmail->subject = $subject;
						$sparkEmail->content = $body;
						$sparkEmail->to = $user ['id'];
						$sparkEmail->date = date ( "Y-m-d H:i:s" );
						$sparkEmail->save ();
						//save into campaign_invitation
						$campaignInvitation = new CampaignInvitation ();
						$row = $campaignInvitation->createRow ();
						$row->consumer_id = $user ['id'];
						$row->campaign_id = $campaignId;
						$row->create_date = date ( "Y-m-d H:i:s" );
						$row->state = "NEW";
						$row->save ();
						//send mail after saving
						$mail->send ();
						$total ++;
						$sentList .= $emailAddress . " ";
						$isSentSuccessfully = true;
					} catch ( Exception $e ) {
						//roll back...
						$this->view->showMessage = 'System Error!';
					}
				}
				$this->view->showMessage = $this->view
					->translate ( 'INVITATION_MAIL_LIST_PART1_Sucessful' );
				$this->view->showsentList = $sentList;
				$this->view->showTotal = "Total: " . $total;
				if (! $isSentSuccessfully) {
					$this->view->showMessage = $this->view
						->translate ( 'INVITATION_MAIL_LIST_PART1_Fail' );
				}
			
			} else {
				$this->view->showMessage = $this->view
					->translate ( 'INVITATION_MAIL_LIST_PART1_DataError' );
			}
		}
	}
	function adminprepareinvitationsAction() {
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		$this->view->activeTab = 'Mails';
		$fc = Zend_Controller_Front::getInstance ();
		$mailForm = new CampaignInvitationMailForm ();
		$this->view->mailForm = $mailForm;
		$this->view->mailForm->emailCategory
			->setAttribs ( array ('onChange' => "ChangeOption('emailCategory')" ) );
		$this->view->mailForm->message
			->setValue ( "" );
		$this->view->startDate = date ( "Y-m-d H:i:s" );
		
		$fc = Zend_Controller_Front::getInstance ();
		$this->view->oFCKeditor = new FCKeditor ( 'htmlmessage' );
		$this->view->oFCKeditor->BasePath = $fc->getBaseUrl () . "/js/fckeditor/";
		$this->view->oFCKeditor->Height = "500px";
		
		$config = Zend_Registry::get ( 'config' );
		$this->view->emailServer = $config->smtp->invitation->mail->username;
		
		
		$db = Zend_Registry::get ( 'db' );
		$select = $db->select ();
		$select->from ( 'email_template', array('email_template.*'));
		$select->join ( 'campaign', 'campaign.id = email_template.campaign',array('campaign.name'));
		$select->where ('email_template.delete =0');
		$select->order ('crdate desc');
		
		$this->view->emailTemplates = $db->fetchAll( $select );
		//Zend_Debug::dump($this->view->oFCKeditor->BasePath);
	}
	
	
	
	function admindynamicprepareinvitationsAction() {
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		$this->view->activeTab = 'Mails';
		$fc = Zend_Controller_Front::getInstance ();
		$mailForm = new CampaignInvitationMailForm ();
		$this->view->mailForm = $mailForm;
		$this->view->mailForm->emailCategory
			->setAttribs ( array ('onChange' => "ChangeOption('emailCategory')" ) );
		$this->view->mailForm->message
			->setValue ( "" );
		$this->view->startDate = date ( "Y-m-d H:i:s" );
		
		$fc = Zend_Controller_Front::getInstance ();
		$this->view->oFCKeditor = new FCKeditor ( 'htmlmessage' );
		$this->view->oFCKeditor->BasePath = $fc->getBaseUrl () . "/js/fckeditor/";
		$this->view->oFCKeditor->Height = "500px";
		
		$config = Zend_Registry::get ( 'config' );
		$this->view->emailServer = $config->smtp->invitation->mail->username;
		
		$db = Zend_Registry::get ( 'db' );
		$select = $db->select ();
		$select->from ( 'email_template', array('email_template.*'));
		$select->join ( 'campaign', 'campaign.id = email_template.campaign',array('campaign.name'));
		$select->where ('email_template.delete =0');
		$select->order ('crdate desc');
		
		$this->view->emailTemplates = $db->fetchAll( $select );
	
		//		Zend_Debug::dump($this->view->oFCKeditor->BasePath);
	}
	function adminshowsendinvitationsAction() {
		$this->view->formData = $this->_request
			->getPost ();
		$emailListString = $this->view->formData ["emailList"];
		$this->view->emailArray = explode ( ';', $emailListString );
		$this->view->sentcount = count ( $this->view->emailArray );
		$this->view->sentStartDate = $this->view->formData ['startDate'];
	
		//		Zend_Debug::dump($this->view->formData);
	}
	//	function adminajaxAction(){	
	//		$this->view->date = $this->_request->getParam('date');
	//		
	//		$db = Zend_Registry::get('db');
	//		$selectHaveSent = $db->select();
	//		$selectHaveSent->from('spark_email', 'to')
	//		->where("subject = 'Spark Email sent at ".$this->view->date."'");
	//		$consumerEmails = $db->fetchAll($selectHaveSent);
	//		if($consumerEmails != null){
	//			$consumerEmailsStr = '';
	//			foreach($consumerEmails as $consumerEmail){
	//				$consumerEmailsStr .= $consumerEmail['to'].',';
	//			}
	//		}else{
	//			$this->_helper->json(null);
	//		}
	//
	//		$this->_helper->json($consumerEmailsStr);
	//	}
	function adminsendinvitationsAction() {
		ini_set('display_errors', 1);
		$frontController = Zend_Controller_Front::getInstance();
		$frontController->throwExceptions(true);
		
		$this->view->title = $this->view
			->translate ( "Wildfire" ) . " - " . $this->view
			->translate ( "INVITATION_MAIL_SEND" );
		$this->view->activeTab = 'Mails';
		//
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		
		$form = new CampaignInvitationMailForm ();
		$consumer = $this->_currentUser;
		$isSentSuccessfully = false;
		
		if ($this->_request
			->isPost ()) { //POST
			$formData = $this->_request
				->getPost ();
			if ($form->isValid ( $formData )) {
				
				
//				$subjectmessage = $formData ["subject"];
//				$message = $formData ["message"];
//				if ($message == null || $message == '') {
//					$message = $formData ["htmlmessage"];
//					$useHtmlEmail = true;
//				} else {
//					$useHtmlEmail = false;
//				}

				$useHtmlEmail = true;
				//2011-02-14 ham.bao get email content and subject
				$emailTemplate = new EmailTemplate();
				$email = $emailTemplate->fetchRow('id = '.$formData['emailTemplate']);
				$subjectmessage = $email->subject;
				$message =  $email->message;
				//2011-02-14 ham.bao get email content and subject
					
				$campaignId = $formData ["campaignId"];
				$code_source = $formData ["code_source"];
				$emailCategory = $formData ['emailCategory'];
				$emailListString = $formData ["emailList"];
				//				$emailArray = explode(';',$emailListString);
				$emailArray = preg_split ( '/[;\s]+[\n\r\t]*/', trim ( $emailListString ) );
				
				$sentList = "";
				$failList = "";
				$total = 0;
				$config = Zend_Registry::get ( 'config' );
				$smtpSender = new Zend_Mail_Transport_Smtp ( $config->smtp->invitation->mail->server, array ('username' => $config->smtp->invitation->mail->username, 'password' => $config->smtp->invitation->mail->password, 'auth' => $config->smtp->invitation->mail->auth, 'ssl' => $config->smtp->invitation->mail->ssl, 'port' => $config->smtp->invitation->mail->port ) );
				//				$smtpSender = new Zend_Mail_Transport_Smtp(
				//							'smtp.163.com',array(
				//							'username'=>'yun_simon@163.com',
				//							'password'=>'19990402',
				//							'auth'=>'login'));
				Zend_Mail::setDefaultTransport ( $smtpSender );
				$db = Zend_Registry::get ( 'db' );
				$langNamespace = new Zend_Session_Namespace ( 'Lang' );
				foreach ( $emailArray as $emailAddress ) {
					$currentTime = date ( "Y-m-d H:i:s" );
					$emailAddress = trim ( $emailAddress );
					if ($emailAddress == null || $emailAddress == '') {
						$failList .= $emailAddress . ", ";
						continue;
					}
					if ($emailCategory == 'Invite non-sparks to join campaign') {
						// ignore the spark!
						$consumerModel = new Consumer ();
						$consumer = $consumerModel->fetchRow ( "email = '" . $emailAddress . "'" );
						if ($consumer != null) {
							$failList .= $emailAddress . ", ";
							continue;
						}
						//						$invitationemailModel = new InvitationEmail();
						//						$invitationemail = $invitationemailModel->fetchRow("invitation_email.to = '".$emailAddress."'");
						//						if($invitationemail != null){
						//							$signupauthcodeModel = new SignupAuthCode();
						//							$signupauthcode = $signupauthcodeModel->fetchRow("signup_auth_code.id = ".$invitationemail->signup_auth_code_id.
						//							" and auto_invitation = ".$campaignId);
						//							if($signupauthcode != null){
						//								continue;
						//							}
						//						}
						

						$selectCode = $db->select ();
						$selectCode->from ( 'signup_auth_code', 'signup_auth_code.auth_code' )
							->joinInner ( 'invitation_email', "invitation_email.signup_auth_code_id = signup_auth_code.id and invitation_email.to ='$emailAddress'" )
							->where ( 'signup_auth_code.auto_invitation= ?', $campaignId );
						$code = $db->fetchOne ( $selectCode );
						
						//generate rand code
						if ($code == false) {
							$codePattern = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
							$signup_auth_code = '';
							for($codeCount = 0; $codeCount < 12; $codeCount ++) {
								$signup_auth_code = $signup_auth_code . $codePattern {mt_rand ( 0,  31)};
							}
						} else {
							$signup_auth_code = $code;
						}
						//var_dump($subjectmessage);die;
						$stringChange = array ('?EMAIL?' => $emailAddress, '?AUTHCODE?' => ( string ) $signup_auth_code );
						$subject = strtr ( $subjectmessage, $stringChange );
						$body = strtr ( $message, $stringChange );
					}
					if ($emailCategory == 'Invite sparks to join campaign') {
						$select = $db->select ();
						$select->from ( 'consumer', '*' )
							->where ( 'email = ?', $emailAddress )
							->where ( 'pest != 1 or pest is null' );
						$user = $db->fetchRow ( $select );
						//ignore the pest!
						if ($user == null) {
							$failList .= $emailAddress . ", ";
							continue;
						}
						//ignore those have been invited
						$selectInvitedSpark = $db->select ();
						$selectInvitedSpark->from ( 'campaign_invitation', '*' )
							->where ( 'campaign_id = ?', $campaignId )
							->where ( 'consumer_id = ?', $user ['id'] );
						$invitedSpark = $db->fetchRow ( $selectInvitedSpark );
						if ($invitedSpark != null) {
							$failList .= $emailAddress . ", ";
							continue;
						}
						
						$stringChange = array ('?USERNAME?' => $user ['name'] );
						$subject = strtr ( $subjectmessage, $stringChange );
						$body = strtr ( $message, $stringChange );
					}
					if ($emailCategory == 'Send mail to sparks') {
						$select = $db->select ();
						$select->from ( 'consumer', '*' )
							->where ( 'email = ?', $emailAddress )
							->where ( 'pest != 1 or pest is null' );
						$user = $db->fetchRow ( $select );
						//ignore the pest!
						if ($user == null) {
							$failList .= $emailAddress . ", ";
							continue;
						}
						$stringChange = array ('?USERNAME?' => $user ['name'] );
						$subject = strtr ( $subjectmessage, $stringChange );
						$body = strtr ( $message, $stringChange );
					}
					
					$mail = new Zend_Mail ( 'utf-8' );
					if ($langNamespace->lang == 'en' || $langNamespace->lang == 'EN') {
						$mail->setSubject ( $subject );
					} else {
						$mail->setSubject ( "=?UTF-8?B?" . base64_encode ( $subject ) . "?=" );
					}
					if ($useHtmlEmail != null && $useHtmlEmail) {
						$mail->setBodyHtml ( $body );
					} else {
						$mail->setBodyText ( $body );
					}
					
					$mail->setFrom ( $config->smtp->invitation->mail->username, $this->view
						->translate ( 'Wildfire_bi_lang' ) );
					//					$mail->setFrom('yun_simon@163.com',$this->view->translate('Wildfire'));
					$mail->addTo ( $emailAddress );
								
					try {
						$mail->send ();
						//save into DB		
						//save into signup_auth_code
						if ($emailCategory == 'Invite non-sparks to join campaign') {
							
							$signupAuthCodeModel = new SignupAuthCode ();
							$signupAuthCode = $signupAuthCodeModel->createRow ();
							$signupAuthCode->auth_code = $signup_auth_code;
							$signupAuthCode->create_date = $currentTime;
							if ($code_source == null || $code_source == '') {
								$signupAuthCode->source = 'SIGNUP';
							} else {
								$signupAuthCode->source = $code_source;
							}
							$signupAuthCode->auto_invitation = $campaignId;
							$signupAuthCode->save ();
							//save into invitation_email
							$invitationEmailModel = new InvitationEmail ();
							$invitationEmail = $invitationEmailModel->createRow ();
							$invitationEmail->subject = $subject;
							$invitationEmail->content = $body;
							$invitationEmail->consumer_id = 173;
							$invitationEmail->to = $emailAddress;
							$invitationEmail->signup_auth_code_id = $signupAuthCode->id;
							$invitationEmail->date = $currentTime;
							$invitationEmail->save ();
						}
						if ($emailCategory == 'Invite sparks to join campaign') {
							//save into spark email
							$sparkEmailModel = new SparkEmail ();
							$sparkEmail = $sparkEmailModel->createRow ();
							$sparkEmail->subject = $subject;
							$sparkEmail->content = $body;
							$sparkEmail->to = $user ['id'];
							$sparkEmail->date = date ( "Y-m-d H:i:s" );
							$sparkEmail->save ();
							//save into campaign_invitation
							$campaigninvitationModel = new CampaignInvitation ();
							$campaigninvitation = $campaigninvitationModel->createRow ();
							$campaigninvitation->campaign_id = $campaignId;
							$campaigninvitation->consumer_id = $user ['id'];
							$campaigninvitation->create_date = $currentTime;
							$campaigninvitation->state = 'NEW';
							$campaigninvitation->save ();
						}
						if ($emailCategory == 'Send mail to sparks') {
							//save into spark email
							$sparkEmailModel = new SparkEmail ();
							$sparkEmail = $sparkEmailModel->createRow ();
							$sparkEmail->subject = $subject;
							$sparkEmail->content = $body;
							$sparkEmail->to = $user ['id'];
							$sparkEmail->date = date ( "Y-m-d H:i:s" );
							$sparkEmail->save ();
						}
						$total ++;
						$sentList .= $emailAddress . ", ";
						$isSentSuccessfully = true;
					} catch ( Exception $e ) {
						//roll back...
						$failList .= $emailAddress . ", ";
						continue;
					}
				}
				$this->view->showMessage = $this->view
					->translate ( 'INVITATION_MAIL_LIST_PART1_Sucessful' );
				$this->view->showsentList = $sentList;
				$this->view->showfailList = $failList;
				$this->view->showTotal = "Total: " . $total;
				if (! $isSentSuccessfully) {
					$this->view->showMessage = $this->view
						->translate ( 'INVITATION_MAIL_LIST_PART1_Fail' );
				}
			
			} else {
				$this->view->showMessage = $this->view
					->translate ( 'INVITATION_MAIL_LIST_PART1_DataError' );
			}
		}
	}
	function admindynamicsendinvitationsAction() {
		$this->view->title = $this->view
			->translate ( "Wildfire" ) . " - " . $this->view
			->translate ( "INVITATION_MAIL_SEND" );
		$this->view->activeTab = 'Mails';
		//
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		
		$form = new CampaignInvitationMailForm ();
		$consumer = $this->_currentUser;
		$isSentSuccessfully = false;
		if ($this->_request
			->isPost ()) { //POST
			$formData = $this->_request
				->getPost ();
			if ($form->isValid ( $formData )) {
//				$subjectmessage = $formData ["subject"];
//				$message = $formData ["message"];
//				if ($message == null || $message == '') {
//					$message = $formData ["htmlmessage"];
//					$useHtmlEmail = true;
//				} else {
//					$useHtmlEmail = false;
//				}

				$useHtmlEmail = true;
				//2011-02-14 ham.bao get email content and subject
				$emailTemplate = new EmailTemplate();
				$email = $emailTemplate->fetchRow('id = '.$formData['emailTemplate']);
				$subjectmessage = $email->subject;
				$message =  $email->message;
				//2011-02-14 ham.bao get email content and subject
				
				$campaignId = $formData ["campaignId"];
				$code_source = $formData ["code_source"];
				$emailCategory = $formData ['emailCategory'];
				$emailListString = $formData ["emailList"];
				//				$emailArray = explode(';',$emailListString);
				// $emailArray= preg_split ( '/[;\s]+[\n\r\t]*/', trim ( $emailListString ) );
				$emailArray = array ();
				$rows = explode ( "\n", $emailListString );
				$i = 0;
				foreach ( $rows as $row ) {
					$cells = explode ( "\t", $row );
					$j = 0;
					foreach ( $cells as $cell ) {
						$emailArray [$i] [$j] = $cell;
						$j ++;
					}
					$i ++;
				}
				$sentList = "";
				$total = 0;
				$config = Zend_Registry::get ( 'config' );
				$smtpSender = new Zend_Mail_Transport_Smtp ( $config->smtp->invitation->mail->server, array ('username' => $config->smtp->invitation->mail->username, 'password' => $config->smtp->invitation->mail->password, 'auth' => $config->smtp->invitation->mail->auth, 'ssl' => $config->smtp->invitation->mail->ssl, 'port' => $config->smtp->invitation->mail->port ) );
				Zend_Mail::setDefaultTransport ( $smtpSender );
				$db = Zend_Registry::get ( 'db' );
				$langNamespace = new Zend_Session_Namespace ( 'Lang' );
				//print_r($emailArray);die;
				foreach ( $emailArray  as $emailAddress ) {
					$currentTime = date ( "Y-m-d H:i:s" );
					//$emailAddress = trim ( $emailAddress[0] );
					if ($emailAddress[0] == null || $emailAddress[0] == '') {
						continue;
					}
					if ($emailCategory == 'Invite non-sparks to join campaign') {
						// ignore the spark!
						$consumerModel = new Consumer ();
						$consumer = $consumerModel->fetchRow ( "email = '" . $emailAddress[0] . "'" );
						if ($consumer != null) {
							continue;
						}
						
						$selectCode = $db->select ();
						$selectCode->from ( 'signup_auth_code', 'signup_auth_code.auth_code' )
							->joinInner ( 'invitation_email', "invitation_email.signup_auth_code_id = signup_auth_code.id and invitation_email.to ='$emailAddress[0]'" )
							->where ( 'signup_auth_code.auto_invitation= ?', $campaignId );
						$code = $db->fetchOne ( $selectCode );
						
						//generate rand code
						if ($code == false) {
							$codePattern = '1234567890ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
							$signup_auth_code = '';
							for($codeCount = 0; $codeCount < 12; $codeCount ++) {
								$signup_auth_code = $signup_auth_code . $codePattern {mt_rand ( 0, 35 )};
							}
						} else {
							$signup_auth_code = $code;
						}
						//var_dump($signup_auth_code);die;
						$stringChange = array ('?EMAIL?' => $emailAddress[0], '?AUTHCODE?' => ( string ) $signup_auth_code );
						$subject = strtr ( $subjectmessage, $stringChange );
						$body = strtr ( $message, $stringChange );
					}
					if ($emailCategory == 'Invite sparks to join campaign') {
						$select = $db->select ();
						$select->from ( 'consumer', '*' )
							->where ( 'email = ?', $emailAddress[0] )
							->where ( 'pest != 1 or pest is null' );
						$user = $db->fetchRow ( $select );
						//ignore the pest!
						if ($user == null) {
							continue;
						}
						//ignore those have been invited
						$selectInvitedSpark = $db->select ();
						$selectInvitedSpark->from ( 'campaign_invitation', '*' )
							->where ( 'campaign_id = ?', $campaignId )
							->where ( 'consumer_id = ?', $user ['id'] );
						$invitedSpark = $db->fetchRow ( $selectInvitedSpark );
						if ($invitedSpark != null) {
							continue;
						}
						$stringChange = array ('?USERNAME?' => $user ['name'] );
						$subject = strtr ( $subjectmessage, $stringChange );
						$body = strtr ( $message, $stringChange );
					}
					if ($emailCategory == 'Send mail to sparks') {
						$select = $db->select ();
						$select->from ( 'consumer', '*' )
							->where ( 'email = ?', $emailAddress[0] )
							->where ( 'pest != 1 or pest is null' );
						$user = $db->fetchRow ( $select );
						//ignore the pest!
						if ($user == null) {
							continue;
						}
						$stringChange = array ('?USERNAME?' => $user ['name'] );
						$subject = strtr ( $subjectmessage, $stringChange );
						$body = strtr ( $message, $stringChange );
					}
					
					$i=0;
					$num = count($emailAddress);
					for($j = 1; $j <= $num; $j ++) {
						if (isset ( $emailAddress [$j] )) {
							$body = str_replace ( '$' . $j, $emailAddress [$j], $body );
						}
					}
					$i++;
					$mail = new Zend_Mail ( 'utf-8' );
					if ($langNamespace->lang == 'en' || $langNamespace->lang == 'EN') {
						$mail->setSubject ( $subject );
					} else {
						$mail->setSubject ( "=?UTF-8?B?" . base64_encode ( $subject ) . "?=" );
					}
					if ($useHtmlEmail != null && $useHtmlEmail) {
						$mail->setBodyHtml ( $body );
					} else {
						$mail->setBodyText ( $body );
					}
					
					$mail->setFrom ( $config->smtp->invitation->mail->username, $this->view
						->translate ( 'Wildfire_bi_lang' ) );
					//					$mail->setFrom('yun_simon@163.com',$this->view->translate('Wildfire'));
					$mail->addTo ( $emailAddress[0] );
					$mail->send ();
					//save into DB					
					try {
						//save into signup_auth_code
						if ($emailCategory == 'Invite non-sparks to join campaign') {
							
							$signupAuthCodeModel = new SignupAuthCode ();
							$signupAuthCode = $signupAuthCodeModel->createRow ();
							$signupAuthCode->auth_code = $signup_auth_code;
							$signupAuthCode->create_date = $currentTime;
							if ($code_source == null || $code_source == '') {
								$signupAuthCode->source = 'SIGNUP';
							} else {
								$signupAuthCode->source = $code_source;
							}
							$signupAuthCode->auto_invitation = $campaignId;
							$signupAuthCode->save ();
							//save into invitation_email
							$invitationEmailModel = new InvitationEmail ();
							$invitationEmail = $invitationEmailModel->createRow ();
							$invitationEmail->subject = $subject;
							$invitationEmail->content = $body;
							$invitationEmail->consumer_id = 16693;
							$invitationEmail->to = $emailAddress[0];
							$invitationEmail->signup_auth_code_id = $signupAuthCode->id;
							$invitationEmail->date = $currentTime;
                                                        $invitationEmail->save ();
                                                   
						}
						if ($emailCategory == 'Invite sparks to join campaign') {
							//save into spark email
							$sparkEmailModel = new SparkEmail ();
							$sparkEmail = $sparkEmailModel->createRow ();
							$sparkEmail->subject = $subject;
							$sparkEmail->content = $body;
							$sparkEmail->to = $user ['id'];
							$sparkEmail->date = date ( "Y-m-d H:i:s" );
							$sparkEmail->save ();
							//save into campaign_invitation
							$campaigninvitationModel = new CampaignInvitation ();
							$campaigninvitation = $campaigninvitationModel->createRow ();
							$campaigninvitation->campaign_id = $campaignId;
							$campaigninvitation->consumer_id = $user ['id'];
							$campaigninvitation->create_date = $currentTime;
							$campaigninvitation->state = 'NEW';
							$campaigninvitation->save ();
						}
						if ($emailCategory == 'Send mail to sparks') {
							//save into spark email
							$sparkEmailModel = new SparkEmail ();
							$sparkEmail = $sparkEmailModel->createRow ();
							$sparkEmail->subject = $subject;
							$sparkEmail->content = $body;
							$sparkEmail->to = $user ['id'];
							$sparkEmail->date = date ( "Y-m-d H:i:s" );
							$sparkEmail->save ();
						}
						$total ++;
						$sentList .= $emailAddress[0] . ", ";
						$isSentSuccessfully = true;
					} catch ( Exception $e ) {
						//roll back...
                                               
						$this->view->showMessage = 'System Error!';
					}
				}
				$this->view->showMessage = $this->view
					->translate ( 'INVITATION_MAIL_LIST_PART1_Sucessful' );
				$this->view->showsentList = $sentList;
				$this->view->showTotal = "Total: " . $total;
				if (! $isSentSuccessfully) {
					$this->view->showMessage = $this->view
						->translate ( 'INVITATION_MAIL_LIST_PART1_Fail' );
				}
			
			} else {
				$this->view->showMessage = $this->view
					->translate ( 'INVITATION_MAIL_LIST_PART1_DataError' );
			}
		}
	}
	
	//deprecated
	function acceptAction() {
		$db = Zend_Registry::get ( 'db' );
		$request = $this->getRequest ();
		$campaignId = ( int ) $this->_request
			->getParam ( 'id', 0 );
		$consumer = $this->_currentUser;
		
		if ($campaignId > 0) {
			
			// check if precampaign poll is finished
			$select1 = $db->select ();
			$select1->from ( 'campaign', 'pre_campaign_survey' );
			$select1->where ( 'campaign.id = ?', $campaignId );
			$previewCamSurvey = $db->fetchOne ( $select1 );
			
			$indicate2Connect = new Indicate2_Connect ();
			$ids = array ($previewCamSurvey );
			$wsResult = $indicate2Connect->getAnswerSetCount ( $consumer->email, $ids );
			//			Zend_Debug::dump($wsResult);
			if ($wsResult == 0) {
				$this->_redirect ( 'campaign/precampaign/survey/' . $previewCamSurvey );
			} else {
				
				$campaignInvitationModel = new CampaignInvitation ();
				$campaignInvitation = $campaignInvitationModel->fetchRow ( "campaign_id=" . $campaignId . " and consumer_id=" . $consumer->id );
				$id = $campaignInvitation->id;
				//				Zend_Debug::dump($campaignInvitation);
				$campaignInvitation->state = "ACCEPTED";
				$campaignInvitation->save ();
				
				$result = $db->fetchOne ( "SELECT COUNT(*) FROM campaign_participation WHERE campaign_invitation_id=:t1", array ('t1' => $id ) );
				if ($result == 0) {
					//create participation
					$campaignParticipationModel = new CampaignParticipation ();
					$currentTime = date ( "Y-m-d H:i:s" );
					$row = $campaignParticipationModel->createRow ();
					$row->campaign_invitation_id = $id;
					$row->accept_date = $currentTime;
					$row->save ();
				}
				$this->_redirect ( 'campaigninvitation/index' );
			}
		}
		
		$this->_helper->layout
			->disableLayout ();
	
	}
	
	function prerejectAction() {
		$this->view->compaign_id = ( int ) $this->_request
			->getParam ( 'id' );
		$campaignModel = new Campaign ();
		$campaign = $campaignModel->fetchRow ( 'id = ' . $this->view->compaign_id );
		$this->view->campaign_name = $campaign->name;
	}
	
	function rejectAction() {
		$compaign_id = ( int ) $this->_request
			->getParam ( 'id' );
		$campaignInvitationModel = new CampaignInvitation ();
		$row = $campaignInvitationModel->fetchRow ( 'campaign_id = ' . $compaign_id . ' and consumer_id = ' . $this->_currentUser->id );
		$row->state = 'REJECTED';
		$row->save ();
		$this->_helper
			->redirector ( 'index', 'campaigninvitation' );
	
		//		Zend_Debug::dump($compaign_id);
	

	}
	
	function adminlistAction() {
		$this->view->title = "Campaign Invitation";
		$this->view->activeTab = "List Campaign Invitations";
		
		$this->campaign_id = $this->_request->getParam ( 'id' );
		$this->view->campaign_id = $this->campaign_id;
		$db = Zend_Registry::get ( 'db' );
		$select = $db->select ();
		$select->from ( 'consumer', array ('name as con_name', 'email' ) );
		$select->join ( 'campaign_invitation', 'consumer.id = campaign_invitation.consumer_id', '*' );
		$select->join ( 'campaign', 'campaign.id = campaign_invitation.campaign_id', 'name as cam_name' );
		$select->order ( 'campaign_invitation.create_date desc' );
		$select->where ( 'campaign_invitation.campaign_id = ?', $this->campaign_id );
		
		$this->view->campaignInvitations = $db->fetchAll ( $select );
		
		//		$this->_helper->layout->disableLayout();
		$this->_helper->layout
			->setLayout ( "layout_admin" );
	}
	
	function deleteAction() {
		$this->view->title = "Delete Campaign Invitation";
		
		if ($this->_request
			->isPost ()) {
			$id = ( int ) $this->_request
				->getPost ( 'id' );
			$del = $this->_request
				->getPost ( 'del' );
			$campaignInvitationForm = new CampaignInvitationForm ();
			if ($del == 'Yes' && $id > 0) {
				$where = 'id = ' . $id;
				$campaignInvitationForm->delete ( $where );
			}
			//TODO : return page is null
			$this->_redirect ( 'campaignInvitation/list' );
		} else {
			$id = ( int ) $this->_request
				->getParam ( 'id' );
			if ($id > 0) {
				$db = Zend_Registry::get ( 'db' );
				$select = $db->select ();
				$select->from ( 'consumer', 'name as con_name' );
				$select->join ( 'campaign_invitation', 'consumer.id = campaign_invitation.consumer_id', 'id' );
				$select->join ( 'campaign', 'campaign.id = campaign_invitation.campaign_id', 'name as cam_name' );
				$select->where ( 'campaign_invitation.id = ?', $id );
				$this->view->campaignInvitation = $db->fetchRow ( $select );
			}
		}
	}
	
	
	function adminemailtemplateAction(){
		$this->_helper->layout->setLayout ( "layout_admin" );
		$emailTemplate = new EmailTemplate();
		$admintemplateId = $this->_request->getParam('id');
		if ($this->_request->isPost ()) {
            if(!$admintemplateId){
				$row  = $emailTemplate->createRow();
				$row->campaign = $this->_request->getPost('campaign');
				$row->subject = $this->_request->getPost('subject');
				if($this->_request->getPost('message')!=''){
					$row->message = $this->_request->getPost('message');
				}else{
					$row->message = $this->_request->getPost('htmlmessage');
				}
				$row->crdate = date('Y-m-d h:i:s');
				$row->save();
            }else{
            	if($this->_request->getPost('message')!=''){
					$message = $this->_request->getPost('message');
				}else{
					$message = $this->_request->getPost('htmlmessage');
				}
				$emailTemplate->update(array('subject'=>$this->_request->getPost('subject'),'message'=>$message),'id = '.$admintemplateId);
            }
	
			$submitted = true;
		}else{
			$submitted = false;
		}
	    $form = new CampaignEmailTemplateForm();
	    
	    $campaigns = new Campaign();
	    $campaignsData = $campaigns->fetchAll(NULL,'create_date desc');
	    //print_r($campaignsData);
	    $campaignOptions = array();
	    
	    foreach ($campaignsData as $val){
	    	$campaignOptions[$val->id] = $val->name;
	    }
		$fc = Zend_Controller_Front::getInstance ();
		$this->view->oFCKeditor = new FCKeditor ( 'htmlmessage' );
		$this->view->oFCKeditor->BasePath = $fc->getBaseUrl () . "/js/fckeditor/";
		$this->view->oFCKeditor->Height = "500px";
		
		
		if($admintemplateId){
			$email           = $emailTemplate->fetchRow('id ='.$admintemplateId);
			$this->view->oFCKeditor->Value  = $email->message ;
			$form->setDefault('subject',$email->subject);
			$this->view->admintemplateId = $admintemplateId;
		}
		
		//var_dump($email->message);die;
		
		$this->view->form = $form;
		$this->view->campaigns = $campaignOptions;
		$this->view->submitted = $submitted;
	}
	
	function adminemailtemplatelistAction(){
		if($this->_request->getParam ('id')){
			//Zend_Debug::dump($this->_request->getParam ('id'));die();
			$this->_helper->layout->disableLayout();
			$doValue = explode('&',$this->_request->getParam('do'));
			$do = $doValue[0];
			$emailTemplate = new EmailTemplate();
			if($do == 'delete'){
				Zend_Debug::dump("adminemailtemplatelistAction");die();
				$emailTemplate->update(array("delete"=>1),'id = '.$this->_request->getParam ('id'));
				$this->_helper->redirector("adminemailtemplatelist");
			}else{
				$this->view->emailTemplate = $emailTemplate->fetchRow('id = '.$this->_request->getParam ('id'));
			}
			$this->view->action = true;
			
		}else{
			$this->_helper->layout->setLayout ( "layout_admin" );
			$db = Zend_Registry::get ( 'db' );
			$select = $db->select ();
			$select->from ( 'email_template', array('email_template.*'));
			$select->join ( 'campaign', 'campaign.id = email_template.campaign',array('campaign.name'));
			$select->where ('email_template.delete =0 ');
			$select->order ('crdate desc');
			
			$this->view->emailTemplates = $db->fetchAll( $select );
			
			$this->view->action = false;
		}
		//print_r($this->view->emailTemplates);die;
	}
	
	
	function admincheckemailAction(){
		$emailCategory  = $this->_request->getParam('emailCategory');
		$emails = array_unique(preg_split ( '/[;\s]+[\n\r\t]*/', $this->_request->getParam('emails') ));
		
		$emailsSparts   = '';
		$emailsNotSparts = '';
		foreach ($emails as $email){
		        $consumerModel = new Consumer ();
				$consumer = $consumerModel->fetchRow ( "email = '" . $email . "'" );
				if ($consumer != null) {				
					$emailsSparts    .= $email . "; ";
				}else{
					$emailsNotSparts .= $email . "; ";
				}
		}
		print_r($emailsSparts."|".$emailsNotSparts);die();
	}
	
	function adminprofilesurveyAction(){
		$profileSurvey = $this->_request->getParam('profileSurvey');
		$consumerId = $this->_request->getParam('id');
		$consumerArray = explode(',', $consumerId);
			
		$profileSurveyInvitation = new ProfileSurveyInvitation();
		$notificationModel = new Notification();
		foreach ($consumerArray as $consumer){			
			if ( $consumer !='') {
				$invitation = $profileSurveyInvitation->fetchRow('consumer_id = '. $consumer . ' and profile_id = ' .$profileSurvey);
				if(!count($invitation)){
					$currentTime = date ( "Y-m-d H:i:s" );
					$row = $profileSurveyInvitation->createRow();
					$row->consumer_id = $consumer;
					$row->profile_id  = $profileSurvey;
					$row->date        = $currentTime;
					$row->save();
					// add notification
					$notificationModel->createRecord("PROFILE_SURVEY",$consumer,$profileSurvey);
				}
			}			
		}
		$this->_helper->json('Success');
	}
	function admindeleteemailAction(){
			$this->_helper->layout->disableLayout();
			$emailTemplate = new EmailTemplate();
			$emailTemplate->update(array("delete"=>1),'id = '.$this->_request->getParam ('id'));
	}
}
