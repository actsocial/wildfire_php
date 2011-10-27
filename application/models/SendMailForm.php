<?php
class SendMailForm extends Zend_Form
{
	public function __construct($options = null)
	{
		parent::__construct($options);
		
		$resendemail = new Zend_Form_Element_Text('resendemail');
		$resendemail->setLabel($this->getView()->translate('To'))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidator('EmailAddress');	
		$max_amount=isset($options['max_amount'])?$options['max_amount']:50;
		for($i=1; $i<=$max_amount; $i++){
			$varName = 'email'.$i;
			${$varName} = new Zend_Form_Element_Text('email'.$i);
			${$varName}->addFilter('StripTags')
			->addFilter('StringTrim')
			->addValidator('EmailAddress');
			if($i==$max_amount){
				${$varName}->setAttrib("style","width:75%");
			}
		}
			
		$subject = new Zend_Form_Element_Text('subject');
		$subject->setAttribs(array("disabled"=>'disabled'))
		->addFilter('StringTrim');
		$subject->setAttrib("style", "width:75%");
		$message = new Zend_Form_Element_Textarea('message');
		$message->setAttribs(array('rows'=>5,'cols'=>100))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(0, 500)),));
		$message->setAttrib("style", "width:75%");
		$sentMailAmount = new Zend_Form_Element_Hidden('sentMailAmount');
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel($this->getView()->translate('INVITATION_MAIL_SEND'));

		$this->addElements(array($resendemail, $sentMailAmount, $subject, $message, $submit));
		for($i=1; $i<=$max_amount; $i++){
			$varName = 'email'.$i;
			$this->addElement(${$varName});
		}
	}
}
?>