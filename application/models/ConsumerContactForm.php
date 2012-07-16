<?php
class ConsumerContactForm extends Zend_Form
{
	public function __construct($options = null)
	{
		parent::__construct($options);
		$this->setName('consumer');

		$id = new Zend_Form_Element_Hidden('id');

		$phone = new Zend_Form_Element_Text('phone');
		$phone->setLabel($this->getView()->translate('CONTACT INFORMATION_PHONE'))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(0, 50)),));
		
		$qq = new Zend_Form_Element_Text('qq');
		$qq->setLabel('QQ号码 :');
		
		$telephone = new Zend_Form_Element_Text('telephone');
		$telephone->setLabel('公司电话 :');
		if($options['relative']){
			for($i = 1 ; $i <= $options['relative'] ; $i++){
				${'friend_name_'.$i} = new Zend_Form_Element_Text('friend_name_'.$i);
			    ${'friend_name_'.$i}->setLabel('朋友姓名 :')
		        //->setRequired(true)
			    ->addFilter('StripTags')
		        ->addFilter('StringTrim')
              //  ->addValidator('NotEmpty', true)
                ->addErrorMessage('Value is empty, but a non-empty value is required.');
//				${'friend_name_'.$i}->setAttrib('onchange','relativeTest(this.value)');
				$this->addElement(${'friend_name_'.$i});
				
				${'friend_email_'.$i} = new Zend_Form_Element_Text('friend_email_'.$i);
			    ${'friend_email_'.$i}->setLabel('邮箱 :')
			    //->setRequired(true)
				->addFilter('StripTags')
		        ->addFilter('StringTrim')
               // ->addValidator('NotEmpty', true)
		        ->addValidator('EmailAddress')
		        ->addErrorMessage($this->getView()->translate('Register_email_is_invalid'));
		        		        //				${'friend_email_'.$i}->setAttrib('onchange','relativeTest(this.value)');
				$this->addElement(${'friend_email_'.$i});
				
				${'friend_message_'.$i} = new Zend_Form_Element_Text('friend_message_'.$i);
			    ${'friend_message_'.$i}->setLabel('留言:')
				->addFilter('StripTags')
		        ->addFilter('StringTrim');
//		        ->addValidator('NotEmpty');
//				${'friend_phone_'.$i}->setAttrib('onchange','relativeTest(this.value)');
//				$this->addElement(${'friend_message_'.$i});
				
			}
		}		

		$recipients_name = new Zend_Form_Element_Text('recipients_name');
		$recipients_name->setLabel($this->getView()->translate('CONTACT INFORMATION_RECIPIENTS_NAME'))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(1, 50)),));

		$address1 = new Zend_Form_Element_Textarea('address1');
		$address1->setLabel($this->getView()->translate('CONTACT INFORMATION_ADDRESS1'))
		->setAttribs(array('rows'=>4,'cols'=>35))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(0, 140)),));

		$postalcode = new Zend_Form_Element_Text('postalcode');
		$postalcode->setLabel($this->getView()->translate('CONTACT POSTAL_CODE'))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(0, 30)),));

		$gender = new Zend_Form_Element_Radio('gender');
		$gender->setLabel($this->getView()->translate('Consumer_gender'))
		->addMultiOptions( array(
							'0' => $this->getView()->translate('Consumer_gender_Female'), 
							'1' => $this->getView()->translate('Consumer_gender_Male'),
		))
		->setSeparator('&nbsp;&nbsp;');
		
		$education = new Zend_Form_Element_Select('education');
		$education->setLabel($this->getView()->translate('Consumer_education'))
		->addMultiOptions( array(
		'' => '',
		'High-School' => $this->getView()->translate('Consumer_education_High-School'), 
		'Junior college' => $this->getView()->translate('Consumer_education_Junior_college'),
		'Bachelor' => $this->getView()->translate('Consumer_education_Bachelor'),
		'Master' => $this->getView()->translate('Consumer_education_Master'),
		'Doctorate' => $this->getView()->translate('Consumer_education_Doctorate'),
		'Other' => $this->getView()->translate('Consumer_education_Other'),
		));
		
		$birthdate = new Zend_Form_Element_Text('birthdate');
		$birthdate->setLabel($this->getView()->translate('CONTACT BIRTHDAY'))
		//->setAttrib('disabled', 'disabled')
		//->setAttrib('readOnly', true)
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addErrorMessage($this->getView()->translate('Please_enter_your_birthdate'));
		
		$income = new Zend_Form_Element_Select('income');
		$income->setLabel($this->getView()->translate('Consumer_income_level_per_month'));
		$income->addMultiOption('', '');
		for($i = 0; $i < 20000; $i = $i+2000){
			$income->addMultiOption($i."-".($i+2000), $i."-".($i+2000));
		}
		$income->addMultiOption('>20000', '>20000');
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel($this->getView()->translate('CONTACT_INFORMATION_EDIT_AND_RERURN'))
		->setAttrib('id', 'edit');
	/*	if($options['relative'] >0){
			$submit->setAttrib('disabled','disabled');
		}*/
		$this->addElements(array($id, $recipients_name, $phone, $address1, $postalcode, $submit,$qq,$telephone,$income,$birthdate,$education,$gender));
		
	}
}
?>