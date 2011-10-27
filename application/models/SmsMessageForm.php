<?php
class SmsMessageForm extends Zend_Form{
	
	public function __construct($options = null)
	{

		$subject = new Zend_Form_Element_Text('subject');
		$subject->setAttribs(array('size'=>50))
		->addFilter('StringTrim');
		
	    $message = new Zend_Form_Element_Textarea('message');
		$message->setAttribs(array('rows'=>3,'cols'=>50))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(0, 140)),));
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel($this->getView()->translate('Save'));

		$this->addElements(array( $subject, $message, $submit));
		
	}
}