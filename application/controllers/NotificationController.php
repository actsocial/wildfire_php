<?php
class NotificationController extends MyController{
	
	
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
		if($notification) {
			$this->view->notification = $notification;
		} else {
			$this->_helper->json("False");
		}
	}
	
	function ajaxchangeAction() {
		$this->_helper->layout->disableLayout();
		$nid = $this->_request->getParam('nid');
		$consumer = $this->_currentUser;
		$consumer_id = $consumer->id;
		$currentTime = date("Y-m-d H:i:s");
		$db = Zend_Registry::get('db');
		$updateSql = $db->prepare("update notification set status='READ', react='true', react_time='" . $currentTime . "' where id='" . $nid . "' and consumer_id='" . $consumer_id . "'");
		$updateSql->execute();
		$this->_helper->json("Success");
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

