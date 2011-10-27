<?php

class CampaignParticipatorSetForm extends Zend_Form{

	public function __construct($options = null, $campaignId)
	{
		parent::__construct($options);
//		$this->setName('campaignInvitation');
		
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('campaign_participation', 'id')
		->join('campaign_invitation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id', null)
		->join('consumer','consumer.id = campaign_invitation.consumer_id', array('recipients_name', 'address1'))
		->where('campaign_invitation.campaign_id = ?',$campaignId)
		->where("campaign_participation.state = 'NEW'")
		->where('consumer.pest is null');
		$consumers = $db->fetchAll($select);
//		Zend_Debug::dump($consumers);

		$optionList = array();
		foreach ($consumers as $consumer){
			$optionList[$consumer['id']]= $consumer['recipients_name'].'    '.$consumer['address1'];
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