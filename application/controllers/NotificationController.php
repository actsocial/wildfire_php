<?php
class NotificationController extends MyController{
	
	public $WOM_REPORT_REPLY_TEMPLET = array(
	  "type"=>"wom_report_reply",
	  "text"=>"您#campaign活动的口碑报告已经审核通过，您获得了#point积分",
	  "redirectionURL"=>"campaign/description/id/#campaignId"
	);
	
	function ajaxpopAction(){
		$this->_helper->layout->disableLayout();
		
		$consumer = $this->_currentUser;
		
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('notification','*')
		->where('status = "NEW"')
		->where('consumer_id = ?', $consumer->id);
		$notification = $db->fetchRow($select);
		//Zend_Debug::dump($notification);die;
		$this->view->notification = $notification;
	}
	
	function deleteAction(){
		
	}
	function addAction(){
	}
	
	function showAction(){
		$select = $db->select();
		$this->view->notificates="";
	}
	
	function reactAction(){
		
	}
	
	function modifyAction(){
	
	}
}

