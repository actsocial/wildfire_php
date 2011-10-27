<?php
include ("services/PointService.php");
include ("phprpc/phprpc_server.php");
include ("phprpc/phprpc_client.php"); 

class Ws_WsController extends Zend_Controller_Action
{
	function serverAction()
	{

		
//		ini_set("soap.wsdl_cache_enabled", "0");
//		$soap = new SoapServer("wf.wsdl"); // this current file here
//		$soap->setClass('PointService');
//		$soap->handle();
		$server = new PHPRPC_Server();  
		$server->add('decrypt', new PointService()); 
		$server->add('verifyAccount', new PointService()); 
		$server->add('exchange', new PointService()); 
		$server->add('getCurrentPoint', new PointService());
		$server->setCharset('UTF-8'); 
		$server->start();
		
		
		$this->_helper->layout->disableLayout();
	}

//	function wsdlAction(){
//		$this->_helper->layout->disableLayout();
//		ini_set("soap.wsdl_cache_enabled", "0");
//		include_once "services/PointService.php";
//		$autodiscover = new Zend_Soap_AutoDiscover();
//		$autodiscover->setClass('PointService');
//		$autodiscover->handle();
//	}

	function clientAction()
	{
		$this->_helper->layout->disableLayout();
		if ($this->_request->isPost()) {//POST
			ini_set("soap.wsdl_cache_enabled", "0");
//			$client = new PHPRPC_Client("http://home.xingxinghuo.cn/public/ws/ws/server");   
			$client = new PHPRPC_Client('http://localhost/wildfire/public/ws/ws/server'); 
			
			$this->view->result = null;
			$interface = $this->_request->getParam("interface");
			try {
				switch($interface){
					case 'decrypt':
						$this->view->inter = "decrypt";
						$this->view->paramter1 = $this->_request->getParam("paramter1");
						$this->view->paramter2 = $this->_request->getParam("paramter2");
						$this->view->paramter3 = $this->_request->getParam("paramter3");
						$this->view->paramter4 = $this->_request->getParam("paramter4");
						$this->view->paramter5 = $this->_request->getParam("paramter5");
						$this->view->result = $client->decrypt($this->view->paramter1,$this->view->paramter2,
												$this->view->paramter3,$this->view->paramter4,$this->view->paramter5);
						break;
					case 'verifyAccount':
						$this->view->inter = "verifyAccount";
						$this->view->paramter1 = $this->_request->getParam("paramter1");
						$this->view->paramter2 = $this->_request->getParam("paramter2");
						$this->view->result = $client->verifyAccount($this->view->paramter1, $this->view->paramter2);
						break;
					case 'exchange':
						$this->view->inter = "exchange";
						$this->view->paramter1 = $this->_request->getParam("paramter1");
						$this->view->paramter2 = $this->_request->getParam("paramter2");
						$this->view->paramter3 = $this->_request->getParam("paramter3");
						$this->view->result = $client->exchange($this->view->paramter1, $this->view->paramter2, $this->view->paramter3);
						break;
					case 'getCurrentPoint':
						$this->view->inter = "getCurrentPoint";
						$this->view->paramter1 = $this->_request->getParam("paramter1");
						$this->view->paramter2 = $this->_request->getParam("paramter2");
						$para = array('userName'=>$this->view->paramter1, 'password'=>$this->view->paramter2);
						$this->view->result = $client->getCurrentPoint($this->view->paramter1, $this->view->paramter2);
						break;
					default:
						break;
				}
			} catch(Zend_Exception  $e) {
				echo $e->getMessage();
			}
				
		}else{
			ini_set("soap.wsdl_cache_enabled", "0");
			$client = new PHPRPC_Client("http://home.xingxinghuo.cn/public/ws/ws/server"); 
//			$client = new PHPRPC_Client('http://localhost/wildfire/public/ws/ws/server'); 
			$this->view->inter = "decrypt";
			$this->view->paramter1 = $this->_request->getParam("site");
			$this->view->paramter2 = $this->_request->getParam("username");
			$this->view->paramter3 = $this->_request->getParam("password");
			$this->view->paramter4 = $this->_request->getParam("realname");
			$this->view->paramter5 = $this->_request->getParam("auth");
			$this->view->result = $client->decrypt($this->view->paramter1,$this->view->paramter2,$this->view->paramter3,$this->view->paramter4,$this->view->paramter5);
						
		}
		$key = "ILoveXingXingHuo";
		$value = "yun_simon@163.com";
//		$value = "test@163.com";
		$value2 = "96e79218965eb72c92a549dd5a330112";
		$value3 = "xingxinghuo";
		$value4 = "星星火会员";
//		$value4 = mb_convert_encoding($value4, "UTF-8");
		$value5 = "xingxinghuodecrypt";
		$td = mcrypt_module_open('tripledes', '', 'ecb', '');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $key, $iv);
		$ret = base64_encode(mcrypt_generic($td, $value));
		$ret2 = base64_encode(mcrypt_generic($td, $value2));
		$ret3 = base64_encode(mcrypt_generic($td, $value3));
		$ret4 = base64_encode(mcrypt_generic($td, $value4));
		$ret5 = base64_encode(mcrypt_generic($td, $value5));

		$ret_de = mdecrypt_generic($td, base64_decode($ret));
		$ret2_de = mdecrypt_generic($td, base64_decode($ret2));
		$ret3_de = mdecrypt_generic($td, base64_decode($ret3));
		$ret4_de = mdecrypt_generic($td, base64_decode(($ret4)));
		$ret5_de = mdecrypt_generic($td, base64_decode($ret5));

		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		Zend_Debug::dump('username:'.$ret);
		Zend_Debug::dump('password:'.$ret2);
		Zend_Debug::dump('site:'.$ret3);
		Zend_Debug::dump('name:'.$ret4);
		Zend_Debug::dump($ret5);
		Zend_Debug::dump($ret_de);
		Zend_Debug::dump($ret2_de);
		Zend_Debug::dump($ret3_de);
		Zend_Debug::dump($ret4_de);
		Zend_Debug::dump($ret5_de);
		//		$this->_helper->redirector('client','ws');


		//		try {
		//			$client = new SoapClient("http://localhost/wildfire/public/ws/ws/server?WSDL",array("trace" => 1)); // Servers WSDL Location
		//
		//			$string =  $client->helloWorld('Wildfire');
		//
		//			Zend_Debug::dump($string);
		//
		//		} catch(Zend_Exception  $e) {
		//			echo $e->getMessage();
		//		}
	}
}
