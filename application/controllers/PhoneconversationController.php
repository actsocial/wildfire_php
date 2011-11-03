<?php
class PhoneconversationController extends MyController {
	function adminphoneAction() {
		$this->_helper->layout
			->setLayout ( "layout_admin" );
			$languageFile = dirname(dirname(dirname(__FILE__))).'/library/simpleChinese.php';
		$translate = new Zend_Translate('array',$languageFile, 'zh_CN');
		$form = new PhoneConversationForm ();
		$form->setTranslator($translate);
		$this->view->form = $form;
		$currentTime = date ( "Y-m-d H:i:s" );
		if ($this->_request
			->isPost ()) {
			$formData = $this->_request
				->getPost ();
			if ($form->isValid ( $formData )) {
				//$phoneNum = $formData ['phoneNum'];
				$phoneNum = $form->getValue('phoneNum');
				//$consumerName = $formData ['consumerName'];
				$consumerName =$form->getValue('consumerName');
				//$content = $formData ['content'];
				$content = $form->getValue('content');
				//$evaluation = $formData ['evaluation'];
				$evaluation =$form->getValue('evaluation');
				//$duration = $formData ['duration'];
				$duration = $form->getValue('duration');
				$time = $currentTime;
				$adminId = $this->_currentUser->id;
				
				$db = Zend_Registry::get ( 'db' );
				$select = $db->select ();
				$select->from ( 'consumer', 'id' );
				$select->where ( 'name=?', $consumerName );
				$consumerId = $db->fetchOne ( $select );
				
				$this->view->adminid = $adminId;
				$this->view->consumerid = $consumerId;
				$this->view->consumername = $consumerName;
				$this->view->phonenum = $phoneNum;
				$this->view->content = $content;
				$this->view->evaluation = $evaluation;
				$this->view->duration = $duration;
				$this->view->time = $time;
				
				$phoneConversationModel = new PhoneConversation ();
				$phoneConversation = $phoneConversationModel->createRow ();
				$phoneConversation->admin_id = $adminId;
				$phoneConversation->consumer_id = $consumerId;
				$phoneConversation->consumer_name = $consumerName;
				$phoneConversation->consumer_phone = $phoneNum;
				$phoneConversation->content = $content;
				$phoneConversation->evaluation = $evaluation;
				$phoneConversation->duration = $duration;
				$phoneConversation->time = $currentTime;
				$phoneConversation->save ();
			}
		}
	
	}
}