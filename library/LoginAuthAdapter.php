<?php
class LoginAuthAdapter implements Zend_Auth_Adapter_Interface {
	protected $_login;
	protected $_password;
	public function __construct($login, $password) {
		$this->_login = $login;
		$this->_password = $password;
	}
	
	public function authenticate() {
		$db = Zend_Registry::get ( 'db' );
	    $rs = $db->fetchAll ( "SELECT id  FROM consumer WHERE (email=:login or login_phone=:login) and password = md5(:password) and state='ACTIVE'", array ('login' => $this->_login, 'password'=> $this->_password ) );
		if (count ( $rs ) > 0) {
		  return new Zend_Auth_Result(Zend_Auth_Result :: SUCCESS, $this->_login);
		} else {
		  return new Zend_Auth_Result(Zend_Auth_Result :: FAILURE, $this->_login);
		}
	}
}
?>
