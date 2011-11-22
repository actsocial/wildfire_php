<?php
class RegisterForm extends Zend_Form
{
	public function __construct($options = null)
	{
		parent::__construct($options);
		$this->setName('consumer');

		$id = new Zend_Form_Element_Hidden('id');

		$auth_code = new Zend_Form_Element_Text('auth_code');
		$auth_code
		//->setRequired(true)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		//->addValidator('NotEmpty');
		
		$email = new Zend_Form_Element_Text('registerEmail');
		$email
		->setRequired(true)
		->addFilter('StripTags')
		->addFilter('StringTrim')
		//->addValidator('NotEmpty')
		->addValidator('EmailAddress')
		->addErrorMessage($this->getView()->translate('Register_email_is_invalid'));
		
		$login_phone = new Zend_Form_Element_Text('loginPhone');
		$login_phone
		->setRequired(true)
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidator('NotEmpty')
		->addErrorMessage($this->getView()->translate('Register_phone_is_invalid'));

		$name = new Zend_Form_Element_Text('name');
		$name
		->setRequired(true)
		->addFilter('StripTags')
		->addFilter('StringTrim')
		//->addValidator('NotEmpty')
		->addValidators(array(array('StringLength', false, array(1, 50)),))
		->addErrorMessage($this->getView()->translate('Register_name_is_invalid'));
		
		$password = new Zend_Form_Element_Password('registerPassword');
		$password
		->setRequired(true)
		->addValidators(array(array('StringLength', false, array(6, 20)),))
		->addErrorMessage($this->getView()->translate('Register_password_is_invalid'));
		/* repeat */
		$repeat = new Zend_Form_Element_Password('repeat');
		$repeat
		->setRequired(true)
		->addValidators(array(array('StringLength', false, array(6, 20)),))
		->addErrorMessage($this->getView()->translate('Register_password_is_invalid'));

		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel($this->getView()->translate("Register"))
		->setAttrib('id', 'register');

		$this->addElements(array($auth_code, $id, $email, $login_phone, $name, $password, $repeat, $submit));
	}
}
?>