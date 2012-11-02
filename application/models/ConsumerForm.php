<?php
class ConsumerForm extends Zend_Form
{
	public function __construct($options = null)
	{
		parent::__construct($options);
		$this->setName('consumer');

		$id = new Zend_Form_Element_Hidden('id');

		$email = new Zend_Form_Element_Text('email');
		$email->setLabel($this->getView()->translate('CONTACT INFORMATION_EMAIL'))
		->setRequired(true)
		->setDescription('*')
		->setAttrib('readOnly', true)
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidator('NotEmpty')
		->addValidator('EmailAddress');
		$email->setDecorators(array('ViewHelper',
							array('Description', array('color' => 'red','tag' => 'font')),
							'Errors',
							array('HtmlTag', array('tag' => 'div', 'class'=>'input-area')),
							array('Label'),	
							));

		$phone = new Zend_Form_Element_Text('phone');
		$phone->setLabel($this->getView()->translate('CONTACT INFORMATION_CONTACT_NUMBER'))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(0, 50)),))
		->addErrorMessage($this->getView()->translate('Please_enter_your_phone'));
		$phone->setDecorators(array('ViewHelper',
								'Errors',
								array('HtmlTag', array('tag'=>'div', 'class'=>'input-area')),
								array('Label')
								));
								
	        $login_phone = new Zend_Form_Element_Text('login_phone');
		$login_phone->setLabel($this->getView()->translate('CONTACT INFORMATION_PHONE'))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->setAttrib('readOnly', true)
		->addValidators(array(array('StringLength', false, array(0, 50)),))
		->addErrorMessage($this->getView()->translate('Please_enter_your_phone'));
		$login_phone->setDecorators(array('ViewHelper',
									array('Description', array('color' => 'red','tag' => 'font')),
								'Errors',
								array('HtmlTag', array('tag'=>'div', 'class'=>'input-area')),
								array('Label')
								));
		
		
		$name = new Zend_Form_Element_Text('name');
		$name->setLabel($this->getView()->translate('CONTACT INFORMATION_NAME'))
		->setRequired(true)
		->setDescription('*')
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(1, 50)),))
		->addErrorMessage($this->getView()->translate('Please_enter_your_name'));
		$name->setDecorators(array(
							'ViewHelper',	
							array('Description', array('color' => 'red','tag' => 'font')),
							'Errors',
							array('HtmlTag', array('tag' => 'div', 'class'=>'input-area')),	
							array('Label')
							));
							


		$recipients_name = new Zend_Form_Element_Text('recipients_name');
		$recipients_name->setLabel($this->getView()->translate('CONTACT INFORMATION_RECIPIENTS_NAME'))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(1, 30)),))
		->addErrorMessage($this->getView()->translate('Please_enter_your_recipients_name'));
		$recipients_name->setDecorators(array(
										'ViewHelper',
										'Errors',
										array('HtmlTag', array('tag'=>'div', 'class'=>'input-area')),
										array('Label')
										));

		$city = new Zend_Form_Element_Text('city');
		$city->setLabel($this->getView()->translate('CONTACT INFORMATION_RECIPIENTS_CITY'))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(1, 30)),));
		$city->setDecorators(array(
										'ViewHelper',
										'Errors',
										array('HtmlTag', array('tag'=>'div', 'class'=>'input-area')),
										array('Label')
										));

		$country = new Zend_Form_Element_Text('country');
		$country->setLabel($this->getView()->translate('CONTACT INFORMATION_RECIPIENTS_COUNTRY'))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(1, 30)),));
		// ->addErrorMessage($this->getView()->translate('Please_enter_your_recipients_name'));
		$country->setDecorators(array(
										'ViewHelper',
										'Errors',
										array('HtmlTag', array('tag'=>'div', 'class'=>'input-area')),
										array('Label')
										));

		$address1 = new Zend_Form_Element_Text('address1');
		$address1->setLabel($this->getView()->translate('CONTACT INFORMATION_ADDRESS1'))
		->setAttribs(array('rows'=>3,'cols'=>80))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(0, 240)),))
		->addErrorMessage($this->getView()->translate('Please_enter_your_address'));
		$address1->setDecorators(array(
									'ViewHelper',
									'Errors',
									array('HtmlTag', array('tag'=>'div', 'class'=>'input-area')),
									array('Label')
									));


		$postalcode = new Zend_Form_Element_Text('postalcode');
		$postalcode->setLabel($this->getView()->translate('CONTACT POSTAL_CODE'))
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidators(array(array('StringLength', false, array(0, 30)),))
		->addErrorMessage($this->getView()->translate('Please_enter_your_postalcode'));
		$postalcode->setDecorators(array(
									'ViewHelper',
									'Errors',
									array('HtmlTag', array('tag'=>'div', 'class'=>'input-area')),
									array('Label')
									));
		
		$birthdate = new Zend_Form_Element_Text('birthdate');
		$birthdate->setLabel($this->getView()->translate('CONTACT BIRTHDAY'))
		//->setAttrib('disabled', 'disabled')
		->setAttrib('readOnly', true)
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addErrorMessage($this->getView()->translate('Please_enter_your_birthdate'))
		->addDecorators(array(
		    array('HtmlTag',array('tag'=>'div','class'=>'info_value input-area','id'=>'birthdate_value')),
		    array('Label')
		));
	
		
		//
		$gender = new Zend_Form_Element_Radio('gender');
		$gender->setLabel($this->getView()->translate('Consumer_gender'))
		->addMultiOptions( array(
							'1' => $this->getView()->translate('Consumer_gender_Male'),
							'0' => $this->getView()->translate('Consumer_gender_Female'), 
							
		))
		->setSeparator('&nbsp;&nbsp;')
		->addDecorators(array(
		    array('HtmlTag',array('tag'=>'div','class'=>'info_value input-area','id'=>'gender_value')),
		    array('Label')
		)); 
		
		//
//		$birth_year = new Zend_Form_Element_Select('birth_year');
//		$birth_year->setLabel($this->getView()->translate('Consumer_birth_year'));
//		$birth_year->addMultiOption('', '');
//		$birth_year->addMultiOption('<1960', $this->getView()->translate('Consumer_birth_year_Before 1960'));
//		for($i = 1960; $i<=1995; $i++){
//			$birth_year->addMultiOption($i, $i);
//		}
//		$birth_year->addMultiOption('>1995', $this->getView()->translate('Consumer_birth_year_After 1995'));
		//
		$profession = new Zend_Form_Element_Select('profession');
		$profession->setLabel($this->getView()->translate('Consumer_profession'))
		->addMultiOptions( array(
		'' => '',
		'Student' => $this->getView()->translate('Consumer_profession_Student'), 
		'Education' => $this->getView()->translate('Consumer_profession_Education'),
		'Freelancers' => $this->getView()->translate('Consumer_profession_Freelancers'),
		'Housewife/Retirement' => $this->getView()->translate('Consumer_profession_Housewife/Retirement'),
		'Manufacturing/Operating' => $this->getView()->translate('Consumer_profession_Manufacturing/Operating'),
		'Construction' => $this->getView()->translate('Consumer_profession_Construction'),
		'Art/Design' => $this->getView()->translate('Consumer_profession_Art/Design'),
		'Advertising/Marketing' => $this->getView()->translate('Consumer_profession_Advertising/Marketing'),
		'Finance/Banking' => $this->getView()->translate('Consumer_profession_Finance/Banking'),
		'IT/Electronics Industry' => $this->getView()->translate('Consumer_profession_IT/Electronics Industry'),
		'Service Industry' => $this->getView()->translate('Consumer_profession_Service Industry'),
		'Financial Accounting' => $this->getView()->translate('Consumer_profession_Financial Accounting'),
		'Servant/Interpreter' => $this->getView()->translate('Consumer_profession_Servant/Interpreter'),
		'HR/Administration' => $this->getView()->translate('Consumer_profession_HR/Administration'),
		'Medical treatment' => $this->getView()->translate('Consumer_profession_Medical treatment'),
		'Consulting/Lawyer' => $this->getView()->translate('Consumer_profession_Consulting/Lawyer'),
		'Marketing' => $this->getView()->translate('Consumer_profession_Marketing'),
		'Purchasing/Distributing' => $this->getView()->translate('Consumer_profession_Purchasing/Distributing'),
		'Biology/Pharmacy' => $this->getView()->translate('Consumer_profession_Biology/Pharmacy'),
		'Supporting' => $this->getView()->translate('Consumer_profession_Supporting'),
		'Other' => $this->getView()->translate('Consumer_profession_Other'),
		))
		->addDecorators(array(
		    array('HtmlTag',array('tag'=>'div','class'=>'info_value input-area','id'=>'profession_value')),
		    array('Label')
		));
		//
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
		))
		->addDecorators(array(
		    array('HtmlTag',array('tag'=>'div','class'=>'info_value input-area','id'=>'education_value')),
		    array('Label')
		));
		//
		$have_children = new Zend_Form_Element_Radio('have_children');
		$have_children->setLabel($this->getView()->translate('Consumer_have_children'))
		->addMultiOptions( array(
		'0' => $this->getView()->translate('No'), 
		'1' => $this->getView()->translate('Yes'),
		))
		->addDecorators(array(
		    array('HtmlTag',array('tag'=>'div','class'=>'info_value input-area','id'=>'have_children_value')),
		    array('Label')
		));
		$have_children->setSeparator('&nbsp;');
		//

		$children_birth_year = new Zend_Form_Element_Text('children_birth_year');
		// $children_birth_year->setLabel($this->getView()->translate('Consumer_children_birth_year'));
		// $children_birth_year->addMultiOption('', '');
		// $children_birth_year->addMultiOption('<1980', $this->getView()->translate('Consumer_children_birth_year_Before 1980'));
		// for($i = 1980; $i<=2010; $i++){
			// $children_birth_year->addMultiOption($i, $i);
		// }
		// $children_birth_year->addMultiOption('>2010', $this->getView()->translate('Consumer_children_birth_year_After 2010'))
		// ->addDecorators(array(
		    // array('HtmlTag',array('tag'=>'div','class'=>'info_value input-area','id'=>'children_birth_year_value')),
		    // array('Label')
		// ));
		$children_birth_year->setLabel($this->getView()->translate('Consumer_children_birth_year'));

		//
		$income = new Zend_Form_Element_Select('income');
		$income->setLabel($this->getView()->translate('Consumer_income_level_per_month'));
		$income->addMultiOption('', '');
		for($i = 0; $i < 20000; $i = $i+2000){
			$income->addMultiOption($i."-".($i+2000), $i."-".($i+2000));
		}
		$income->addMultiOption('>20000', '>20000')
		->addDecorators(array(
		    array('HtmlTag',array('tag'=>'div','class'=>'info_value input-area','id'=>'income_value')),
		    array('Label')
		));
		//
		$online_shopping = new Zend_Form_Element_Radio('online_shopping');
		$online_shopping->setLabel($this->getView()->translate('Consumer_do_your_often_go_shopping_online'))
		->addMultiOptions( array(
		'Once a week or more' => $this->getView()->translate('Consumer_do_your_often_go_shopping_online_Once a week or more'), 
		'Once a month or more' => $this->getView()->translate('Consumer_do_your_often_go_shopping_online_Once a month or more'), 
		'Less then once a month' => $this->getView()->translate('Consumer_do_your_often_go_shopping_online_Less then once a month'),  
		'Never' => $this->getView()->translate('Consumer_do_your_often_go_shopping_online_Never'),
		))
		->setSeparator('')
		->addDecorators(array(
		    array('HtmlTag',array('tag'=>'div','class'=>'info_value input-area','id'=>'onlineShopping_value')),
		    array('Label')
		));
		//
		$use_extra_bonus_for = new Zend_Form_Element_MultiCheckbox('use_extra_bonus_for');
		$use_extra_bonus_for->setLabel($this->getView()->translate('Consumer_What_will_you_use_extra_bouns_for'))
		->addMultiOptions( array(
		'Traveling' => $this->getView()->translate('Consumer_What_will_you_use_extra_bouns_for_Traveling'),
		'House ware' => $this->getView()->translate('Consumer_What_will_you_use_extra_bouns_for_House ware'), 
		'Further education' => $this->getView()->translate('Consumer_What_will_you_use_extra_bouns_for_Further education'), 
		'Clothes and shoes' => $this->getView()->translate('Consumer_What_will_you_use_extra_bouns_for_Clothes and shoes'),
		'Electronic products' => $this->getView()->translate('Consumer_What_will_you_use_extra_bouns_for_Electronic products'),
		'Good food' => $this->getView()->translate('Consumer_What_will_you_use_extra_bouns_for_Good food'),
		'Luxury' => $this->getView()->translate('Consumer_What_will_you_use_extra_bouns_for_Luxury'),
		'Skin care products' => $this->getView()->translate('Consumer_What_will_you_use_extra_bouns_for_Skin care products'),
		'Gym and yoga' => $this->getView()->translate('Consumer_What_will_you_use_extra_bouns_for_Gym and yoga'),
		'Party' => $this->getView()->translate('Consumer_What_will_you_use_extra_bouns_for_High-level party'),
		'Investment and stock' => $this->getView()->translate('Consumer_What_will_you_use_extra_bouns_for_Investment and stock'),
		))
		->setSeparator('');
		$use_extra_bonus_for->addDecorators(array(
		    array('HtmlTag',array('tag'=>'div','class'=>'info_value input-area multi-lines form-multi-options','id'=>'bonus_value')),
		    array('Label')
		));
		//
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel($this->getView()->translate('CONTACT INFORMATION_EDIT'))
		->setAttrib('id', 'edit');
		
		$this->addElements(array($id, $email, $login_phone, $name, $recipients_name, $phone, $city,$country,$address1, $postalcode, $birthdate, 
		$gender, $profession, $education, $have_children, $children_birth_year, $income, $online_shopping, $use_extra_bonus_for, 
		$submit));

	}
}
?>