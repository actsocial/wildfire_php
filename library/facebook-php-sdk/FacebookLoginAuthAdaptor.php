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
		$consumer = $db->fetchOne("SELECT *  FROM consumer WHERE facebookid=:facebookid", array('facebookid'=>$this->_facebookid));
		if($consumer) {
			return new Zend_Auth_Result(Zend_Auth_Result :: SUCCESS, $this->_facebookid);
		}else {
			$consumer = $db->fetchOne("SELECT *  FROM consumer WHERE email=:email", array('email'=>$this->_facebookemail));
			if($consumer) {
				$consumer->facebookid = $this->_facebookid;
				$consumer.save();
				return new Zend_Auth_Result(Zend_Auth_Result :: SUCCESS, $this->_facebookid);
			}else {
				return new Zend_Auth_Result(Zend_Auth_Result :: FAILURE, $this->_facebookid);
			}
		}
	}
}
?>