<?php
require_once("soap-wsse.php");

class SecureSoapClient extends SoapClient {

	protected $_username;
	protected $_password;
	
	public function __construct($wsdl, $options = null, $username, $password) {
		
		parent::__construct($wsdl, $options);
		
		$this->_username = $username;
		$this->_password = $password;
		
	}
	
	public function __doRequest($request, $location, $saction, $version) {
		
		$doc = new DOMDocument('1.0');
		$doc->loadXML($request);

		$objWSSE = new WSSESoap($doc);

		/* add Timestamp with no expiration timestamp */
		//$objWSSE->addTimestamp();

		$objWSSE->addUserToken($this->_username, $this->_password, true);
		 
		//Zend_Debug::dump($objWSSE->saveXML());
		
		return parent::__doRequest($objWSSE->saveXML(), $location, $saction, $version);
	}
}