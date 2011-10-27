<?php
class Points99Form extends Zend_Form
{
	public function __construct($options = null)
	{
		parent::__construct($options);
		$this->setName('jifengtong');
		
		$site = new Zend_Form_Element_Hidden('site');
		$userName = new Zend_Form_Element_Hidden('userName');
		$password = new Zend_Form_Element_Hidden('password');
		$realName = new Zend_Form_Element_Hidden('realName');
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel($this->getView()->translate('Exchange_Now'))
		->setAttrib('id', 'exchange');


		$this->addElements(array($site, $userName, $password, $realName, $submit));

	}
}
?>