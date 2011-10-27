<?php
class CampaignEmailTemplateForm extends Zend_Form{
	
	public function __construct($options = null)
	{
		parent::__construct($options);
		
		$campaigns = new Zend_Form_Element_Select('campaign');
		
		$subject = new Zend_Form_Element_Text('subject');
		$subject->setAttribs(array('size'=>150))
		->addFilter('StringTrim');
				
	    $message = new Zend_Form_Element_Textarea('message');
		$message->setAttribs(array('rows'=>30,'cols'=>150))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(0, 65535)),));
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel($this->getView()->translate('Save'));

		$this->addElements(array($campaigns,  $subject, $message, $submit));
		
	}
}