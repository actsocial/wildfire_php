<?php

class CampaignInvitationForm extends Zend_Form{

	public function __construct($options = null, $campaignId)
	{
		parent::__construct($options);
		$this->setName('campaignInvitation');
		
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('campaign_invitation', 'consumer_id')
		->where('campaign_invitation.campaign_id = ?', $campaignId);
		$consumerIds = $db->fetchAll($select);
		$consumerStr = "(0,";
		foreach ($consumerIds as $consumerId){
			$consumerStr .= $consumerId['consumer_id'].",";
		}
		$consumerStr = substr($consumerStr,0,strlen($consumerStr)-1);
		$consumerStr .= ")";
		
		$selectConsumer = $db->select();
		$selectConsumer->from('consumer', array('id', 'email'))
		->where('id not in '.$consumerStr)
		->where('pest != 1 or pest is null');
		$consumers = $db->fetchAll($selectConsumer);
//		Zend_Debug::dump($consumerStr);

		$optionList = array();
		foreach ($consumers as $consumer){
			$optionList[$consumer['id']]= $consumer['email'];
		}
		
		$consumerList = new Zend_Form_Element_MultiCheckbox('consumerList', array(
			'multiOptions' => $optionList,
			'Label'=>'Consumers'
			)	
		);
		
		
		$fromCampaignId = new Zend_Form_Element_Hidden('fromCampaignId');
		$fromCampaignId->setValue($campaignId);
        
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Submit');
		
		//$this->addElement($consumerList);
		$this->addElements(array($consumerList, $fromCampaignId, $submit));
		

	}
	
}