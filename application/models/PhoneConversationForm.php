<?php
class PhoneConversationForm extends Zend_Form {
	public function __construct($options = null) {
		parent::__construct ( $options );
		$this->setName ( 'phoneForm' );
		
		$phonenum = new Zend_Form_Element_Text('phoneNum');
		$phonenum->setRequired ( true )
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( 'NotEmpty' );
		$phonenum->setAttribs(array('onblur'=>'searchname();'));
		
		$image = new Zend_Form_Element_File( 'image' );
		//$image->setDestination(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'qq' . DIRECTORY_SEPARATOR);
		$fileName = date('Y-m-d_H:i:s').'.png';
		$config = Zend_Registry::get('config');
		$image->addFilter('Rename',array(
                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'conversation_images' . DIRECTORY_SEPARATOR . $fileName,
                'overwrite' => true));
			
		$consumername =new Zend_Form_Element_Text('consumerName');
		$consumername->setRequired ( true )
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( 'NotEmpty' );
			
		$content =new Zend_Form_Element_Textarea('content');
		$content->setRequired ( true )
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( 'NotEmpty' );
			
		$evaluation = new Zend_Form_Element_Textarea('evaluation');
		$evaluation->setRequired(true)
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( 'NotEmpty' );
		
		$duration = new Zend_Form_Element_Text('duration');
		$duration->setRequired(true)
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( 'NotEmpty' );
			
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('发 送');
		
		$this->addElements(array($phonenum,$consumername,$content,$evaluation,$duration, $submit ,$image));
	}
}