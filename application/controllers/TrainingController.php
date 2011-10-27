<?php

class TrainingController extends MyController{
	function indexAction(){
		
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("TRAINING");
		
		$this->view->activeTab = 'Training';
		$this->_helper->redirector('howitworks', 'training');
	}
	
	function howitworksAction(){
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("TRAINING");
		$this->view->activeTab = 'Training';
		
		$this->view->id = (int)$this->_request->getParam('id', 0);
		
	}
	
	function introtowildfireAction(){
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("TRAINING");
		$this->view->activeTab = 'Training';
		
		$this->view->id = (int)$this->_request->getParam('id', 0);
		
	}

	function faqAction(){
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("FAQ");
		$this->view->activeTab = 'Training';
		$this->view->id = (int)$this->_request->getParam('id', 0);
	}
	
	function samplereportAction() {
		$langNamespace = new Zend_Session_Namespace('Lang');
		$this->view->lang = $langNamespace->lang;
		
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("SAMPLE_REPORT");
		$this->view->activeTab = 'Training';
		$this->view->id = (int)$this->_request->getParam('id', 0);
	}
}
