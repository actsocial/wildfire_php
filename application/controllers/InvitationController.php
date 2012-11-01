<?php
require_once 'Zend/Mail/Transport/Smtp.php';
require_once 'Zend/Mail.php';

class InvitationController extends MyController
{
	protected $_maxInvitation = 10;

	function indexAction()
	{
		// error_reporting(0);

		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("Friend_Invitations");

		$consumer = $this->_currentUser;

		//selcet the email address which has been invited
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('invitation_email', 'distinct  (invitation_email.to)');
		$select->where('consumer_id = ?',$this->_currentUser->id);
		$receivers = $db->fetchAll($select);
		
		//select campaigns which were this consumer joined in.
				
		$select3 = $db->select();
		$select3->from('campaign','campaign.*')
				->join('campaign_invitation','campaign.id=campaign_invitation.campaign_id','campaign_invitation.state')
				->where("campaign_invitation.state !='NEW' ")
				->where("campaign_invitation.consumer_id = ?",$this->_currentUser->id);

		$allCampaigns = $db->fetchAll($select3);
		// print_r($allCampaigns);die();
		
		$select2=$db->select();
		$select2->from('consumer','invitation_limit');
		$select2->where('id =?',$this->_currentUser->id);
		$default_count=$db->fetchAll($select2);
		if($default_count==NULL||$default_count==''){
			$default_count=10;
		}
		$this->view->sendMailForm=new SendMailForm(array("max_amount"=>count($receivers)+1));
		$this->view->justone=count($receivers)+1;
		$this->view->receivers=$receivers;
		for($i=1;$i<count($receivers)+1&&$i<=$default_count;$i++){
			$n = "email".(string)$i;
			$this->view->sendMailForm->$n->setValue($receivers[$i-1]['to']);
			$this->view->sendMailForm->$n->setAttribs(array("disabled"=>'disabled'));
		}

		//set email subject value
		$langNamespace = new Zend_Session_Namespace('Lang');
		if($langNamespace->lang == 'en' || $langNamespace->lang == 'EN'){
			$this->view->sendMailForm->subject->setValue("Invitation to Wildfire from ".$this->_currentUser->email);
		}else{
			$this->view->sendMailForm->subject->setValue("您的朋友 ".$this->_currentUser->email."，邀请您加入星星火！");
		}
		//count the amount of not sent mail
		if ($consumer->invitation_limit !=null){
			$this->view->notSendMailAmont = $consumer->invitation_limit - count($receivers);
			$this->view->Invitation_limit = $consumer->invitation_limit;
		}else{
			$this->view->notSendMailAmont = $this->_maxInvitation - count($receivers);
			$this->view->Invitation_limit = $this->_maxInvitation;
		}
		$this->view->sendMailForm->sentMailAmount->setValue(count($receivers));
		$this->view->allCampaigns = $allCampaigns;
		//		Zend_Debug::dump($row->create_date);
	}
	
	/**
	 * sendAction 邀请朋友加入(给朋友的邮箱发送邮件)
	 */
	function sendAction(){
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("INVITATION_MAIL_SEND");
		//
		$form = new SendMailForm();
		$consumer = $this->_currentUser;
		$isSentSuccessfully = false;
		if($consumer['pest'] == '1'){
			$this->view->showMessage = $this->view->translate('INVITATION_MAIL_LIST_PART1_Sucessful');
			$isSentSuccessfully = true;
			return;
		}
		if ($this->_request->isPost()) {//POST
			$formData = $this->_request->getPost();
			if ($form->isValid($formData)) {
				if ($consumer->invitation_limit !=null){
					$this->invitation_limit = $consumer->invitation_limit;
				}else{
					$this->invitation_limit = $this->_maxInvitation;
				}
				//get the amount of emails have been sent
				$db = Zend_Registry::get('db');
				$select = $db->select();
				$select->from('invitation_email', 'distinct  (invitation_email.to)');
				$select->where('consumer_id = ?',$this->_currentUser->id);
//				$select->from('invitation_email', 'count(*)');
//				$select->where('consumer_id = ?',$consumer->id);
				for($i=(int)$db->fetchOne($select)+1; $i<=$this->invitation_limit; $i++){
					if($form->getValue('email'.(string)$i)!=''){
						//generate rand code
						$codePattern = '1234567890ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
						$signup_auth_code = '';
						for($codeCount=0; $codeCount<12; $codeCount++){
							$signup_auth_code = $signup_auth_code.$codePattern{mt_rand(0,35)};
						}
						//send mail
						$emailSubject = $this->view->translate('Invitation_Email_subject');
						$emailBody = $this->view->translate('Invitation_Email_body');
						$stringChange = array(
							'?USERNAME?' => $this->_currentUser['name'],
							'?EMAIL?' => $this->_currentUser['email'],
							'?MESSAGE?' => $form->getValue('message'),
							'?AUTHCODE?' => (string)$signup_auth_code);
						$emailSubject = strtr($emailSubject,$stringChange);
						$emailBody = strtr($emailBody,$stringChange);
						$config = Zend_Registry::get('config');
						$smtpSender = new Zend_Mail_Transport_Smtp(
								$config->smtp->friend->mail->server,
								array(
									'username'=> $config->smtp->friend->mail->username,
									'password'=> $config->smtp->friend->mail->password,
									'auth'=> $config->smtp->friend->mail->auth,
									'ssl' => $config->smtp->friend->mail->ssl,
			               			'port' => $config->smtp->friend->mail->port));
//							$smtpSender = new Zend_Mail_Transport_Smtp(
//														smtp.163.com',array(
//														'username'=>'yun_simon@163.com',
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
						$mail->setFrom($config->smtp->friend->mail->username, $consumer->name);
						$mail->addHeader('Reply-To', $consumer->email);
//						$mail->setFrom('yun_simon@163.com',$this->view->translate('Wildfire'));
						$mail->addTo($form->getValue('email'.(string)$i));
						//save into DB					
						try{
							$currentTime = date("Y-m-d H:i:s");
							
							//save into signup_auth_code
							$signupAuthCodeModel = new SignupAuthCode();
							$signupAuthCode = $signupAuthCodeModel->createRow();
							$signupAuthCode->auth_code = $signup_auth_code;
							$signupAuthCode->create_date = $currentTime;
							$signupAuthCode->sender = $this->_currentUser->id;
							$signupAuthCode->source = 'FRIENDS';
							$signupAuthCode->auto_invitation = 0;
							$signupAuthCode->save();
							
							 
							//send mail after saving
							$mail->send();	
							
							//save into invitation_email
							$invitationEmailModel = new InvitationEmail();
							$invitationEmail = $invitationEmailModel->createRow();
							$invitationEmail->subject = $emailSubject;
							$invitationEmail->content = $form->getValue('message');
							$invitationEmail->consumer_id = $this->_currentUser->id;
							$invitationEmail->to = $form->getValue('email'.(string)$i);
							$invitationEmail->signup_auth_code_id = $signupAuthCode->id;
							$invitationEmail->date = $currentTime;
							$invitationEmail->save();
							$this->view->showMessage = $this->view->translate('INVITATION_MAIL_LIST_PART1_Sucessful');
							$isSentSuccessfully = true;
						}catch (Exception $e){
							//roll back...
							$this->view->showMessage = 'System Error!';
						}	
					}
				}
				if(!$isSentSuccessfully){
					$this->view->showMessage = $this->view->translate('INVITATION_MAIL_LIST_PART1_Fail');
				}

			}else{
				$this->view->showMessage = $this->view->translate('INVITATION_MAIL_LIST_PART1_DataError');
			}
		}

		//		$this->_helper->redirector('index','invitation',null,array('isSendMailSuccessful'=>$isSendMailSuccessful));
	}

	function reinviteAction(){
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("Friend_Invitations");
		$this->view->sendMailForm = new SendMailForm();
		
		$this->view->sendMailForm->resendemail->setValue($this->_request->getParam('resend_email'));
		$this->view->sendMailForm->resendemail->setAttribs(array("disabled"=>'disabled'));
		
		$langNamespace = new Zend_Session_Namespace('Lang');
		if($langNamespace->lang == 'en' || $langNamespace->lang == 'EN'){
			$this->view->sendMailForm->subject->setValue("Invitation to Wildfire from ".$this->_currentUser->email);
		}else{
			$this->view->sendMailForm->subject->setValue("您的朋友 ".$this->_currentUser->email."，邀请您加入星星火！");
		}
		$this->view->sendto = $this->_request->getParam('resend_email');
		
	}
	
	function resendAction(){
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("INVITATION_MAIL_SEND");
		//
		$form = new SendMailForm();
		$consumer = $this->_currentUser;
		$isSentSuccessfully = false;
		
		$sendto = $this->_request->getParam('sendto');
		
		if ($this->_request->isPost()) {//POST
			$formData = $this->_request->getPost();
			if ($form->isValid($formData)) {
						$currentTime = date("Y-m-d H:i:s");
						//get signup_auth_code
						$db = Zend_Registry::get('db');
						$select = $db->select();
						$select->from('signup_auth_code', array('id','auth_code'))
						->join('invitation_email', 'invitation_email.signup_auth_code_id = signup_auth_code.id', null)
						->where('invitation_email.consumer_id = ?', $this->_currentUser->id)
						->where('invitation_email.to = ?', $sendto);
						$signup_auth_code_array = $db->fetchAll($select);
						$signup_auth_code_id = $signup_auth_code_array[0]['id'];
						$signup_auth_code = $signup_auth_code_array[0]['auth_code'];
				
						
						//send mail
						$emailSubject = $this->view->translate('Invitation_Email_subject');
						$emailBody = $this->view->translate('Invitation_Email_body');
						$stringChange = array(
							'?USERNAME?' => $this->_currentUser['name'],
							'?EMAIL?' => $this->_currentUser['email'],
							'?MESSAGE?' => $form->getValue('message'),
							'?AUTHCODE?' => (string)$signup_auth_code);
						$emailSubject = strtr($emailSubject,$stringChange);
						$emailBody = strtr($emailBody,$stringChange);

						$config = Zend_Registry::get('config');
						$smtpSender = new Zend_Mail_Transport_Smtp(
								$config->smtp->friend->mail->server,
								array(
									'username'=> $config->smtp->friend->mail->username,
									'password'=> $config->smtp->friend->mail->password,
									'auth'=> $config->smtp->friend->mail->auth,
									'ssl' => $config->smtp->friend->mail->ssl,
			               			'port' => $config->smtp->friend->mail->port));
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
						$mail->setFrom($config->smtp->friend->mail->username, $consumer->name);
						$mail->addHeader('Reply-To', $consumer->email);
//												$mail->setFrom('yun_simon@163.com',$this->view->translate('Wildfire'));
						$mail->addTo($sendto);						
						//save into invitation_email
						$currentTime = date("Y-m-d H:i:s");
						$invitationEmailModel = new InvitationEmail();
						$invitationEmail = $invitationEmailModel->createRow();
						$invitationEmail->subject = $emailSubject;
						$invitationEmail->content = $form->getValue('message');
						$invitationEmail->consumer_id = $this->_currentUser->id;
						$invitationEmail->to = $sendto;
						$invitationEmail->signup_auth_code_id = $signup_auth_code_id;
						$invitationEmail->date = $currentTime;
						$invitationEmail->save();
 
						//send mail after saving
						$mail->send();	
						$this->view->showMessage = $this->view->translate('INVITATION_MAIL_LIST_PART1_Sucessful');
						$isSentSuccessfully = true;
	
						if(!$isSentSuccessfully){
							$this->view->showMessage = $this->view->translate('INVITATION_MAIL_LIST_PART1_Fail');
						}

			}else{
				$this->view->showMessage = $this->view->translate('INVITATION_MAIL_LIST_PART1_DataError');
			}
		}	
	}
}