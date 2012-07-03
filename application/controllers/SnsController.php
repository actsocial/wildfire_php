<?php

class SnsController extends MyController
{
	public function indexAction() 
	{
		//$this->_helper->layout->disableLayout();
		$db = Zend_Registry::get ( 'db' );
		$select = $db->select()
		             ->from('sns_user')
								 ->where('consumer = ?', (int)$this->_currentUser->id);
	  $sns_users = $db->fetchAll($select);
		$this->view->users = $sns_users;
		
		$sns = new Sns();
		$this->view->sns_enabled_source = $sns->get_enable_source();

		$config = Zend_Registry::get('config');
		$this->view->writer_host = $config->writer->host;

	}
	
		
	public function ajaxsave() {
		$this->_helper->layout->disableLayout();
		$sns_param = urldecode($this->_request->getParam('sns'));
		if (isset($sns_param['source']))
			$source = $sns_param['source'];
		$sns = new Sns($source);
		if (isset($sns_param['token']))
			$token = $sns_param['token'];
		if (isset($sns_param['secret']))
			$secret = $sns_param['secret'];
		if (isset($sns_param['expires_in']))
			$expires_in = $sns_param['expires_in'];
		if (isset($sns_param['expires_at']))
			$expires_at = $sns_param['expires_at'];
			
		$sns.gen_access_token($oauth_verifier, $source, $oauth_token, $domain);
		
		$table = new Sns();
		$data = array(
			'access_token' => $token,
			'access_token_secret' => $secret,
			'expires_at' => $expires_at,
			'expires_in' => $expires_in,
			'consumer' => (int) $this->_currentUser->id,
			'platform_type' => $source
		);
		$table->insert($data);

	}
  
	public function public_tweet()
	{
		
	}
	
}




