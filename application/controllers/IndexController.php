<?php

class IndexController extends MyController
{
	function indexAction()
	{
		//2011-05-24 redirect to home if consumer login already
		if (isset($this->_currentUser)){
			$this->_helper->redirector->gotoUrl('home/index');		
		}
		//2011-05-24 redirect to home if consumer login already
		$fc = Zend_Controller_Front::getInstance();
		$form = new LoginForm(array(
			'action' => $fc->getBaseUrl().'/login/login',
			'method' => 'post',
			));
		
		$this->view->form = $form;
		$this->view->title = '星星火 - Wildfire';
		$this->view->messages = $this->_flashMessenger->getMessages();
		
		$this->view->url = $this->getRequest()->getParam('url');
		$this->_helper->layout->disableLayout();
		
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
		$this->view->title = '星星火 - Wildfire';
		$this->view->messages = $this->_flashMessenger->getMessages();
		
		$this->view->url = $this->getRequest()->getParam('url');
		$this->_helper->layout->disableLayout();
		
		$lang = $this->_request->getParam('lang');
		if (isset($lang)){
			$langNamespace = new Zend_Session_Namespace('Lang');
			$langNamespace->lang =$lang;
			$this->_helper->redirector->gotoUrl('index/index');
		}
	}
}