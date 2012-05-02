<?php

class MyController extends Zend_Controller_Action
{

	protected $_flashMessenger = null;

	protected $_currentUser;
	//2011-04-08 ham.bao separate the sessions with admin
	protected $_currentAdmin;	
	protected $_currentClient;

	public function init()
	{
		$langNamespace = new Zend_Session_Namespace('Lang');
		$config = Zend_Registry::get('config');
		if ($langNamespace->lang==null){
			//$config = Zend_Registry::get('config');
			$defaultLanguage = strval($config->framework->language->default);
			$langNamespace->lang = $defaultLanguage;
		}
		$this->view->home = $config->app->home;
		$this->view->joomlahome = $config->joomla->home;
		if (Zend_Auth::getInstance()->hasIdentity()){
			$authNamespace = new Zend_Session_Namespace('Zend_Auth');
			$this->_currentUser  = $authNamespace->user;
			//2011-04-08 ham.bao separate the sessions with admin
			$this->_currentAdmin = $authNamespace->admin;
			$this->_currentClient = $authNamespace->client;
			$this->view->currentUser = $this->_currentUser;
			$authNamespace->setExpirationSeconds(12*60*60);
		}
		$this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('adminajax', 'json')->initContext();
		$this->initView();
	}

	public function preDispatch()
	{
		$controller = $this->getRequest()->getControllerName();
		$action = $this->getRequest()->getActionName();
		$authNamespace = new Zend_Session_Namespace('Zend_Auth');
		//print_r($authNamespace->role);die;
		if($this->filter($controller, $action)) {
		  if (!Zend_Auth::getInstance()->hasIdentity()) {
		    $config = Zend_Registry::get('config');
	    	$lang = $this->_request->getParam('lang');
			if ((isset($lang)) && ($lang!=null) ){
			  $langNamespace = new Zend_Session_Namespace('Lang');
			  $langNamespace->lang =$lang;
			}
			if(substr($action,0,5) == 'admin') {
              $this->_redirector = $this->_helper->getHelper('Redirector');
			  $this->_redirector->gotoUrl('/admin?url='.$this->getRequest()->getPathInfo());
			} else if(substr($action,0,6) == 'client') {
				$this->_helper->redirector('login','client');
			} else {			
			  $this->_redirector = $this->_helper->getHelper('Redirector');
			  $this->_redirector->gotoUrl('/index/index?url='.$this->getRequest()->getPathInfo());
			}
		  } else {
		  	//2011-04-08 ham.bao separate the sessions with admin
		  	if(substr($action,0,5)== 'admin' && $this->_currentAdmin->getTableClass() != 'Admin') {
			  //$this->_helper->redirector('login','admin');
			  $this->_redirector = $this->_helper->getHelper('Redirector');
			  $this->_redirector->gotoUrl('/admin?url='.$this->getRequest()->getPathInfo());
			} else if(substr($action,0,6)== 'client') {
			  //$this->_helper->redirector('login','client');
			  //2011-04-08 ham.bao separate the sessions with client
			  //if ($this->_currentUser->getTableClass() != 'Client'){
			  if ($this->_currentClient->getTableClass() != 'Client'){
	            $this->_redirector = $this->_helper->getHelper('Redirector');
			  	$this->_redirector->gotoUrl('/client?url='.$this->getRequest()->getPathInfo());
			  }else{
			  	//check client new message count
			  	if(Zend_Session::namespaceIsset("ClientMessage")) {
			  		$namespace = new Zend_Session_Namespace('ClientMessage');
			  		$attrName = "count_".$this->_currentUser->id;
			  		if($namespace->$attrName > 0) {
			  			$this->view->client_message_count = "(".$namespace->$attrName.")";
			  		}
			  	  }
			    }
			  }
			}
	    }	
	}
	
	private function filter($controller, $action) {
	  $exceptions = array(
	  				   'useremail'   => null, 
                       'login'       => null,
                       'index'       =>null, 
                       'site'        =>null, 
                       'training'    =>null, 
                       'language'    =>null, 
                       'register'    =>null,
                       'forgetpassword'        =>null,
                       'mission'               =>array('index'=>null, 'detail'=>null),
		               'gift'                  =>array('list'=>null, 'description'=>null),
		               'campaignpreinvitation' => array('show'=>null, 'thankyou'=>null), 
		               'admin'  => array('index'=>null, 'login'=>null), 
		               'client' => array('index'=>null, 'login'=>null),
	                   'sms'    => array('sendcoupon'=>null),
	                   'spam'   => array('index'=>null,'spam'=>null),
	                   'gift'   => array('list'=>null),
	                   'report' => array('saveuploaddata' => null),	  
		               );
	  return !(array_key_exists($controller, $exceptions) && (!isset($exceptions[$controller]) || array_key_exists($action, $exceptions[$controller])));
	}
}