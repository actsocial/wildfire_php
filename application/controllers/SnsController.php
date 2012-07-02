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
		
		$selet = $db->select()
								->from('sns_user');
	  $all_users =$db->fetchAll($select);
		$this->view->all_users = $all_users;
		
		$config = Zend_Registry::get('config');
		//$this->view->writer_host = $config->writer

	}
  
	public function public_tweet()
	{
		
	}
	
}




