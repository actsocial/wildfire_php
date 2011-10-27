<?php
class PasswordForm extends Zend_Form
{
	public function __construct($options = null)
	{
		parent::__construct($options);
		$this->setName('consumer');

//		$id = new Zend_Form_Element_Hidden('id');
		
		$email = new Zend_Form_Element_Text('email');
		$email->setLabel($this->getView()->translate('CONTACT INFORMATION_EMAIL'))
		->setRequired(true)
		->setAttrib('readOnly', true)
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidator('NotEmpty')
		->addValidator('EmailAddress');
		
		$oldpassword = new Zend_Form_Element_Password('oldpassword');
		$oldpassword->setLabel($this->getView()->translate('CONTACT INFORMATION_OLDPASSWORD'))
		->setRequired(true)
		->addValidators(array(array('StringLength', false, array(6, 20)),))
		->addErrorMessage($this->getView()->translate('Can_not_be_empty'));
		
		$newpassword = new Zend_Form_Element_Password('newpassword');
		$newpassword->setLabel($this->getView()->translate('CONTACT INFORMATION_NEWPASSWORD'))
		->setRequired(true)
		->addValidators(array(array('StringLength', false, array(6, 20)),))
		->addErrorMessage($this->getView()->translate('Can_not_be_empty'));
		/* repeat */
		$repeat = new Zend_Form_Element_Password('repeat');
		$repeat->setLabel($this->getView()->translate('CONTACT INFORMATION_CONFIRMNEWPASSWORD'))
		->setRequired(true)
		->addValidators(array(array('StringLength', false, array(6, 20)),))
		->addErrorMessage($this->getView()->translate('Can_not_be_empty'));
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel($this->getView()->translate('CONTACT INFORMATION_CHANGEPASSWORD'))
		->setAttrib('id', 'changepassword');
		
		$this->addElements(array($email, $oldpassword, $newpassword, $repeat, $submit));
	}
}
?>