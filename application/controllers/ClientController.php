<?php

class ClientController extends MyController
{
	function indexAction()
	{
		$fc = Zend_Controller_Front::getInstance();
		$form = new LoginForm(array(
			'action' => $fc->getBaseUrl().'/client/login',
			'method' => 'post',
			));
		$form->getElement('url')->setValue($this->getRequest()->getParam('url'));
		$this->view->form = $form;
		$this->_helper->layout->setLayout($this->getClientTemplate($this->getRequest()->getParam('theme')));
		$this->view->campaign_id = $this->getRequest()->getParam('id');
		$messageArray = $this->_flashMessenger->getMessages();
		if(isset($messageArray) && isset($messageArray[0])){
			$this->view->message = $messageArray[0];
		}
	}
	
	public function loginAction()
	{
		$request = $this->getRequest();

		// Check if we have a POST request
		if (!$request->isPost()) {
			$this->_helper->redirector('index', 'client');
		}

		// Get our form and validate it
		$form = new LoginForm();
		if (!$form->isValid($request->getPost())) {
			// Invalid entries
			$this->view->form = $form;
			$this->_flashMessenger->addMessage("Email or password is incorrect.");
			$this->_helper->redirector('index', 'client');// re-render the login form
		}
		
		// Get our authentication adapter and check credentials
		$adapter = $this->getAuthAdapter($form->getValues());
		$auth = Zend_Auth::getInstance();
		$result = $auth->authenticate($adapter);
		if (!$result->isValid()) {
			// Invalid credentials
			$form->setDescription('Invalid credentials provided');
			$this->view->form = $form;
			$this->_flashMessenger->addMessage("Email or password is incorrect.");
			$this->_helper->redirector('index', 'client');
			// re-render the login form
		}

		$db = Zend_Registry::get('db');
		$client_id = $db->fetchOne(
    		"SELECT id FROM client WHERE email = :temp",
			array('temp' => $auth->getIdentity())
		);
		$clientModel = new Client();
		$client = $clientModel->find($client_id)->current();
		
		$authNamespace = new Zend_Session_Namespace('Zend_Auth');
		//2011-04-08 ham.bao separate the sessions with client
		$authNamespace->client =$client;
		// get accessible campaign list 
		$clientCampaginSelect = $db->select();
		$clientCampaginSelect->from('client_campaign', 'campaign_id')
		->join('campaign', 'client_campaign.campaign_id = campaign.id', array('name'))
		->where('client_campaign.client_id = ?', $client_id)
		->order('campaign.id desc');
		$clientCampaign = $db->fetchAll($clientCampaginSelect);
		$campaignlist = array();
		foreach($clientCampaign as $temp){
			$campaignlist[$temp['campaign_id']] = array($temp['campaign_id'], $temp['name']);
		}
		
		$clientCampaignListNamespace = new Zend_Session_Namespace('ClientCampaignList');
		if($clientCampaignListNamespace->list == null){
			$clientCampaignListNamespace->list = $campaignlist;
		}
		// We're authenticated! Redirect to the home page
		$url = $form->getValue('url');

		//get unviewed message count save it to session
        $clientMessageNamespace = new Zend_Session_Namespace('ClientMessage');
        //$db = Zend_Registry::get('db');
        $messageCount = $db->fetchOne("SELECT count(*) FROM client_message cm WHERE cm.to_type='Client' and cm.to=:clientId and state='NEW'", array('clientId' => $client_id ));
	    if($messageCount > 0) {
            $attrName = "count_".$client_id;
            $clientMessageNamespace->$attrName = $messageCount;
        }

		if (isset ($url) && (!empty ($url))) {
		  $this->_redirector = $this->_helper->getHelper('Redirector');
		  $this->_redirector->gotoUrl($url);
		} else {
		  $campaignIdArray = array_keys($campaignlist);
		  $this->_helper->redirector('clientcloudtag','dashboard', null, array('id' => $campaignIdArray[0]));
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
			$authAdapter->setTableName('client');
			$authAdapter->setIdentityColumn('email');
			$authAdapter->setCredentialColumn('password');
				
			$authAdapter->setIdentity($email);
			$authAdapter->setCredential(md5($password));
		}
		return $authAdapter;
	}
	
	function getClientTemplate($client) {
      if(file_exists(APPLICATION_PATH. "/layouts/client_template_". $client .".phtml")) {
        return ("/layouts/client_template_". $client .".phtml");
      } else {
        return "layout_client";
      }
    }

}
