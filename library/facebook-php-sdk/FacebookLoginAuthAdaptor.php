<?php
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
				$consumerModel = new Consumer();
				$consumerModel->insert(array('name'=>$this->_facebookname,'email'=>$this->_facebookemail,'facebookid'=>$this->_facebookid,'state'=>'ACTIVE'));
			}
			return new Zend_Auth_Result(Zend_Auth_Result :: FAILURE, $this->_facebookid);
		}
	}
}
?>