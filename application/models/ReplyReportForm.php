<?php
class ReplyReportForm extends Zend_Form
{
	public function __construct($options = null)
	{
		parent::__construct($options);
		
		
		$email = new Zend_Form_Element_Text('email');
		$email->setLabel($this->getView()->translate('To'))
		->addFilter('StripTags')
		->setRequired(true)
		->addFilter('StringTrim');		

		$subject = new Zend_Form_Element_Text('subject');
		$subject->setLabel($this->getView()->translate('INVITATION_MAIL_SUBJECT'))
		->addFilter('StringTrim');

		$message = new Zend_Form_Element_Textarea('message');
		$message->setLabel($this->getView()->translate('INVITATION_MAIL_CONTENT'))
		->setAttribs(array('rows'=>30,'cols'=>100,'onkeydown'=>'cal();'))
		->setRequired(true)
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(0, 3000)),));
		
		$grade = new Zend_Form_Element_Select('grade');
		$grade->setLabel($this->getView()->translate('Admin_Reply_Grade'))
		->addMultiOptions(array('0'=>0, '100'=>100, '200'=>200, '300'=>300, '400'=>400, '500'=>500));
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel($this->getView()->translate('INVITATION_MAIL_SEND'))
		->setAttribs(array('onclick'=>'cal();'));
		$this->addElements(array($email, $subject, $message, $grade, $submit));
	}
}
?>