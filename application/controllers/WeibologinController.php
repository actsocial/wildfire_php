<?php
include_once( 'weiboconfig.php' );
include_once( 'saetv2.ex.class.php' );
require_once 'WeiboLoginAuthAdapter.php';
require_once APPLICATION_PATH . '/models/Log.php';
class WeibologinController extends MyController{
	
	function indexAction(){
		$o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
		
		$login_url = $o->getAuthorizeURL( WB_CALLBACK_URL );
		header("Location:$login_url");
	}
	
	function callbackAction(){
		$o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
		$token = null;
		if (isset($_REQUEST['code'])) {
			$keys = array();
			$keys['code'] = $_REQUEST['code'];
			$keys['redirect_uri'] = WB_CALLBACK_URL;
			try {
				$token = $o->getAccessToken( 'code', $keys );
			} catch (OAuthException $e) {
			}
		}
		if ($token) {
			$tokenNamespace = new Zend_Session_Namespace('token');
			$tokenNamespace->token = $token;
			setcookie( 'weibojs_'.$o->client_id, http_build_query($token) );
			$c = new SaeTClientV2( WB_AKEY , WB_SKEY , $token['access_token'] );
			$uid_get = $c->get_uid();
			$uid = $uid_get['uid'];
			$db = Zend_Registry :: get('db');
			
			$adapter = new WeiboLoginAuthAdapter($uid);
			$auth = Zend_Auth :: getInstance();
			$result = $auth->authenticate($adapter);
			$consumerModel = new Consumer();
			
			$consumer_id = $db->fetchOne("SELECT id FROM consumer WHERE weiboid = :temp and state='ACTIVE'", array (
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
			
		}
	}
	
}