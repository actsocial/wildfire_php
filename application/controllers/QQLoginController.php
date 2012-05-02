<?php
include_once( 'comm/qqconfig.php' );
include_once("comm/utils.php");
require_once 'QQLoginAuthAdapter.php';
require_once APPLICATION_PATH . '/models/Log.php';
class QQLoginController extends Zend_Controller_Action{
	
	function indexAction(){
		$_SESSION['state'] = md5(uniqid(rand(), TRUE));
		$login_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id=" 
        . $_SESSION["appid"] . "&redirect_uri=" . urlencode($_SESSION["callback"])
        . "&state=" . $_SESSION['state']
        . "&scope=".$_SESSION["scope"];
    	header("Location:$login_url");
	}
	
	function callbackAction(){
		
// 		if($this->_request->getParam('state')== $_SESSION['state']) //csrf
// 	    {
	        $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&"
	            . "client_id=" . $_SESSION["appid"]. "&redirect_uri=" . urlencode($_SESSION["callback"])
	            . "&client_secret=" . $_SESSION["appkey"]. "&code=" . $_REQUEST["code"];
	
	        $response = get_url_contents($token_url);
	        if (strpos($response, "callback") !== false)
	        {
	            $lpos = strpos($response, "(");
	            $rpos = strrpos($response, ")");
	            $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
	            $msg = json_decode($response);
	            if (isset($msg->error))
	            {
	                echo "<h3>error:</h3>" . $msg->error;
	                echo "<h3>msg  :</h3>" . $msg->error_description;
	                exit;
	            }
	        }
			
	        $params = array();
	        parse_str($response, $params);
	
	        //debug
	        //print_r($params);
	
	        //set access token to session
	        $_SESSION["access_token"] = $params["access_token"];
	        require_once("user/get_user_info.php");
	        $graph_url = "https://graph.qq.com/oauth2.0/me?access_token=". $_SESSION['access_token'];
		
			$str  = get_url_contents($graph_url);
			if (strpos($str, "callback") !== false)
			{
				$lpos = strpos($str, "(");
				$rpos = strrpos($str, ")");
				$str  = substr($str, $lpos + 1, $rpos - $lpos -1);
			}
		
			$me = json_decode($str);
			if (isset($me->error))
			{
				echo "<h3>error:</h3>" . $me->error;
				echo "<h3>msg  :</h3>" . $me->error_description;
				exit;
			}
		
			//debug
			//echo("Hello " . $user->openid);
		
			//set openid to session
			$_SESSION["openid"] = $me->openid;
	        
	        $user  = get_user_info();
	        $uid = $me->openid;
			$adapter = new QQLoginAuthAdapter($uid);
			$auth = Zend_Auth :: getInstance();
			$result = $auth->authenticate($adapter);
			$consumerModel = new Consumer();
			$db = Zend_Registry :: get('db');
			$consumer_id = $db->fetchOne("SELECT id FROM consumer WHERE qqid = :temp and state='ACTIVE'", array (
							'temp' => $uid
			));
			$consumer = $consumerModel->find($consumer_id)->current();
			if($result->isValid()) {
				$authNamespace = new Zend_Session_Namespace('Zend_Auth');
				$authNamespace->user = $consumer;
				$authNamespace->role = 'consumer';
				$logModel = new Log();
				$logId = $logModel->insert(array (
								'consumer_id' => $consumer->id,
								'date' => date("Y-m-d H:i:s"),
								'event' => 'LOGIN'
				));
				$this->_helper->redirector('index', 'home');
			}else{
				$this->_helper->redirector('register', 'register');
			}
// 	    }
// 	    else 
// 	    {
// 	        echo("The state does not match. You may be a victim of CSRF.");
// 	    }
	}
	
}