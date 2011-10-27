<?php
class PhoneController extends MyController {
	function adminaddAction() {
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		$translate = new Zend_Translate ('array',Array( "Value is required and can't be empty"=>$this->view->translate('validation_null')));
		$form = new PhoneConversationForm ();
		$form->setTranslator($translate);
		if($this->_request->getParam('id') ){
			$consumer = new Consumer();
			$consumerData = $consumer->fetchRow( 'id='.$this->_request->getParam('id') );
			$form->setDefault('phoneNum',$consumerData->phone);
			$form->setDefault('consumerName',$consumerData->name);
		}

		$this->view->form = $form;
		$currentTime = date ( "Y-m-d H:i:s" );

		if ($this->_request
			->isPost ()) {
			$formData = $this->_request
				->getPost ();
			if ($form->isValid ( $formData )) {
				$phoneNum = $form->getValue('phoneNum');
				$consumerName =$form->getValue('consumerName');
				$content = $form->getValue('content');
				$evaluation =$form->getValue('evaluation');
				$duration = $form->getValue('duration');
				$image     = $form->getValue ( 'image' );
				$time = $currentTime;
				//2011-04-08 ham.bao separate the sessions with admin
				$adminId = $this->_currentAdmin->id;
				
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
				$phoneConversation->image = $image;
				$phoneConversation->save ();
			}
		}
	
	}
	function adminajaxsearchAction()
	{
		$this->_helper->layout->disableLayout ();
		if ($this->_request->isPost ()) {
				$formData = $this->_request
				->getPost ();
				$consumerNum =$formData['consumerphone'];
				$consumerModel =new Consumer();
				$consumers = $consumerModel->fetchAll ( 'login_phone like "%'.$consumerNum.'%" or phone like "%'.$consumerNum.'%"');
				$consumer =$consumers[0];
				$this->view->consumer =$consumer;
				
			}
	}
}