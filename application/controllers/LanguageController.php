<?php

class LanguageController extends MyController
{

	public function indexAction()
	{
		$lang = $this->_request->getParam('lang');
		
		$langNamespace = new Zend_Session_Namespace('Lang');
		$langNamespace->lang =$lang;
//		Zend_Debug::dump($_SERVER["HTTP_REFERER"]);
		if(isset($this->_currentUser->id)){
			$consumerModel = new Consumer();
			$consumer = $consumerModel->fetchRow('id = '.$this->_currentUser->id);
			if($consumer != null){
				$consumer->language_pref = $lang;
				$consumer->save();
			}else{
				$adminModel = new Admin();
				$admin = $adminModel->fetchRow('id = '.$this->_currentUser->id);
				if($admin != null){
					$admin->language_pref = $lang;
					$admin->save();
				}	
			}
		}
		
		$messageArray = $this->_flashMessenger->getMessages();
		if($messageArray != null){
			foreach($messageArray as $message){
				$this->_flashMessenger->addMessage($message);
			}
		}
		$this->_helper->redirector->gotoUrl($_SERVER["HTTP_REFERER"]);
	}
	

}
