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
  
	public function public_tweet()
	{
		
	}
	
}




