<?php
require_once 'Zend/Mail/Transport/Smtp.php';
require_once 'Zend/Mail.php';

class FacebookLoginAuthAdaptor implements Zend_Auth_Adapter_Interface {
	protected $_facebookid, $_facebookname,$_facebookemail;
	public function __construct($_facebookid, $_facebookname,$_facebookemail) {
		$this->_facebookid = $_facebookid;
		$this->_facebookname = $_facebookname;
		$this->_facebookemail = $_facebookemail;
	}
	
	public function authenticate() {
		$db = Zend_Registry::get ( 'db' );
		$rs = $db->fetchAll ( "SELECT id  FROM consumer WHERE facebookid=:facebookid and state='ACTIVE'", array ('facebookid' => $this->_facebookid) );
		if (count ( $rs ) > 0) {
			return new Zend_Auth_Result(Zend_Auth_Result :: SUCCESS, $this->_facebookid);
		} else {
			if(isset($this->_facebookid)&&''!=$this->_facebookid){
				$pass = $this->create_password();

				$consumerModel = new Consumer();
				$consumerModel->insert(array('name'=>$this->_facebookname,'password'=>md5($pass),'email'=>$this->_facebookemail,'facebookid'=>$this->_facebookid,'state'=>'ACTIVE'));

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
				$emailBody = $this->view->translate('INVITE_FACEBOOK_EMAIL_TEMPLATE_BODY');
				$emailSubject =$this->view->translate('INVITE_FACEBOOK_EMAIL_TEMPLATE_SUBJECT');
				$emailBody = strtr($emailBody,$stringChange);
				$mail->addHeader('Reply-To', $consumer->email);
				$mail->setBodyText((string)$emailBody);
				$mail->setSubject($emailSubject);
				$mail->setFrom($config->smtp->friend->mail->username, "Wildfire");
				// $mail->addHeader('Reply-To', $consumer->email);
//						$mail->setFrom('yun_simon@163.com',$this->view->translate('Wildfire'));
				$mail->addTo($this->_facebookemail);
				$mail->send();	
			}
			return new Zend_Auth_Result(Zend_Auth_Result :: FAILURE, $this->_facebookid);
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
}
?>