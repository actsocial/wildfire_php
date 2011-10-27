<?php
include_once 'Indicate2Connect.php';
class EmailController extends MyController {
	
	
	function adminindexAction() {
		
		if($this->_request->getParam ( 'file' )){
			$this->_helper->layout->disableLayout();
			$file = explode('&',$this->_request->getParam ( 'file' ));
			$file = $file[0];
			$incomingEmailAttachments = new IncomingEmailAttachments();
			$row = $incomingEmailAttachments->fetchAll('id = '.$file)->toArray();
			header("Content-type:".$row[0]['type']);
			$this->view->file = $row[0]['content'];			
		}else{
	        $this->_helper->layout->setLayout("layout_admin");
			$this->rowsPerPage = 10;
			if ($this->_request->getParam ( 'page' )) {
				$this->curPage = $this->_request->getParam ( 'page' );
			} else {
				$this->curPage = 1;
			}
			//print_r($curPage);die;
			$content = array ();
			
			$incomingEmailModel = new IncomingEmail();
			$rows = $incomingEmailModel->fetchAll('incoming_email.exit=1')->toArray();
			$total = count($rows);
			
			$paginator = new Zend_Paginator ( new Zend_Paginator_Adapter_Array ( $rows ) );
	
			if($total>=$this->rowsPerPage*$this->curPage){
				$content = $incomingEmailModel->fetchAll('incoming_email.exit=1','crdate desc',$this->rowsPerPage,$this->rowsPerPage*($this->curPage-1))->toArray();
			}elseif ($total>($this->rowsPerPage*($this->curPage-1))){
				$content = $incomingEmailModel->fetchAll('incoming_email.exit=1','crdate desc',$total-$this->rowsPerPage*($this->curPage-1),$this->rowsPerPage*($this->curPage-1))->toArray();
				
			}
			if (count($content)){
	            $num = count($content);        
	            for ($i = 0; $i < $num; $i++) {
	            	if(strlen($content[$i]['attachments'])){
	            		$content[$i]['attachfile'] = explode(',',$content[$i]['attachments']);
	            	}else{
	            		$content[$i]['attachfile'] = '';
	            	}
	            }
			}
			$this->view->paginator = $paginator;
			$paginator->setCurrentPageNumber ( $this->curPage )->setItemCountPerPage ( $this->rowsPerPage );
			$this->view->controller = $this->_request->getControllerName ();
			$this->view->action = $this->_request->getActionName ();
			$this->view->content = $content;
			//print_r ($content );
			//die ();
			
		}
	}
	
	function adminrecieveAction(){	
		
        $this->_helper->layout->setLayout("layout_admin");
		$this->_mail = new Zend_Mail_Storage_Imap(array(
											'host'     => 'imap.gmail.com',
											'user'     => 'yangyang@wildfire.asia',
											'port'     => 993,
											'ssl'      => true,
									        'password' => 'liuZ838k')
					   );

		$this->unseen = $this->_mail->getStatusMessage(array('unseen'));
		if(count($this->unseen)){
			$this->processMail();
			$this->saveData();
		}
				
        $this->_helper->redirector('adminindex','email');
		
	}
	
	function adminreplyemailAction(){		
		$this->_helper->layout->disableLayout();
		$idValue = explode('&',$this->_request->getParam('id'));
		$id = $idValue[0];
		$errMsg = array();
		$this->view->sent = 0;
		$incomingEmailModel = new IncomingEmail();
		if (!$this->_request->isPost()){			
			$data = $incomingEmailModel->fetchRow('id = '.$id);
			$this->view->email = $data->email;
		}else{
			$postData = $this->_request->getPost();
			//var_dump($postData);die;
			$id = $postData['id'];

			if(strlen(trim($postData['subject']))==0){
				$errMsg[] = '标题不能为空！';
			}
			if(strlen(trim($postData['content']))==0){
				$errMsg[] = '内容不能为空！';
			}
			
			if(!count($errMsg)){
					$langNamespace = new Zend_Session_Namespace('Lang');
					$useHtmlEmail = false;
					$config = Zend_Registry::get('config');					
					$smtpSender = new Zend_Mail_Transport_Smtp(
								$config->smtp->invitation->mail->server,
								array(
									'username'=> $config->smtp->invitation->mail->username,
									'password'=> $config->smtp->invitation->mail->password,
									'auth'=> $config->smtp->invitation->mail->auth,
									'ssl' => $config->smtp->invitation->mail->ssl,
			               			'port' => $config->smtp->invitation->mail->port));
					Zend_Mail::setDefaultTransport($smtpSender);
					$mail = new Zend_Mail('utf-8');		
							
					if($langNamespace->lang == 'en' || $langNamespace->lang == 'EN'){
						$mail->setSubject($postData['subject']);
					}else{
						$mail->setSubject("=?UTF-8?B?".base64_encode($postData['subject'])."?=");
					}
					if($useHtmlEmail != null && $useHtmlEmail){
						$mail->setBodyHtml($postData['content']);
					}else{
						$mail->setBodyText($postData['content']);
					}

					$mail->setFrom($config->smtp->invitation->mail->username,$this->view->translate('Wildfire_bi_lang'));
					$mail->addTo($postData['email']);
					//print_r($mail);die;
					$mail->send();
					
					//save the reply information
					$incomingEmailReplyModel = new IncomingEmailReply();
					$row = $incomingEmailReplyModel->createRow();
					$row->crdate 				= date("Y-m-d H:i:s");
					$row->subject 				= $postData['subject'];
					$row->content 				= $postData['content'];
					$row->incoming_email_id 	= $id;
					$row->email   				= $postData['email'];
					$return = $row->save();
					//update reply_id field of the incoming email
					$incomingEmailModel->update(array('reply_id'=>$return),'id='.$id);
					$this->view->sent = 1;
			}

           
            $this->view->email = $postData['email'];			
		}
        $this->view->message = $errMsg;
		$this->view->id    = $id;
		//print_r($data);die;		
	}
	
	function adminconverttoreportAction(){
		$this->_helper->layout->disableLayout();
		$idValue = explode('&',$this->_request->getParam('id'));
		$id = $idValue[0];
		
		$emailModel = new IncomingEmail();
		$emailData = $emailModel->fetchRow('id='.$id);
		
		$consumerModel = new Consumer();
		$consumerData = $consumerModel->fetchRow('email like "%'.$emailData->email .'%"');
		
		if($consumerData!=NULL){
			$this->view->consumer = true;
		}else{
			$this->view->consumer = false;
		}
		
		$campaignModel = new Campaign();
		$campaignData = $campaignModel->fetchRow('create_date < "'.date('Y-m-d H:i:s').'" and "'.date('Y-m-d H:i:s').'"<expire_date');
		
		if($this->view->consumer){
				$campaigninvitationModel = new CampaignInvitation();
				$campaigninvitationData = $campaigninvitationModel->fetchRow('campaign_id = '.$campaignData->id.' and consumer_id='.$consumerData->id);
				if($campaigninvitationData){
					$this->view->invitation = true;
				}else{
					$this->view->invitation = false;
				}
		}
		
		if( $this->view->consumer&&$this->view->invitation ){
				$emailSession 		= new Zend_Session_Namespace('IncomingEmail');
				$emailSession->email = $emailData->email;
			
				$langNamespace = new Zend_Session_Namespace('Lang');
				$lang = $langNamespace->lang;
				
				if ($lang=='en'){
					$surveyId =	$campaignData->i2_survey_id_en;
				}else{
					$surveyId =	$campaignData->i2_survey_id;
				}
				
				$this->view->file = "./surveys/".$surveyId.".phtml";
		}	
		$this->_helper->layout->setLayout("layout_questionnaire");
	}
	
	
	function successconvertAction(){
		$this->_helper->layout->disableLayout();
	}
	
	function adminviewreplyAction(){
		$this->_helper->layout->disableLayout();
		$idValue = explode('&',$this->_request->getParam('reply_id'));
		$id = $idValue[0];
		$incomingEmailReplyModel = new IncomingEmailReply();
		$row = $incomingEmailReplyModel->fetchRow('id='.$id);
		$this->view->data = $row;
		//var_dump($row);die;		
	}
	
	function ajaxAction(){
		if($this->_request->isPost()){
			$postData = $this->_request->getPost();
			$incomingEmailModel = new IncomingEmail();
			switch($postData['action']){
				case "seen"   : $incomingEmailModel->update(array('unseen'=>0),'id in('.$postData['id'].')');
				                break;
				case "unseen" : $incomingEmailModel->update(array('unseen'=>1),'id in('.$postData['id'].')');
				                break;
				case "delete" : $incomingEmailModel->update(array('exit'  =>0),'id in('.$postData['id'].')');
				                $this->_helper->redirector('adminindex','email');
				                break;
				default		  : break;
			}
			//print_r($postData);
		}
				
		die;
	}
	
	function _contentDecoder($encoding, $content) {
		switch ($encoding) {
			case 'quoted-printable' :
				$result = quoted_printable_decode ( $content );
				break;
			case 'base64' :
				$result = base64_decode ( $content );
				break;
			default :
				$result = $content;
				break;
		}
		return $result;
	}
		
	function processMail() {
		if ($this->_mail instanceof Zend_Mail_Storage_Imap) {
			foreach ( $this->unseen as $key=>$id ) {		
				$messageNum = $id;
				$message = $this->_mail->getMessage($messageNum);
				$rawMail = $this->_mail->getRawHeader ( $messageNum ) . $this->_mail->getRawContent ( $messageNum );
				$received = $message->received;
				$date = date ( 'Y-m-d H:i:s', strtotime ( $message->date ) );
				$messageId = $message->getHeader ( 'message-id' );
				//remove <> from message id
				$messageId = (preg_match ( '|< (.*?)>|', $messageId, $regs )) ? $regs [1] : $messageId;
				$from = $message->from;
				
				$fromparts = explode ( '?', $from );
				if (count ( $fromparts ) > 4) {
					$email = (preg_match ( '|<(.*?)>|', $fromparts [4], $regs ))?$regs[1]:'';
					$fromparts [4] = str_replace ( array ('=', '<', '>' ), array ('', '(', ')' ), $fromparts [4] );
					if ($fromparts [2] == 'Q') {
						$from = quoted_printable_decode ( $fromparts [3] ) . $fromparts [4];
					}
					if ($fromparts [2] == 'B') {
						$from = base64_decode ( $fromparts [3] ) . $fromparts [4];
					}
				} else {
					$email = (preg_match ( '|<(.*?)>|', $from, $regs ))?$regs[1]:'';					
					$from = str_replace ( array ('<', '>' ), array ('(', ')' ), $from );
					
				}
				//$subject      = ($message->subject);
				$charCode = 'utf-8';
				$subject = $message->subject;
				if(preg_match('/utf-8/i',$subject)||preg_match('/gb2312/i',$subject)||preg_match('/gbk/i',$subject)){
					$subjectarray = explode(" ",$subject);
					if(count($subjectarray)){
						$subject = "";
						foreach($subjectarray as $val){
							$subparts = array();							
								preg_match_all("/(=\?)(.*)(\?=)/",$val,$subparts);							
								$parts = explode('?',$subparts[2][0]);
								$charCode = $parts [0];
								if ($parts [1] == 'Q'||$parts [1] == 'q') {
										$subject .= iconv ($parts [0],'utf-8',quoted_printable_decode ( $parts [2] )) ;
								}
								if ($parts [1] == 'B'||$parts [1] == 'b') {
										$subject .= iconv ($parts [0],'utf-8',base64_decode ( $parts [2] ));
								}	
						}
					}
				}
				$to = $message->to;
				$plainContent = null;
				$htmlContent = null;
				$attachments = array ();
				/**
				 * Check if the message has multiple parts
				 */
				if ($message->isMultipart ()) {
					/**
					 * We have a multipart message
					 * lets extract the plain content,
					 * html content, and attachments
					 */
					foreach ( new RecursiveIteratorIterator ( $message ) as $part ) {
						try {
							$headers = $part->getHeaders();
							switch (strtok ( $part->contentType, ';' )) {
								case 'text/plain' :
									$plainContent = $this->_contentDecoder ( $headers['content-transfer-encoding'], $part->getContent () );
									//$plainContent = $part->getContent () ;
									break;
								case 'text/html' :
									$htmlContent  = $this->_contentDecoder ( $headers['content-transfer-encoding'], $part->getContent () );
									break;
								default : //attachment handle
									//print_r($headers);
									$type = strtok ( $part->contentType, ';' );
									$fileName = 'attach';
									$attachment =  $this->_contentDecoder ( $headers['content-transfer-encoding'], $part->getContent () );
									//print_r($attachment);die;
									
									$attachments [] = array ('file_name' => $fileName, 'type' => $type, 'content' => $attachment );
									break;
							}
						} catch ( Zend_Mail_Exception $e ) {
							echo "$e \n";
						}
					}
				} else {
					$plainContent = $message->getContent ();
				}
				$this->_inbox [] = array (  'messageNumber' => $messageNum, 
											'rawMail' => $rawMail, 							
											'received' => $received, 							
											'date' => $date, 							
											'messageId' => $messageId, 							
											'from' => $from, 				
											'email' => $email,							
											'subject' => $subject, 							
											'to' => $to, 							
											'plainContent' => iconv($charCode,'utf-8', $plainContent ), 							
											'htmlContent' =>  iconv($charCode,'utf-8',$htmlContent), 																		'attachments' => $attachments );
			}
		} else {
			$this->_inbox = false;
		}
	}
	
	function saveData(){
		$currentTime = date("Y-m-d H:i:s");
		if ($this->_inbox) {
			$incomingEmailModel = new IncomingEmail();
			foreach ($this->_inbox as $val){
					if(strlen($val['htmlContent'])!=0){
						$valcontent = $val['htmlContent'];
					}else{
						$valcontent = $val['plainContent'];
					}	
					$straid = '';				
					if(count($val['attachments'])){
						$incomingEmailAttachmentsModel = new IncomingEmailAttachments();
						foreach($val['attachments'] as $each){
							$rowincomging = $incomingEmailAttachmentsModel->createRow();
							$rowincomging->name 		= $each['file_name'];
							$rowincomging->type 		= $each['type'];
							$rowincomging->content 		= $each['content'];
							$straid 				    .= (strlen($straid))?','.$rowincomging->save():$rowincomging->save();
						}
					}
					$row = $incomingEmailModel->createRow();
					$row->email = $val['email'];
					$row->from = $val['from'];
					$row->crdate = $currentTime;
					$row->to = $val['to'];
					$row->date = $val['date'];
					$row->subject = $val['subject'];
					$row->content = $valcontent;
					$row->attachments = $straid;
					$row->save();					
			}
		}
	}
	
	public function adminsmtpsettingAction(){
		$this->_helper->layout->setLayout("layout_admin");
		//get config
		$config = Zend_Registry::get('config');
		$selectArray = array('select_1server' => $config->smtp->select_1->mail->server,
							'select_1username' => $config->smtp->select_1->mail->username,
							'select_1password' => $config->smtp->select_1->mail->password,
							'select_1auth' => $config->smtp->select_1->mail->auth,
							'select_1ssl' => $config->smtp->select_1->mail->ssl,
							'select_1port' => $config->smtp->select_1->mail->port,
							'select_1id' => $config->smtp->select_1->mail->id,
							
							'select_2server' => $config->smtp->select_2->mail->server,
							'select_2username' => $config->smtp->select_2->mail->username,
							'select_2password' => $config->smtp->select_2->mail->password,
							'select_2auth' => $config->smtp->select_2->mail->auth,
							'select_2ssl' => $config->smtp->select_2->mail->ssl,
							'select_2port' => $config->smtp->select_2->mail->port,
							'select_2id' => $config->smtp->select_2->mail->id,
							
							'select_3server' => $config->smtp->select_3->mail->server,
							'select_3username' => $config->smtp->select_3->mail->username,
							'select_3password' => $config->smtp->select_3->mail->password,
							'select_3auth' => $config->smtp->select_3->mail->auth,
							'select_3ssl' => $config->smtp->select_3->mail->ssl,
							'select_3port' => $config->smtp->select_3->mail->port,
							'select_3id' => $config->smtp->select_3->mail->id);
		$this->view->selectArray = $selectArray;
		if ($this->_request->isPost()) {//POST
			//set config
			$formData = $this->_request->getPost();
			$configselectedArray = array('invitation' => $formData['Invitation'],
										'friend' => $formData['Friend'],
										'report' => $formData['Report'],
										'welcome' => $formData['Welcome'],
										'postcampaign' => $formData['PostCampaign'],
										'forgetpassword' => $formData['ForgetPassword']);
			//get
			$file_handle = fopen("../application/config.ini", "r");
			$configStr = '';
			while (!feof($file_handle)) {
				$configStr .= fgets($file_handle);
			}
			fclose($file_handle);
			//set
			for($i = 1; $i<= count($configselectedArray); $i++){
				$tempArray = each($configselectedArray);
				$key = $tempArray['key'];
				$value= $tempArray['value'];
				$configStr = preg_replace("/smtp.".$key.".mail.server.*\n/", "smtp.".$key.".mail.server = ".$selectArray[$value.'server']."\n", $configStr);
				$configStr = preg_replace("/smtp.".$key.".mail.username.*\n/", "smtp.".$key.".mail.username = ".$selectArray[$value.'username']."\n", $configStr);
				$configStr = preg_replace("/smtp.".$key.".mail.password.*\n/", "smtp.".$key.".mail.password = ".$selectArray[$value.'password']."\n", $configStr);
				$configStr = preg_replace("/smtp.".$key.".mail.auth.*\n/", "smtp.".$key.".mail.auth = ".$selectArray[$value.'auth']."\n", $configStr);
				$configStr = preg_replace("/smtp.".$key.".mail.ssl.*\n/", "smtp.".$key.".mail.ssl = ".$selectArray[$value.'ssl']."\n", $configStr);
				$configStr = preg_replace("/smtp.".$key.".mail.port.*\n/", "smtp.".$key.".mail.port = ".$selectArray[$value.'port']."\n", $configStr); 
				$configStr = preg_replace("/smtp.".$key.".mail.selected.*\n/", "smtp.".$key.".mail.selected = ".$selectArray[$value.'id']."\n", $configStr); 
			}		
			//save
			file_put_contents("../application/config.ini", $configStr);
			
			$this->_helper->redirector('adminprepareinvitations','campaigninvitation');
			
		}

		$config = Zend_Registry::get('config');
		$this->view->invitationSelected = $config->smtp->invitation->mail->selected;
		$this->view->friendSelected = $config->smtp->friend->mail->selected;
		$this->view->reportSelected = $config->smtp->report->mail->selected;
		$this->view->welcomeSelected = $config->smtp->welcome->mail->selected;
		$this->view->postcampaignSelected = $config->smtp->postcampaign->mail->selected;
		$this->view->forgetpasswordSelected = $config->smtp->forgetpassword->mail->selected;
		
		$this->view->select1SMTPServer = $config->smtp->select_1->mail->username;
		$this->view->select2SMTPServer = $config->smtp->select_2->mail->username;
		$this->view->select3SMTPServer = $config->smtp->select_3->mail->username;
		
	}

}