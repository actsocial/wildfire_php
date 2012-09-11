<?php
class WeiboLoginAuthAdapter implements Zend_Auth_Adapter_Interface {
	protected $_weiboid;
	public function __construct($_weiboid) {
		$this->_weiboid = $_weiboid;
	}
	
	public function authenticate() {
		$db = Zend_Registry::get ( 'db' );
		$rs = $db->fetchAll ( "SELECT id  FROM consumer WHERE weiboid=:weiboid and state='ACTIVE'", array ('weiboid' => $this->_weiboid) );
		if (count ( $rs ) > 0) {
			return new Zend_Auth_Result(Zend_Auth_Result :: SUCCESS, $this->_weiboid);
		} else {
			$consumerModel = new Consumer();
			$consumerModel->insert(array('name'=>'微博用户','weiboid'=>$this->_weiboid,'state'=>'ACTIVE'));
			return new Zend_Auth_Result(Zend_Auth_Result :: FAILURE, $this->_weiboid);
		}
	}
}
?>