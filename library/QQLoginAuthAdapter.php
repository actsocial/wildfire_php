<?php
class QQLoginAuthAdapter implements Zend_Auth_Adapter_Interface {
	protected $_qqid;
	public function __construct($_qqid) {
		$this->_qqid = $_qqid;
	}
	
	public function authenticate() {
		$db = Zend_Registry::get ( 'db' );
		$rs = $db->fetchAll ( "SELECT id  FROM consumer WHERE qqid=:qqid and state='ACTIVE'", array ('qqid' => $this->_qqid) );
		if (count ( $rs ) > 0) {
			return new Zend_Auth_Result(Zend_Auth_Result :: SUCCESS, $this->_qqid);
		} else {
			return new Zend_Auth_Result(Zend_Auth_Result :: FAILURE, $this->_qqid);
		}
	}
}
?>