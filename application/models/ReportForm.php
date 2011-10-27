<?php
class ReportForm extends Zend_Form{
	public function __construct($options = null)
	{
		parent::__construct($options);
		$this->setName('report');
		$image = new Zend_Form_Element_File( 'image' );
		//$image->setDestination(PUBLIC_PATH . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . 'qq' . DIRECTORY_SEPARATOR);
		$fileName = $options[0].'_'.$options[1].'_'.date('Y-m-d_H:i:s').'.png';
		$config = Zend_Registry::get('config');
		$image->addFilter('Rename',array(
                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'report_images' . DIRECTORY_SEPARATOR . $fileName,
				//'target' => PUBLIC_PATH. DIRECTORY_SEPARATOR . 'image/qq' . DIRECTORY_SEPARATOR . $fileName,
                'overwrite' => true));
		$submit = new Zend_Form_Element_Submit ( 'submit' );
		$submit->setLabel("提交");
		
		$this->addElements( array( $image , $submit ) );
	}
}