<?php
require_once 'LoginAuthAdapter.php';
require_once APPLICATION_PATH . '/models/Log.php';
class LoginController extends MyController {
	public function loginAction() {
		$request = $this->getRequest();
		$config = Zend_Registry :: get('config');
		// Check if we have a POST request
		if (!$request->isPost()) {
			$this->_helper->redirector('index', 'index');
		}

		$lang = $this->getRequest()->getPost('lang');
		if ((isset ($lang)) && ($lang != null)) {
			$langNamespace = new Zend_Session_Namespace('Lang');
			$langNamespace->lang = $lang;
		}

		// Get our form and validate it
		$form = new LoginForm();
		if (!$form->isValid($request->getPost())) {
			// Invalid entries
			$this->_flashMessenger->addMessage('Email or Password is required and its length should between 6 and 20');
			$this->view->form = $form;
			$this->_helper->redirector('loginfailed', 'index');
		}

		// Get our authentication adapter and check credentials
		$adapter = new LoginAuthAdapter($form->getValue('email'), $form->getValue('password'));
		$auth = Zend_Auth :: getInstance();
		$result = $auth->authenticate($adapter);

		if ($result->isValid()) {
			// We're authenticated! Redirect to the home page
			$db = Zend_Registry :: get('db');
			$consumer_id = $db->fetchOne("SELECT id FROM consumer WHERE email = :temp or login_phone = :temp and state='ACTIVE'", array (
				//'temp' => $auth->getIdentity()
	            'temp' => $form->getValue('email')
			));
			$consumerModel = new Consumer();
			$consumer = $consumerModel->find($consumer_id)->current();
			$authNamespace = new Zend_Session_Namespace('Zend_Auth');
			$authNamespace->user = $consumer;
			$authNamespace->role = 'consumer';
			//log
			$logModel = new Log();
			$logId = $logModel->insert(array (
				'consumer_id' => $consumer->id,
				'date' => date("Y-m-d H:i:s"),
				'event' => 'LOGIN'
			));

			$url = $form->getValue('url');

			if (isset ($url) && (!empty ($url))) {
				$this->_redirector = $this->_helper->getHelper('Redirector');
				$this->_redirector->gotoUrl($url);
			} else {
				$this->_helper->redirector('index', 'home');
			}
		} else {
			// Invalid credentials
			$this->_flashMessenger->addMessage('Invalid credentials provided');
			$this->view->form = $form;
			$this->_helper->redirector('loginfailed', 'index');
		}
	}

	public function getPhoneAuthAdapter($phone, $password) {
		$db = Zend_Registry :: get('db');
		$authAdapter = new Zend_Auth_Adapter_DbTable($db);
		$authAdapter->setTableName('consumer');
		$authAdapter->setIdentityColumn('phone');
		$authAdapter->setCredentialColumn('password');

		$authAdapter->setIdentity($phone);
		$authAdapter->setCredential(md5($password));

		return $authAdapter;
	}

	public function getAuthAdapter(array $params) {
		$email = $params["email"];
		$password = $params["password"];

		$db = Zend_Registry :: get('db');
		$authAdapter = new Zend_Auth_Adapter_DbTable($db);
		$authAdapter->setTableName('consumer');
		$authAdapter->setIdentityColumn('email');
		$authAdapter->setCredentialColumn('password');

		$authAdapter->setIdentity($email);
		$authAdapter->setCredential(md5($password));

		return $authAdapter;
	}

	public function logoutAction() {
		Zend_Auth :: getInstance()->clearIdentity();

		$authNamespace = new Zend_Session_Namespace('Zend_Auth');
		$authNamespace->__unset('user');

		$appspace = new Zend_Session_Namespace('application');
		$appspace->__unset('popup');

		$cartspace = new Zend_Session_Namespace('Cart');
		$cartspace->__unset('list');

		$consumerExtraInfo = new Zend_Session_Namespace('consumerExtraInfo');
		$consumerExtraInfo->__unset('data');
		
		$clientCampaignList = new Zend_Session_Namespace('ClientCampaignList');
		$clientCampaignList->__unset('list');
		
		//unset client new message count
		$clientCampaignList = new Zend_Session_Namespace('ClientMessage');
        $clientCampaignList->__unset('count_'.$this->_currentUser->id);
        Zend_Debug::dump($clientCampaignList);

		$config = Zend_Registry :: get('config');
		$url = $config->joomla->home;
		$this->_flashMessenger->addMessage('Logout successfully!');
		$this->_helper->redirector->gotoUrl($url);
	}

	public function statusAction() {
		$fc = Zend_Controller_Front :: getInstance();
		$form = new LoginForm(array (
			'action' => $fc->getBaseUrl() . '/login/login',
			'method' => 'post',
			'target' => '_parent'
		));

		$this->view->form = $form;
		$lang = $this->_request->getParam('lang');
		$this->view->lang = $lang;

		$langNamespace = new Zend_Session_Namespace('Lang');
		$langNamespace->lang = $lang;
		//		Zend_Debug::dump($lang);
		if ($lang == 'en') {
			$form->email->setLabel("Email");
			$form->password->setLabel("Password");
			$form->login->setLabel("Login");
		} else {
			$form->email->setLabel("邮件地址");
			$form->password->setLabel("密码");
			$form->login->setLabel("登录");
		}

		$this->_helper->layout->disableLayout();
	}

}