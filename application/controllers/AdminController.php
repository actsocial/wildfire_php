<?php
/*
 * Admin Controller is for follwing features:
 *  - Admin users login/logout
 */
class AdminController extends MyController
{
	function indexAction()
	{
	        //2011-05-24 redirect to home if consumer login already
		if (isset($this->_currentAdmin)){
			$this->_helper->redirector->gotoUrl('campaign/adminindex');		
		}
		//2011-05-24 redirect to home if consumer login already
		
		$fc = Zend_Controller_Front::getInstance();
		$form = new LoginForm(array(
			'action' => $fc->getBaseUrl().'/admin/login',
			'method' => 'post',
			));

		$form->getElement('url')->setValue($this->getRequest()->getParam('url'));

		$this->view->form = $form;
		$this->_helper->layout->setLayout("layout_admin");
	}
	
	public function loginAction()
	{
		$request = $this->getRequest();

		// Check if we have a POST request
		if (!$request->isPost()) {
			$this->_helper->redirector('index', 'admin');
		}

		// Get our form and validate it
		$form = new LoginForm();
		if (!$form->isValid($request->getPost())) {
			// Invalid entries
			$this->view->form = $form;
			$this->_helper->redirector('index', 'admin');// re-render the login form
		}

		// Get our authentication adapter and check credentials
		$adapter = $this->getAuthAdapter($form->getValues());
		$auth = Zend_Auth::getInstance();
		$result = $auth->authenticate($adapter);
		if (!$result->isValid()) {

			// Invalid credentials
			$form->setDescription('Invalid credentials provided');
			$this->view->form = $form;
			$this->_helper->redirector('index', 'admin');
			// re-render the login form
		}

		$db = Zend_Registry::get('db');
		$admin_id = $db->fetchOne(
    		        "SELECT id FROM admin WHERE email = :temp",
			        array('temp' => $auth->getIdentity())
			);
		
		$adminModel = new Admin();
		$admin = $adminModel->find($admin_id)->current();
		
		$authNamespace = new Zend_Session_Namespace('Zend_Auth');
		//2011-04-08 ham.bao separate the sessions with admin
		//$authNamespace->user = $admin;
		$authNamespace->admin = $admin;
		//2011-04-08 ham.bao separate the sessions with admin
		$authNamespace->role = 'administrator';
		
		// We're authenticated! Redirect to the home page
		$url = $form->getValue('url');

		if (isset ($url) && (!empty ($url))) {
		  $this->_redirector = $this->_helper->getHelper('Redirector');
		  $this->_redirector->gotoUrl($url);
		} else {
		  $this->_helper->redirector('adminindex','campaign');
		}

	}

	public function getAuthAdapter(array $params)
	{
		$email = $params["email"];
		$password = $params["password"];
		if (empty($email)) {
			$this->view->message = 'Please provide a username.';
		} else {
			$db = Zend_Registry::get('db');
			$authAdapter = new Zend_Auth_Adapter_DbTable($db);
			$authAdapter->setTableName('admin');
			$authAdapter->setIdentityColumn('email');
			$authAdapter->setCredentialColumn('password');				
			$authAdapter->setIdentity($email);
			$authAdapter->setCredential(md5($password));
		}
		return $authAdapter;
	}	
}
