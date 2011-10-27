<?php
class LoginForm extends Zend_Form
{
	public function init()
	{
		$email = $this->addElement('text', 'email', 
		array('filters' => array('StringTrim', 'StringToLower'),
		'validators' => array(array('StringLength', false, array(3,50)),),
		'required' => true,
		'label' => $this->getView()->translate('Email'),
		'class' => 'inputtext'
		));
		
		$password = $this->addElement('password', 'password', 
		array('filters' => array('StringTrim'),
		'validators' => array(	array('StringLength', false, array(6, 20)),	),
		'required' => true,
		'label' => $this->getView()->translate('Password'),
		'class' => 'inputtext'
		));

		$login = $this->addElement('submit', 'login', 
		array('required' => false,
		'ignore' => true,
		'label' => $this->getView()->translate('Login'),
		));
		
		$url = $this->addElement('hidden', 'url', null);
		// We want to display a 'failed authentication' message if necessary;
		// we'll do that with the form 'description', so we need to add that
		// decorator.
		$this->setDecorators(array('FormElements',
		array('HtmlTag', array('tag' => 'div', 'class' => 'login_form')),
		array('Description', array('placement' => 'prepend')),
		'Form'
		));
	}
}