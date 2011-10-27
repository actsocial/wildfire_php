<?php

class MailController extends MyController {
	function adminlistAction() {

		$this->rowsPerPage = 4;
		if ($this->_request->getParam ( 'page' )) {
			$this->curPage = $this->_request->getParam ( 'page' );
		} else {
			$this->curPage = 1;
		}
		$messages = $this->_mail->getUniqueId ();
		//print_r($curPage);die;
		$content = array ();
		
		$paginator = new Zend_Paginator ( new Zend_Paginator_Adapter_Array ( $messages ) );
		if (count ( $messages )) {
			for($i = ($this->curPage - 1) * $this->rowsPerPage + 1; $i <= $this->curPage * $this->rowsPerPage; $i ++) {
				$message [$i] = $this->_mail->getMessage ( $i );
				$content [$i] = $message [$i]->getContent ();
			}
		}
		$this->view->paginator = $paginator;
		$paginator->setCurrentPageNumber ( $this->curPage )->setItemCountPerPage ( $this->rowsPerPage );
		$this->view->controller = $this->_request->getControllerName ();
		$this->view->action = $this->_request->getActionName ();
		$this->view->content = $content;
		$this->view->message = $message;

		$this->view->inbox = $this->_inbox;
		//print_r ( $this->_inbox );
		//die ();
	}
	
	function adminrecieveAction(){
		
		$this->_mail = new Zend_Mail_Storage_Imap(array(
											'host'     => 'imap.gmail.com',
											'user'     => 'bingquan3846@gmail.com',
											'port'     => 993,
											'ssl'      => true,
									        'password' => '830208bingquan')
					   );
			
		/*if (! isset ( $this->_mail )) {
			$this->_mail = new Zend_Mail_Storage_Pop3 ( array ('host' => 'pop.163.com', 'user' => 'ham_bao@163.com', 'password' => '830208' ) );
		}		
		$this->_mail = new Zend_Mail_Storage_Imap ( array ('host' => 'imap.163.com', 'user' => 'ham_bao@163.com', 'password' => '830208' ,'port'=>143) );
		*/
		$this->unseen = $this->_mail->getStatusMessage(array('unseen'));
		if(count($this->unseen)){
			$this->processMail();
			$this->saveData();
		}

		//print_r($this->_inbox);die;
		
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
		if ($this->_mail instanceof Zend_Mail_Storage_Imap && $this->_mail->countMessages () > 0) {
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
					$fromparts [4] = str_replace ( array ('=', '<', '>' ), array ('', '(', ')' ), $fromparts [4] );
					if ($fromparts [2] == 'Q') {
						$from = quoted_printable_decode ( $fromparts [3] ) . $fromparts [4];
					}
					if ($fromparts [2] == 'B') {
						$from = base64_decode ( $fromparts [3] ) . $fromparts [4];
					}
				} else {
					$from = str_replace ( array ('<', '>' ), array ('(', ')' ), $from );
				}
				//$subject      = ($message->subject);
				$subject = $message->subject;
				$subjectarray = explode(" ",$subject);
				if(count($subjectarray)){
					$subject = "";
					foreach($subjectarray as $val){
						$subparts = array();
						preg_match_all("/(=\?)(.*)(\?=)/",$val,$subparts);
						
						$parts = explode('?',$subparts[2][0]);
						if ($parts [1] == 'Q'||$parts [1] == 'q') {
								$subject .= quoted_printable_decode ( $parts [2] ) ;
						}
						if ($parts [1] == 'B'||$parts [1] == 'b') {
								$subject .= base64_decode ( $parts [2] );
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
							switch (strtok ( $part->contentType, ';' )) {
								case 'text/plain' :
									$plainContent = $this->_contentDecoder ( $part->getHeader ( 'content-transfer-encoding' ), $part->getContent () );
									break;
								case 'text/html' :
									$htmlContent = $this->_contentDecoder ( $part->getHeader ( 'content-transfer-encoding' ), $part->getContent () );
									break;
								default : //attachment handle
									$type = strtok ( $part->contentType, ';' );
									$fileName = $part->getHeader ( 'content-description' );
									$attachment = $this->_contentDecoder ( $part->getHeader ( 'content-transfer-encoding' ), $part->getContent () );
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
							
											'subject' => $subject, 
							
											'to' => $to, 
							
											'plainContent' => quoted_printable_decode ( $plainContent ), 
							
											'htmlContent' => $htmlContent, 
							
											'attachments' => $attachments );
			}
		} else {
			$this->_inbox = false;
		}
	}
	
	function saveData(){
		$currentTime = date("Y-m-d H:i:s");
		if ($this->_inbox) {
			foreach ($this->_inbox as $val){
					if(strlen($val['plainContent'])!=0){
						$valcontent = $val['plainContent'];
					}else{
						$valcontent = $val['htmlContent'];
					}					
					$db = Zend_Registry::get('db');
					$insertSql = $db->prepare("insert into incoming_email(message_id,incoming_email.from,crdate,incoming_email.to,incoming_email.date,subject,content) values ('$val[messageId]','$val[from]','$currentTime','$val[to]','$val[date]','$val[subject]','$valcontent')");
					$insertSql->execute();
			}
		}
	}
	


}