<?php
class TelephonePlanForm extends Zend_Form {
	public function __construct($options = null) {
		$this->setName ( 'document' );
		$this->setAction ( "" );
		$this->setAttrib ( 'enctype', 'multipart/form-data' );
		
		$doc_file = new Zend_Form_Element_File ( 'doc_path' );
		$doc_file->setLabel('请选择电话谈话内容文件(CSV)')->setRequired ( true );
		
		$detail   = new Zend_Form_Element_Textarea('detail');
		$detail->setLabel('描述')->setAttrib('cols',60)->setAttrib('rows',6);
					  
		// creating object for submit button
		 $submit = new Zend_Form_Element_Submit('submit');
		 $submit->setLabel('创建')
				 ->setAttrib('id', 'submitbutton');
		 

		// adding elements to form Object
		 $this->addElements(array($doc_file, $detail,$submit));
	}

}