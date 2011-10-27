<?php
class CampaignInvitationMailForm extends Zend_Form
{
	public function __construct($options = null)
	{
		parent::__construct($options);
		
		$emailCategoryOptionList = array('Invite non-sparks to join campaign' => $this->getView()->translate('Admin_Non-Sparks_Invitation'),
										 'Invite sparks to join campaign' => $this->getView()->translate('Admin_Sparks_Invitation'),
										 'Send mail to sparks' => $this->getView()->translate('Admin_Send_Mail_To_Sparks'));
		$emailCategory = new Zend_Form_Element_Select('emailCategory');
        $emailCategory->setMultiOptions($emailCategoryOptionList);
        
		$emailList = new Zend_Form_Element_Textarea('emailList');
		$emailList->setAttribs(array('rows'=>5,'cols'=>150,'onChange'=>'datetable()'))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(0, 65535)),));

		$subject = new Zend_Form_Element_Text('subject');
		$subject->setAttribs(array('size'=>150))
		->addFilter('StringTrim');
		
		$message = new Zend_Form_Element_Textarea('message');
		$message->setAttribs(array('rows'=>30,'cols'=>150))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(0, 65535)),));

		$optionList = array();
		$campaign = new Campaign();
		$campaigns = $campaign->fetchAll(null, "id desc", null, null);
		foreach ($campaigns as $campaign){
			$optionList[$campaign->id]= $campaign->name;
		}
		$optionList['0']= $this->getView()->translate('ADMIN_NOT_AUTO_INVITATION');
		
		$campaignId = new Zend_Form_Element_Select('campaignId');
        $campaignId->setMultiOptions($optionList);
		
        $code_source = new Zend_Form_Element_Text('code_source');
		$code_source->addFilter('StringTrim');
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel($this->getView()->translate('INVITATION_MAIL_SEND'));

		$this->addElements(array($emailCategory, $emailList,  $subject, $message, $campaignId, $code_source, $submit));
	}
}
?>