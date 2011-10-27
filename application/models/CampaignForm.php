<?php
class CampaignForm extends Zend_Form
{
	public function __construct($options = null)
	{
		parent::__construct($options);
		$this->setName('campaign');
		
		
		$name = new Zend_Form_Element_Text('name');
		$name->setLabel($this->getView()->translate('Campaigns_Name'))
		->setRequired(true)
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidator('NotEmpty');
		
		
		
		$company = new Zend_Form_Element_Text('company');
		$company->setLabel($this->getView()->translate('Company_Name'))
		->setRequired(false)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
//		$campaign_type=new Zend_Form_Element_Text('campaigntype');
//		$campaign_type->setLabel($this->getView()->translate('Campaign_type'))
//		->setRequired(false)
//		->addFilter('StripTags')
//		->addFilter('StringTrim');

		$campaign_type=new Zend_Form_Element_Text('i2_survey_id');
		$campaign_type->setLabel($this->getView()->translate('Campaign_i2_survey_id'))
		->setRequired(false)
		->addFilter('StripTags')
		->addFilter('StringTrim');

		$campaign_expire_date=new Zend_Form_Element_Text('expire_date');
		$campaign_expire_date->setLabel($this->getView()->translate('Campaigns_expire_date'))
		->setRequired(false)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
 		$campaign_pre_campaign_survey=new Zend_Form_Element_Text('pre_campaign_survey');
		$campaign_pre_campaign_survey->setLabel($this->getView()->translate('pre_campaign_survey'))
		->setRequired(false)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
 		$campaign_post_campaign_survey=new Zend_Form_Element_Text('post_campaign_survey');
		$campaign_post_campaign_survey->setLabel($this->getView()->translate('post_campaign_survey'))
		->setRequired(false)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
		$campaign_invitation_total=new Zend_Form_Element_Text('total');
		$campaign_invitation_total
		->setLabel("最高参与人数")
		->setRequired(false)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
		$campaign_participation=new Zend_Form_Element_Text("participation");
		$campaign_participation
		->setLabel("实际参与人数")
		->setRequired(false)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
 		$campaign_product_name=new Zend_Form_Element_Textarea('product_name');
		$campaign_product_name
		->setAttrib("rows", 3)
		->setLabel('产品名称')
		->setRequired(false)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
 		$campaign_simple_description=new Zend_Form_Element_Textarea('simple_description');
		$campaign_simple_description
		->setLabel('活动简单描述')
		->setAttrib("rows",3)
		->setRequired(false)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
		$campaign_invitation_description=new Zend_Form_Element_Textarea('invitation_description');
		$campaign_invitation_description
		->setLabel('邀请描述')
		->setAttrib("rows", 3)
		->setRequired(false)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
		//该属性暂时没有使用
		$invitation_image_name=new Zend_Form_Element_Textarea('invitation_image_name');
		$invitation_image_name
		->setLabel('邀请图片')
		->setAttrib("rows", 3)
		->setAttrib("disabled","disabled")
		->setRequired(false)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
		$campaign_simple_description2=new Zend_Form_Element_Textarea('invitation_description2');
		$campaign_simple_description2
		->setLabel('活动描述')
		->setAttrib("rows", 3)
		->setRequired(false)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
		$Pre_campaign_intro=new Zend_Form_Element_Textarea('pre_campaign_intro');
		$Pre_campaign_intro
		->setLabel("Pre_campaign_intro")
		->setRequired(false)
		->setAttrib("rows", 3)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
		$Thanks_for_post_campaign_survey_title=new Zend_Form_Element_Textarea('thanks_for_post_campaign_survey');
		$Thanks_for_post_campaign_survey_title
		->setRequired(false)
		->setLabel("Thanks_for_post_campaign_survey")
		->setAttrib("rows", 3)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
		$Thanks_for_post_campaign_survey_content=new Zend_Form_Element_Textarea('thanks_for_post_campaign_survey_content');
		$Thanks_for_post_campaign_survey_content
		->setRequired(false)
		->setLabel("Thanks_for_post_campaign_survey_content")
		->setAttrib("rows", 3)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
		$Campaign_post_survey_notice=new Zend_Form_Element_Textarea('post_survey_notice');
		$Campaign_post_survey_notice
		->setRequired(false)
		->setLabel("Campaign_post_survey_notice")
		->setAttrib("rows", 3)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
		$Pre_campaign_info=new Zend_Form_Element_Textarea('pre_campaign_info');
		$Pre_campaign_info
		->setRequired(false)
		->setLabel("Pre_Campaign_info")
		->setAttrib("rows", 3)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
		$Pre_campaign_thankyou=new Zend_Form_Element_Textarea('pre_campaign_thankyou');
		$Pre_campaign_thankyou
		->setRequired(false)
		->setLabel("Pre_campaign_thankyou")
		->setAttrib("rows", 3)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
		$Pre_campaign_friends=new Zend_Form_Element_Textarea('pre_campaign_friends');
		$Pre_campaign_friends
		->setRequired(false)
		->setLabel("Pre_campaign_friends")
		->setAttrib("rows", 3)
		->addFilter('StripTags')
		->addFilter('StringTrim');
		
		$upload_photo1=new Zend_Form_Element_File('photo_one');
		$upload_photo1
		->setDestination(PUBLIC_PATH.'/images/campaign/')
		->setName('photo_one')
		->setLabel("图片1");
		
//		$fileName = "campaign_".$id."_01.jpg";
//		$config = Zend_Registry::get('config');
//		$upload_photo1->addFilter('Rename',array(
//                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'conversation_images' . DIRECTORY_SEPARATOR . $fileName,
//                'overwrite' => true));
		
		$upload_photo2=new Zend_Form_Element_File("photo_two");
		$upload_photo2
		->setDestination(PUBLIC_PATH.'/images/campaign/')
		->setName("photo_two")
		->setLabel("图片2");
		
		$upload_photo3=new Zend_Form_Element_File("photo_three");
		$upload_photo3
		->setDestination(PUBLIC_PATH.'/images/campaign/')
		->setName("photo_three")
		->setLabel("图片3");
		
		$upload_photo4=new Zend_Form_Element_File("photo_four");
		$upload_photo4
		->setDestination(PUBLIC_PATH.'/images/campaign/')
		->setName("photo_four")
		->setLabel("图片4");
		
		$upload_photo5=new Zend_Form_Element_File("photo_five");
		$upload_photo5
		->setDestination(PUBLIC_PATH.'/images/campaign/')
		->setName("photo_five")
		->setLabel("图片5");
		
		$upload_photo6=new Zend_Form_Element_File("photo_six");
		$upload_photo6
		->setDestination(PUBLIC_PATH.'/images/campaign/')
		->setName("photo_six")
		->setLabel("图片6");
		
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setAttrib('id', 'submitbutton');
		$id = new Zend_Form_Element_Hidden('id');
		
		$this->addElements(array($name, $company,$campaign_type,$campaign_expire_date,$campaign_pre_campaign_survey,$campaign_post_campaign_survey,$campaign_invitation_total,$campaign_participation, 
		$campaign_product_name,$campaign_simple_description,$campaign_invitation_description,$invitation_image_name,$campaign_simple_description2,
		$Pre_campaign_intro,$Thanks_for_post_campaign_survey_title,$Thanks_for_post_campaign_survey_content,$Campaign_post_survey_notice,$Pre_campaign_info,
		$Pre_campaign_thankyou,$Pre_campaign_friends,$upload_photo1, $upload_photo2,$upload_photo3,$upload_photo4,$upload_photo5,$upload_photo6,
		$submit,$id));
	}
}
?>