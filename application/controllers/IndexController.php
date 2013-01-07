<?php
require 'facebook-php-sdk/facebook.php';
include_once( 'facebook-php-sdk/facebookconfig.php' );
class IndexController extends MyController
{
	function indexAction()
	{
		//2011-05-24 redirect to home if consumer login already
		if (isset($this->_currentUser)){
//			var_dump($this->_currentUser);die;
			$this->_helper->redirector->gotoUrl('home/index');		
		}
		//2011-05-24 redirect to home if consumer login already
		$fc = Zend_Controller_Front::getInstance();
		$form = new LoginForm(array(
			'action' => $fc->getBaseUrl().'/login/login',
			'method' => 'post',
			));
		
		$this->view->form = $form;
		$this->view->title = 'Wildfire';
		$this->view->messages = $this->_flashMessenger->getMessages();
		
		$this->view->url = $this->getRequest()->getParam('url');
		$this->_helper->layout->disableLayout();
		$facebook = new Facebook(array(
		  'appId'  => FB_AKEY,
		  'secret' => FB_SKEY
		));
		$this->view->facebook_login_url = $facebook->getLoginUrl(array('scope' => 'email','redirect_uri' => FB_CALLBACK_URL));
		
		$lang = $this->_request->getParam('lang');
		if (isset($lang)){
			$langNamespace = new Zend_Session_Namespace('Lang');
			$langNamespace->lang =$lang;
			$this->_helper->redirector->gotoUrl('index/index');
		}
	}
	
	function loginfailedAction()
	{
		$fc = Zend_Controller_Front::getInstance();
		$form = new LoginForm(array(
			'action' => $fc->getBaseUrl().'/login/login',
			'method' => 'post',
			));
	
		$this->view->form = $form;
		$this->view->title = 'Wildfire';
		$this->_helper->layout->disableLayout();
		$facebook = new Facebook(array(
		  'appId'  => FB_AKEY,
		  'secret' => FB_SKEY,
		  'authorizationRedirectUrl' => FB_CALLBACK_URL,
		));
		$this->view->facebook_login_url = $facebook->getLoginUrl(array('scope' => 'email'));
		/*$this->view->messages = $this->_flashMessenger->getMessages();
		
		$this->view->facebook_login_url = $facebook->getLoginUrl(array('scope' => 'email'));

		$this->view->url = $this->getRequest()->getParam('url');
		$this->_helper->layout->disableLayout();
		
		$lang = $this->_request->getParam('lang');

		if (isset($lang)){
			$langNamespace = new Zend_Session_Namespace('Lang');
			$langNamespace->lang =$lang;
			$this->_helper->redirector->gotoUrl('index/index');
		}*/
	}
}