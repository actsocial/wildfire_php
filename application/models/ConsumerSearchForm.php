<?php
class ConsumerSearchForm extends Zend_Form {
	public function __construct($options = null) {
		parent::__construct ( $options );
		$this->setName ( 'consumerSearchForm' );
		
		$search = new Zend_Form_Element_Text ( 'search' );
		$search->setRequired ( true )
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( 'NotEmpty' );
			
		$submit = new Zend_Form_Element_Submit ( 'submit' );
		$submit->setLabel("查 询");
		$this->addElements(array($search,$submit));
	}
}