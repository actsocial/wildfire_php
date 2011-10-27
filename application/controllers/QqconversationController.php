<?php

class QqconversationController extends MyController {
	
	function adminaddAction() {
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		$translate = new Zend_Translate ('array',Array( "Value is required and can't be empty"=>$this->view->translate('validation_null')));
		if($this->_request->getParam('uid')){
			$uid  = $this->_request->getParam('uid');
			$consumerModel = new Consumer();
			$consumer = $consumerModel->fetchAll('id =' . $uid);
			$consumer = $consumer[0];
		}
		$form = new QqConversationForm ();
		$form->setTranslator($translate);
		if($this->_request->getParam('uid')){
			$form->setDefault('qqNum', $consumer->qq);
			$form->setDefault('consumerName', $consumer->name);
		}

		$this->view->form = $form;
		$currentTime = date ( "Y-m-d H:i:s" );
		if ($this->_request
			->isPost ()) {
			$formData = $this->_request
				->getPost ();
			if ($form->isValid ( $formData )) {
				$qqNum = $form->getValue ( 'qqNum' );
				$consumerName = $form->getValue ( 'consumerName' );
				$content = $form->getValue ( 'content' );
				$evaluation = $form->getValue ( 'evaluation' );
				$mediaFrom = $form->getValue ( 'mediafrom' );
				$image     = $form->getValue ( 'image' );
				$time = $currentTime;
				//2011-04-08 ham.bao separate the sessions with admin
				$adminId = $this->_currentAdmin->id;
				//select admin_id consumer_id
				$db = Zend_Registry::get ( 'db' );
				$select = $db->select ();
				$select->from ( 'consumer', 'id' );
				$select->where ( 'name=?', $consumerName );
				$consumerId = $db->fetchOne ( $select );
				//2011-06-03 upload the image 
                

				//view
				$this->view->adminid = $adminId;
				$this->view->consumerid = $consumerId;
				$this->view->consumername = $consumerName;
				$this->view->qqnum = $qqNum;
				$this->view->content = $content;
				$this->view->evaluation = $evaluation;
				$this->view->mediafrom = $mediaFrom;
				$this->view->time = $time;
				//save conversation
				$qqConversationModel = new QqConversation ();
				$qqConversation = $qqConversationModel->createRow ();
				$qqConversation->admin_id = $adminId;
				$qqConversation->consumer_id = $consumerId;
				$qqConversation->consumer_name = $consumerName;
				$qqConversation->consumer_qq = $qqNum;
				$qqConversation->content = $content;
				$qqConversation->evaluation = $evaluation;
				$qqConversation->mediafrom = $mediaFrom;
				$qqConversation->time = $currentTime;
				$qqConversation->image = $image;
				$qqConversation->save ();
			} else {
				$form->populate ( $formData );
			}
		}
	}
}