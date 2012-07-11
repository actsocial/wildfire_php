<?php
/*
 * Campaign Controller is for follwing features:
 *  - manage campaigns
 *  - pre/post campaign workflow
 *  - show description of campaign page
 */

include_once 'Campaign.php';
include_once 'Indicate2Connect.php';

class CampaignController extends MyController
{
	function adminindexAction()
	{
		$this->view->messages = $this->_flashMessenger->getMessages();
		$this->view->title = "All Campaigns";
		$this->view->activeTab = "List Campaigns";
		$campaign = new Campaign();
		$order = "id desc";
		$this->view->campaigns = $campaign->fetchAll(null, $order, null, null);
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$this->view->email = $auth->getIdentity();
		}
		$this->_helper->layout->setLayout("layout_admin");
	}

	function adminsendpostcampaignsurveyindexAction(){
		$this->view->title = "Send mail";
		$this->view->campaign_id = (int)$this->_request->getParam('id');
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('consumer', array('email','id'))
		->where('consumer.pest != 1 or consumer.pest is null')
		->join('campaign_invitation', 'consumer.id = campaign_invitation.consumer_id', null)
		->join('campaign_participation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id', null)
		->where("campaign_invitation.campaign_id = ?", $this->view->campaign_id)
//		->where("campaign_participation.state != 'NEW'")
		->where("campaign_participation.state != 'FINISHING'")
		->where("campaign_participation.state != 'COMPLETED'");
		$this->view->participates = $db->fetchAll($select);
		$this->view->participate_amount = count($this->view->participates);
		//
		$selectHaveSent = $db->select();
		$selectHaveSent->from('campaign_participation', null)
		->join('campaign_invitation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id', null)
		->join('consumer', 'consumer.id = campaign_invitation.consumer_id', array('email', 'id'))
		->where('campaign_invitation.campaign_id = ?',$this->view->campaign_id)
		->where("campaign_participation.state = 'FINISHING' or campaign_participation.state = 'COMPLETED'");
		$this->view->haveSentEmails = $db->fetchAll($selectHaveSent); 
		$this->view->haveSentEmailArray = array();
		if($this->view->haveSentEmails != null){
			foreach($this->view->haveSentEmails as $haveSentEmail){
				$this->view->haveSentEmailArray[$haveSentEmail['email']] = '1';
			}
		}
		//		Zend_Debug::dump($this->view->haveSentEmails);
		$this->_helper->layout->setLayout("layout_admin");
	}

	function adminajaxAction(){
		$this->view->title = "Send mail";
		$this->campaign_id = (int)$this->_request->getParam('id');
		// set finishing state
		$db = Zend_Registry::get('db');
		$stmt = $db->prepare("update campaign_invitation,campaign_participation,consumer set campaign_participation.state = 'FINISHING'
		where consumer.id = campaign_invitation.consumer_id 
		and campaign_invitation.id = campaign_participation.campaign_invitation_id 
		and consumer.pest is null 
		and campaign_invitation.campaign_id = ".$this->campaign_id);
		$stmt->execute();
		// show the set
		$select = $db->select();
		$select	->from('consumer', array('email'))
				->where('consumer.pest is null')
				->join('campaign_invitation', 'consumer.id = campaign_invitation.consumer_id', null)
				->join('campaign_participation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id', 'id')
				->where("campaign_invitation.campaign_id = ?", $this->campaign_id)
				->where("campaign_participation.state = 'FINISHING'");
		$participate_list = $db->fetchAll($select);

		$this->view->sentlist = '';
		$this->view->sentcount = 0;
		foreach ($participate_list as $participate){
			$this->view->sentlist .= $participate['email'].",";
			$this->view->sentcount++;
		}
		$this->_helper->json($this->view->sentlist);
		$this->_helper->layout->disableLayout();
	}

	function adminshowAction(){
		$this->view->title = "Campaign Show";
		$this->view->campaign_id = (int)$this->_request->getParam('id');
		$curPage = 1;
		$rowsPerPage = 50;
		if($this->_request->getParam('page'))
		{
			$curPage = $this->_request->getParam('page');
		}

		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('campaign_participation', '*')
		->join('campaign_invitation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id', null)
		->join('consumer','consumer.id = campaign_invitation.consumer_id', array('email','recipients_name'))
		->where('campaign_invitation.campaign_id = ?',$this->view->campaign_id);
		//		->order('campaign_participation.accept_date desc');

		$consumer_list = $db->fetchAll($select);
        //paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($consumer_list));
		$paginator->setCurrentPageNumber($curPage)
		->setItemCountPerPage($rowsPerPage);
		$this->view->paginator = $paginator;

		Zend_View_Helper_PaginationControl::setDefaultViewPartial('pagination/pagelist.phtml');
		$this->view->NoInitValue = ($curPage-1)*$rowsPerPage+1;
		//		Zend_Debug::dump($this->view->paginator);
		$this->_helper->layout->setLayout("layout_admin");

	}
	function adminsetsparkkitsAction(){
		$this->_helper->layout->setLayout("layout_admin");
		$request = $this->getRequest();

		$fc = Zend_Controller_Front::getInstance();
		$campaignId = $this->_request->getParam('id');
		$this->view->cpForm = $cpForm = new CampaignParticipatorSetForm(array(
		   'action' => $fc->getBaseUrl().'/campaign/adminsetsparkkits/id/'.$campaignId,
		   'method' => 'post',
		), $campaignId);
		// Check if we have a POST request
		if ($request->isPost()) {
			$formData = $request->getPost();
			$participationIds = $formData['consumerList'];
			$campaignId = $formData['fromCampaignId'];
			$currentTime = date("Y-m-d H:i:s");
			$idStr = "(";
			foreach ($participationIds as $participationId){
				$idStr.=$participationId.",";
			}
			$idStr = substr($idStr,0,strlen($idStr)-1);
			$idStr.=")";
			//			Zend_Debug::dump($idStr);
				
			$table = new CampaignParticipation();
			$db = $table->getAdapter();
			$set = array('state'=>'KIT SENT');
			$where = $db->quoteInto('id in '.$idStr);
			$rows_affected = $table->update($set, $where);
				
			$this->_helper->redirector('adminsetsparkkits', 'campaign', null, array('id' => $campaignId));
		}


	}
	function adminupdateparticipationAction(){
		$participation_id = (int)$this->_request->getParam('participant_id');
		$campaign_id = (int)$this->_request->getParam('id');
		$participation_state = $this->_request->getParam('state');
		$table = new CampaignParticipation();
		$row = $table->fetchRow('id = '.$participation_id);
		$row->state = $participation_state;
		$row->save();

	}
	
	function adminaddAction()
	{
		$this->view->title = "Add New Campaign";
		$form = new CampaignForm();
		$campaign_db=new Campaign();
		$form->submit->setLabel($this->view->translate('Add'));
		$this->view->form = $form;
		$currentTime = date("Y-m-d H:i:s");
		if ($this->_request->isPost()) {
			$formData = $this->_request->getPost();
			if ($form->isValid($formData)) {
				$campaign = new Campaign();
				$row = $campaign->createRow();
				$row->name = $form->getValue('name');
				$row->company = $form->getValue('company');
				$row->i2_survey_id=$form->getValue("i2_survey_id");
				$row->expire_date=$form->getValue("expire_date");
				$row->pre_campaign_survey=$form->getValue("pre_campaign_survey");
				$row->post_campaign_survey=$form->getValue("post_campaign_survey");
				$row->i2_survey_id_en=$form->getValue("i2_survey_id");
				$row->pre_campaign_survey_en=$form->getValue("pre_campaign_survey");
				$row->post_campaign_survey_en=$form->getValue("post_campaign_survey");
				$row->product_name=$form->getValue('product_name');
				$row->product_name_en=$form->getValue('product_name');
				$row->simple_description=$form->getValue('simple_description');
				$row->simple_description_en=$form->getValue('simple_description');
				$row->invitation_description=$form->getValue("invitation_description");
				$row->invitation_description_en=$form->getValue("invitation_description");
				$row->invitation_image_name=$form->getValue('invitation_image_name');
				$row->invitation_description2=$form->getValue('invitation_description2');
				$row->invitation_description2_en=$form->getValue('invitation_description2');
				$row->pre_campaign_intro=$form->getValue('pre_campaign_intro');
				$row->pre_campaign_intro_en=$form->getValue('pre_campaign_intro');
				$row->thanks_for_post_campaign_survey=$form->getValue("thanks_for_post_campaign_survey");
				$row->thanks_for_post_campaign_survey_en=$form->getValue("thanks_for_post_campaign_survey");
				$row->thanks_for_post_campaign_survey_content=$form->getValue("thanks_for_post_campaign_survey_content");
				$row->thanks_for_post_campaign_survey_content_en=$form->getValue("thanks_for_post_campaign_survey_content");
				$row->post_survey_notice=$form->getValue("post_survey_notice");
				$row->post_survey_notice_en=$form->getValue("post_survey_notice");
				$row->pre_campaign_info=$form->getValue("pre_campaign_info");
				$row->pre_campaign_info_en=$form->getValue("pre_campaign_info");
				$row->pre_campaign_thankyou=$form->getValue('pre_campaign_thankyou');
				$row->pre_campaign_thankyou_en=$form->getValue('pre_campaign_thankyou');
				$row->pre_campaign_friends=$form->getValue('pre_campaign_friends');
				$row->pre_campaign_friends_en=$form->getValue('pre_campaign_friends');
				$row->create_date = $currentTime;
				$row->save();
				$id=$row->id;
				$fileName_1 = "campaign_".$id."_01.jpg";
				$fileName_2 = "campaign_".$id."_02.jpg";
				$fileName_3 = "campaign_".$id."_03.jpg";
				$fileName_4 = "campaign_".$id."_04.jpg";
				$fileName_5 = "campaign_".$id."_side.jpg";
				$fileName_6 = "campaign_invitation_tab_en_".$id.".jpg";
				$config = Zend_Registry::get('config');
				$form->photo_one->addFilter('Rename',array(
//	                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'campaign' . DIRECTORY_SEPARATOR . $fileName_1,
					'target'=>PUBLIC_PATH.'/images/campaign/'.$fileName_1,
	                'overwrite' => true));
				$form->photo_two->addFilter('Rename',array(
//	                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'campaign' . DIRECTORY_SEPARATOR . $fileName_2,
					'target'=>PUBLIC_PATH.'/images/campaign/'.$fileName_2,
	                'overwrite' => true));
				$form->photo_three->addFilter('Rename',array(
//	                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'campaign' . DIRECTORY_SEPARATOR . $fileName_3,
					'target'=>PUBLIC_PATH.'/images/campaign/'.$fileName_3,
	                'overwrite' => true));
				$form->photo_four->addFilter('Rename',array(
//	                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'campaign' . DIRECTORY_SEPARATOR . $fileName_4,
					'target'=>PUBLIC_PATH.'/images/campaign/'.$fileName_4,
	                'overwrite' => true));
				$form->photo_five->addFilter('Rename',array(
//	                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'campaign' . DIRECTORY_SEPARATOR . $fileName_5,
					'target'=>PUBLIC_PATH.'/images/campaign/'.$fileName_5,
	                'overwrite' => true));
				$form->photo_six->addFilter('Rename',array(
//	                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'campaign' . DIRECTORY_SEPARATOR . $fileName_6,
					'target'=>PUBLIC_PATH.'/images/campaign/'.$fileName_6,
	                'overwrite' => true));
				$form->getValue("photo_one");
				$form->getValue("photo_two");
				$form->getValue("photo_three");
				$form->getValue("photo_four");
				$form->getValue("photo_five");
				$form->getValue("photo_six");
				
				$this->_redirect('campaign/adminindex');
			} else {
				$form->populate($formData);
			}
		}
		$this->_helper->layout->setLayout("layout_admin");
	}

	function admineditAction()
	{
		$this->view->title = "Edit Campaign";

		$form = new CampaignForm();
		$form->submit->setLabel($this->view->translate('Save'));
		$this->view->form = $form;

		if ($this->_request->isPost()) {
			$formData = $this->_request->getPost();
//			$id = (int)$this->_request->getPost('id');
			if ($form->isValid($formData)) {
				$campaign = new Campaign();
				$id = (int)$form->getValue('id');
				$row = $campaign->fetchRow('id='.$id);
				$row->id=$id;
				$row->name = $form->getValue('name');
				$row->company = $form->getValue('company');
				$row->i2_survey_id=$form->getValue("i2_survey_id");
				$row->expire_date=$form->getValue("expire_date");
				$row->pre_campaign_survey=$form->getValue("pre_campaign_survey");
				$row->post_campaign_survey=$form->getValue("post_campaign_survey");
				$row->i2_survey_id_en=$form->getValue("i2_survey_id");
				$row->pre_campaign_survey_en=$form->getValue("pre_campaign_survey");
				$row->post_campaign_survey_en=$form->getValue("post_campaign_survey");
				$row->product_name=$form->getValue('product_name');
				$row->product_name_en=$form->getValue('product_name');
				$row->simple_description=$form->getValue('simple_description');
				$row->simple_description_en=$form->getValue('simple_description');
				$row->invitation_description=$form->getValue("invitation_description");
				$row->invitation_description_en=$form->getValue("invitation_description");
				$row->invitation_image_name="campaign_invitation_bk_en_2.png";
				$row->invitation_image_name_en="campaign_invitation_bk_en_2.png";
				$row->invitation_description2=$form->getValue('invitation_description2');
				$row->invitation_description2_en=$form->getValue('invitation_description2');
				$row->pre_campaign_intro=$form->getValue('pre_campaign_intro');
				$row->pre_campaign_intro_en=$form->getValue('pre_campaign_intro');
				$row->thanks_for_post_campaign_survey=$form->getValue("thanks_for_post_campaign_survey");
				$row->thanks_for_post_campaign_survey_en=$form->getValue("thanks_for_post_campaign_survey");
				$row->thanks_for_post_campaign_survey_content=$form->getValue("thanks_for_post_campaign_survey_content");
				$row->thanks_for_post_campaign_survey_content_en=$form->getValue("thanks_for_post_campaign_survey_content");
				$row->post_survey_notice=$form->getValue("post_survey_notice");
				$row->post_survey_notice_en=$form->getValue("post_survey_notice");
				$row->pre_campaign_info=$form->getValue("pre_campaign_info");
				$row->pre_campaign_info_en=$form->getValue("pre_campaign_info");
				$row->pre_campaign_thankyou=$form->getValue('pre_campaign_thankyou');
				$row->pre_campaign_thankyou_en=$form->getValue('pre_campaign_thankyou');
				$row->pre_campaign_friends=$form->getValue('pre_campaign_friends');
				$row->pre_campaign_friends_en=$form->getValue('pre_campaign_friends');
				
				$row->save();
				
				
				$fileName_1 = "campaign_".$id."_01.jpg";
				$fileName_2 = "campaign_".$id."_02.jpg";
				$fileName_3 = "campaign_".$id."_03.jpg";
				$fileName_4 = "campaign_".$id."_04.jpg";
				$fileName_5 = "campaign_".$id."_side.jpg";
				$fileName_6 = "campaign_invitation_tab_en_".$id.".jpg";
				$config = Zend_Registry::get('config');
				$form->photo_one->addFilter('Rename',array(
//	                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'campaign' . DIRECTORY_SEPARATOR . $fileName_1,
					'target'=>PUBLIC_PATH.'/images/campaign/'.$fileName_1,
	                'overwrite' => true));
				$form->photo_two->addFilter('Rename',array(
//	                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'campaign' . DIRECTORY_SEPARATOR . $fileName_2,
					'target'=>PUBLIC_PATH.'/images/campaign/'.$fileName_2,
	                'overwrite' => true));
				$form->photo_three->addFilter('Rename',array(
//	                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'campaign' . DIRECTORY_SEPARATOR . $fileName_3,
					'target'=>PUBLIC_PATH.'/images/campaign/'.$fileName_3,
	                'overwrite' => true));
				$form->photo_four->addFilter('Rename',array(
//	                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'campaign' . DIRECTORY_SEPARATOR . $fileName_4,
					'target'=>PUBLIC_PATH.'/images/campaign/'.$fileName_4,
	                'overwrite' => true));
				$form->photo_five->addFilter('Rename',array(
//	                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'campaign' . DIRECTORY_SEPARATOR . $fileName_5,
					'target'=>PUBLIC_PATH.'/images/campaign/'.$fileName_5,
	                'overwrite' => true));
				$form->photo_six->addFilter('Rename',array(
//	                'target' => $config->framework->upload_dir . DIRECTORY_SEPARATOR . 'campaign' . DIRECTORY_SEPARATOR . $fileName_6,
					'target'=>PUBLIC_PATH.'/images/campaign/'.$fileName_6,
	                'overwrite' => true));
				$form->getValue("photo_one");
				$form->getValue("photo_two");
				$form->getValue("photo_three");
				$form->getValue("photo_four");
				$form->getValue("photo_five");
				$form->getValue("photo_six");
//				$db=$campaign->getAdapter();
//				$where = $db->quoteInto('id = ?', $id);
//				$campaign->update(
//					array("name"=>$form->getValue('name'),
//					"company"=>$form->getValue('company'),
//					"i2_survey_id"=>$form->getValue("i2_survey_id"),
//					"pre_campaign_survey"=>$form->getValue("pre_campaign_survey"),
//					"expire_date"=>$form->getValue("expire_date"),
//					"pre_campaign_survey"=>$form->getValue("pre_campaign_survey"),
//					"post_campaign_survey"=>$form->getValue("post_campaign_survey"),
//					"i2_survey_id_en"=>$form->getValue("i2_survey_id"),
//					"pre_campaign_survey_en"=>$form->getValue("pre_campaign_survey"),
//					"post_campaign_survey_en"=>$form->getValue('$form->getValue("post_campaign_survey")')),
//					$where);
				$this->_redirect('campaign/adminindex');
			} else {
				$form->populate($formData);
			}
		}else {
			// campaign id is expected in $params['id']
			$id = (int)$this->_request->getParam('id', 0);
			if ($id > 0) {
				$campaign = new Campaign();
				$cam = $campaign->fetchRow('id='.$id);
				$form->populate($cam->toArray());
//				var_dump($form);die();
				/*$campaigninfomation_xml=new DOMDocument('1.0','utf-8');
				$bool=$campaigninfomation_xml->load(APPLICATION_PATH.'/language/campaigninformation.xml');
				if($bool){
					$campaigninfomation_xml->formatOutput=true;
					$body=$campaigninfomation_xml->documentElement;
					$tus=$body->getElementsByTagName("tu");
					foreach ($tus as $tu){
						if($tu -> getAttribute('tuid')=="Campaign_name_".$id){
							$tuvs=$tu->getElementsByTagName("tuv");
							foreach ($tuvs as $tuv) {
								if ($tuv->getAttribute('xml:lang')=="zh"){
									$segs=$tuv->getElementsByTagName("seg");
									$this->view->form->Product_name->setValue($segs->item(0)->nodeValue);
								}
							}
						}else if ($tu->getAttribute('tuid')=="Campaign_simple_description_".$id){
							$tuvs=$tu->getElementsByTagName("tuv");
							foreach ($tuvs as $tuv) {
								if ($tuv->getAttribute('xml:lang')=="zh"){
									$segs=$tuv->getElementsByTagName("seg");
									$this->view->form->Simple_description->setValue($segs->item(0)->nodeValue);
								}
							}
						}else if ($tu->getAttribute('tuid')=="Campaign_invitation_description_".$id){
							$tuvs=$tu->getElementsByTagName("tuv");
							foreach ($tuvs as $tuv) {
								if ($tuv->getAttribute('xml:lang')=="zh"){
									$segs=$tuv->getElementsByTagName("seg");
									$this->view->form->Invitation_description->setValue($segs->item(0)->nodeValue);
								}
							}
						}else if ($tu->getAttribute('tuid')=="Campaign_invitation_description2_".$id){
							$tuvs=$tu->getElementsByTagName("tuv");
							foreach ($tuvs as $tuv) {
								if ($tuv->getAttribute('xml:lang')=="zh"){
									$segs=$tuv->getElementsByTagName("seg");
									$this->view->form->Simple_description2->setValue($segs->item(0)->nodeValue);
								}
							}
						}else if ($tu->getAttribute('tuid')=="Pre_campaign_intro_".$id){
							$tuvs=$tu->getElementsByTagName("tuv");
							foreach ($tuvs as $tuv) {
								if ($tuv->getAttribute('xml:lang')=="zh"){
									$segs=$tuv->getElementsByTagName("seg");
									$this->view->form->Pre_campaign_intro->setValue($segs->item(0)->nodeValue);
								}
							}
						}else if ($tu->getAttribute('tuid')=="Thanks_For_Post_Campaign_Survey_".$id){
							$tuvs=$tu->getElementsByTagName("tuv");
							foreach ($tuvs as $tuv) {
								if ($tuv->getAttribute('xml:lang')=="zh"){
									$segs=$tuv->getElementsByTagName("seg");
									$this->view->form->Thanks_for_post_campaign_survey->setValue($segs->item(0)->nodeValue);
								}
							}
						}else if ($tu->getAttribute('tuid')=="Thanks_For_Post_Campaign_Survey_Content_".$id){
							$tuvs=$tu->getElementsByTagName("tuv");
							foreach ($tuvs as $tuv) {
								if ($tuv->getAttribute('xml:lang')=="zh"){
									$segs=$tuv->getElementsByTagName("seg");
									$this->view->form->Thanks_for_post_campaign_survey_content->setValue($segs->item(0)->nodeValue);
								}
							}
						}else if ($tu->getAttribute('tuid')=="Campaign_post_survey_notice_".$id){
							$tuvs=$tu->getElementsByTagName("tuv");
							foreach ($tuvs as $tuv) {
								if ($tuv->getAttribute('xml:lang')=="zh"){
									$segs=$tuv->getElementsByTagName("seg");
									$this->view->form->Campaign_post_survey_notice->setValue($segs->item(0)->nodeValue);
								}
							}
						}else if ($tu->getAttribute('tuid')=="pre_campaign_info_".$id){
							$tuvs=$tu->getElementsByTagName("tuv");
							foreach ($tuvs as $tuv) {
								if ($tuv->getAttribute('xml:lang')=="zh"){
									$segs=$tuv->getElementsByTagName("seg");
									$this->view->form->Pre_campaign_info->setValue($segs->item(0)->nodeValue);
								}
							}
						}else if ($tu->getAttribute('tuid')=="pre_campaign_thankyou_".$id){
							$tuvs=$tu->getElementsByTagName("tuv");
							foreach ($tuvs as $tuv) {
								if ($tuv->getAttribute('xml:lang')=="zh"){
									$segs=$tuv->getElementsByTagName("seg");
									$this->view->form->Pre_campaign_thankyou->setValue($segs->item(0)->nodeValue);
								}
							}
						}else if ($tu->getAttribute('tuid')=="pre_campaign_friends_".$id){
							$tuvs=$tu->getElementsByTagName("tuv");
							foreach ($tuvs as $tuv) {
								if ($tuv->getAttribute('xml:lang')=="zh"){
									$segs=$tuv->getElementsByTagName("seg");
									$this->view->form->Pre_campaign_friends->setValue($segs->item(0)->nodeValue);
								}
							}
						}
					}
				}*/
				$this->view->form=$form;
			}
		}
		$this->_helper->layout->setLayout("layout_admin");

	}
	function admindeleteAction()
	{
		$this->view->title = "Delete Campaign";

		if ($this->_request->isPost()) {
			$id = (int)$this->_request->getPost('id');
			$del = $this->_request->getPost('del');
			if ($del == 'Yes' && $id > 0) {
				$campaignModel = new Campaign();
				$where = 'id = ' . $id;
				$campaignModel->delete($where);
			}
			$this->_redirect('campaign/adminindex');
		} else {
			$id = (int)$this->_request->getParam('id');
			if ($id > 0) {
				$campaignModel = new Campaign();
				$this->view->campaign = $campaignModel->fetchRow('id='.$id);
			}
		}
	}

	function descriptionAction()
	{
		$this->view->activeTab = 'Campaigns';

		$consumer = $this->_currentUser;
		$id = (int)$this->_request->getParam('id');

		//precampaignsurvey的css使用的是layout_survey
		$this->_helper->layout->setLayout("layout_survey");
		
		if ($id > 0) {
			$this->view->id = $id;
				
			$campaignModel = new Campaign();
			$this->view->campaign = $campaignModel->fetchRow('id='.$id);
		} else {
			//TODO not found
			return;
		}
		//whether participate in the campaign 2011-05-19 ham.bao
		$campaigninvitationModel = new CampaignInvitation();
		$campaigninvitation = $campaigninvitationModel->fetchRow('campaign_id = '.$id.' and consumer_id'.' ='.$consumer->id);	
		if($campaigninvitation == null){
			$this->_helper->redirector('index','home');
		}
		
		
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->campaign->name;

		//count days left before expire
		$expire_date = $this->view->campaign["expire_date"];
		$expire_date_year = substr($expire_date,0,4);
		$expire_date_month = substr($expire_date,5,2);
		$expire_date_day = substr($expire_date,8,2);
		$expire_date_hour = substr($expire_date,11,2);
		$expire_date_min = substr($expire_date,14,2);
		$expire_date_sec = substr($expire_date,17,2);
		$expire = mktime($expire_date_hour,$expire_date_min,$expire_date_sec,$expire_date_month,$expire_date_day,$expire_date_year);
		$currentTime = mktime();
		$this->view->dayCount = round(($expire - $currentTime)/3600/24);
		if ($this->view->dayCount  <= 0){
			$this->view->dayCount  = 0;
		}

		//count campaign points
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('reward_point_transaction_record', 'sum(reward_point_transaction_record.point_amount) AS point, ');
		$select->where('reward_point_transaction_record.consumer_id=?',$consumer->id);
		$select->where('report.campaign_id =?',$id);
		$select->join('report','reward_point_transaction_record.id = report.reward_point_transaction_record_id');
		$select->group('report.campaign_id');

		$this->view->campaignPoint = $db->fetchOne($select);
		if(empty($this->view->campaignPoint) || $this->view->campaignPoint == ''){
			$this->view->campaignPoint = 0;
		}


		$select2 = $db->select();
		$select2->from('report','count(*)')
		->where('report.consumer_id = ? ',$consumer->id)
		->where('report.campaign_id = ? ',$id);
		$this->view->reportCount = $db->fetchOne($select2);

		$langNamespace = new Zend_Session_Namespace('Lang');
		$this->view->lang = $langNamespace->lang;
		//url report
		//2011 ham.bao multi urlreport
		$urlReportModel = new UrlReport();
		$urlReportData = $urlReportModel->fetchAll('campaign_id = '.$id.' and consumer_id = '.$consumer->id ,'create_date desc');
		$this->view->urlReport = $urlReportData->toArray();
		//print_r($this->view->urlReport);die;
		
	}

	function precampaignAction(){

		$this->view->activeTab = 'Campaign';

		$consumer = $this->_currentUser;		

		$campaignModel = new Campaign();
		$id =  (int)$this->_request->getParam('id', 0);
		
		//2010-12-06 ham.bao add the logic to detect whether the current user has participated 
		$campaignInvitaion 		=  new CampaignInvitation();
		$campaignInvitaionData 	=  $campaignInvitaion->fetchRow("campaign_id=".$id." and consumer_id=".$consumer->id);
		if($campaignInvitaionData->state != 'NEW'){
			$this->view->visiable = false;
		}else{
			$this->view->visiable = true;
		}
		//precampaignsurvey的css使用的是layout_survey
		$this->_helper->layout->setLayout("layout_survey");
		
		$this->view->campaign_id = $id;
		$campaign = $campaignModel->fetchRow("id=".$id);
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$campaign->name;
		$this->view->name = $campaign->name;

		$langNamespace = new Zend_Session_Namespace('Lang');
		$lang = $langNamespace->lang;
		if ($lang=='en'){
			$surveyId =	$campaign->pre_campaign_survey_en;
		}else{
			$surveyId =	$campaign->pre_campaign_survey;
		}

		//check static file
		$testEnv = Zend_Registry::get('testEnv');
		$file = "./surveys/".$surveyId.".phtml";
		// if static file not exist, go to the normal flow
		if ($testEnv != 0 || file_exists($file) == false) {
			// connect to webservice, get the page
			$indicate2Connect = new Indicate2_Connect();
			$accesscode = $indicate2Connect->createParticipation($consumer->email, $surveyId);

			$config = Zend_Registry::get('config');
			$this->view->filloutPage = $config->indicate2->home."/c/".$accesscode."/theme/wildfire";
			if ($testEnv == 0) {
    			//save the page to static file
    			if ($data = @file_get_contents($this->view->filloutPage)) {
    				set_time_limit(10);
    				ignore_user_abort(true);
    				Zend_Debug::dump(file_put_contents($file, $data));
    			} else {
    				Zend_Debug::dump('Get remote page error!');
    			}
			}
		} else {
			$this->view->file = $file;
			$this->view->surveyId = $surveyId;
		}
		$this->view->campaign=$campaign;
		$this->view->includeCrystalCss = true;
	}

	function precampaignfinishedAction(){
		$this->view->activeTab = 'Campaign';
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("You_are_in");

		//precampaignsurvey的css使用的是layout_survey
		$this->_helper->layout->setLayout("layout_survey");
		
		$id = (int)$this->_request->getParam('survey');
		$campaignModel = new Campaign();	
		$this->view->campaign  = $campaignModel->fetchRow("pre_campaign_survey=".$id." or "."pre_campaign_survey_en=".$id);

		$db = Zend_Registry::get('db');
		if($this->view->campaign != null){
			$campaignId = $this->view->campaign->id;
		}
		$consumer = $this->_currentUser;
		//		Zend_Debug::dump($this->view->campaign->id);
		if ($this->view->campaign != null && $campaignId != null && $campaignId > 0) {
				
			// check if precampaign poll is finished
			//			$select1 = $db->select();
			//			$select1->from('campaign', 'pre_campaign_survey');
			//			$select1->where('campaign.id = ?',$campaignId);
			//			$previewCamSurvey = $db->fetchOne($select1);
				
			//			$indicate2Connect = new Indicate2_Connect();
			//			$ids = array($previewCamSurvey);
			//			$wsResult = $indicate2Connect->getAnswerSetCount($consumer->email,$ids);
			//
			//			if ($wsResult==0){
			//				$this->_redirect('campaign/precampaign/survey/'.$previewCamSurvey);
			//			}else{

			$campaignInvitationModel = new CampaignInvitation();
			$campaignInvitation = $campaignInvitationModel->fetchRow("campaign_id=".$campaignId." and consumer_id=".$consumer->id);
			
			$id = $campaignInvitation->id;
			//Zend_Debug::dump($campaignInvitation);
			$campaignInvitation->state = "ACCEPTED";
			$campaignInvitation->save();
             
			//2011-05-19 ham.bao add the badge
//			$consumerBadgeModel = new ConsumerBadge();
//			$notificationModel = new Notification();
//			$consumerBadgeData  = $consumerBadgeModel->fetchRow('badge='.$this->view->campaign->badge .' and consumer='.$consumer->id);
//			if(!count($consumerBadgeData)){
//				$row = $consumerBadgeModel->createRow();
//				$row->consumer = $consumer->id;
//				$row->badge    = $this->view->campaign->badge;
//				$row->create_date = date("Y-m-d H:i:s");
//				$row->save();
//				// add notification
//				$notificationModel->createRecord("CONSUMER_BADGE",$consumer->id);
//
//			}
			//2011-05-19 ham.bao add the badge
			
			$result = $db->fetchOne(
	    					"SELECT COUNT(*) FROM campaign_participation WHERE campaign_invitation_id=:t1", array('t1' => $id)
					  );
			if($result==0){
			    //2011-02-22 ham.bao add the logic to calculate the number of participation
				$campaignModel->update( array ( 'participation' => ( $this->view->campaign->participation+1 )),'id = '.$this->view->campaign->id);
				if(($this->view->campaign->participation + 1) >= $this->view->campaign->total){
					$campaignInvitationModel->update( array ('state' => 'EXPIRED') ,' state = "NEW" and campaign_id ='.$this->view->campaign->id);				
					$signauthcodeModel = new SignupAuthCode();
					$signauthcodeModel->update ( array('auto_invitation' => 0) , 'auto_invitation ='.$this->view->campaign->id);
			    }
				
				//create participation
				$campaignParticipationModel = new CampaignParticipation();
				$currentTime = date("Y-m-d H:i:s");
				$row = $campaignParticipationModel->createRow();
				$row->campaign_invitation_id = $id;
				$row->accept_date = $currentTime;
				$row->state = 'NEW';
				$row->save();

				//send "welcome to campaign" mail
				//set the content of mail
				$emailSubject = $this->view->translate('Welcome_to_Spark_Campaign_Email_subject_campaign_'.$campaignId);
				$emailBody = $this->view->translate('Welcome_to_Spark_Campaign_Email_body_campaign_'.$campaignId);
				$stringChange = array('?USERNAME?'=>$this->_currentUser['name']);
				$emailBody = strtr($emailBody,$stringChange);
				//send...
				$config = Zend_Registry::get('config');
				$smtpSender = new Zend_Mail_Transport_Smtp(
				$config->smtp->welcome->mail->server,
				array(
									'username'=> $config->smtp->welcome->mail->username,
									'password'=> $config->smtp->welcome->mail->password,
									'auth'=> $config->smtp->welcome->mail->auth,
									'ssl' => $config->smtp->welcome->mail->ssl,
			               			'port' => $config->smtp->welcome->mail->port));
				Zend_Mail::setDefaultTransport($smtpSender);
				$mail = new Zend_Mail('utf-8');
				$langNamespace = new Zend_Session_Namespace('Lang');
				if($langNamespace->lang == 'en' || $langNamespace->lang == 'EN'){
					$mail->setSubject($emailSubject);
				}else{
					$mail->setSubject("=?UTF-8?B?".base64_encode($emailSubject)."?=");
				}
				$mail->setBodyText($emailBody);
				$mail->setFrom($config->smtp->welcome->mail->username, $this->view->translate('Wildfire'));
				$mail->addTo($this->_currentUser['email']);
				$mail->send();
			}
		}
		//edit ConsumerContactForm();
		$form = new ConsumerContactForm( array('relative' =>$this->view->campaign->relative));
		$consumer = $this->_currentUser;
		
		$consumerFriend = new ConsumerFriend();
	    $friends  = $consumerFriend->fetchAll('consumer= '.$consumer->id .' and campaign='.$this->view->campaign->id);		
		$this->view->friendsNum = count($friends);
		
		if ($this->_request->getPost ()){
			$formData = $this->_request->getPost ();
			$form->populate($formData);	
			$this->view->city = $formData["city"];
			$this->view->province = $formData["province"];	
			$this->view->encity = $formData["city"];		
			
		}else{
			$form->populate($consumer->toArray());
			// zh city
			if($consumer["city"]!= NULL && $consumer["province"]!= NULL ){
				$this->view->city = $consumer["city"];
				$this->view->province = $consumer["province"];
			}
			// en city
			if($consumer["city"]!= NULL && $consumer["province"]== NULL ){
				$this->view->encity = $consumer["city"];
			}		
						
			if(count($friends)){
				$i = 1;
				foreach ($friends as $friend){
					$name = 'friend_name_'.$i ;
					$email= 'friend_email_'.$i;
					$message = 'friend_message_'.$i;
					$form->$name->setValue($friend->name);
					$form->$email->setValue($friend->email);
					$form->$message->setValue($friend->message);
					$i++;
				}
			}		
		}
		
		//var_dump($form);die;

		$langNamespace = new Zend_Session_Namespace('Lang');
		$this->view->language = $langNamespace->lang;

		$this->view->form = $form;
		$this->view->friendsLimit = $this->view->campaign->relative;
		//Zend_Debug::dump($this->_request->getPost ());
		
		//Zend_Debug::dump($this->view->friendsLimit);
	}

	function postcampaignAction(){

		$this->view->activeTab = 'Campaign';

		$consumer = $this->_currentUser;

		$campaignModel = new Campaign();
		$id =  (int)$this->_request->getParam('id', 0);
		$campaign = $campaignModel->fetchRow("id=".$id);
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$campaign->name;
		$this->view->name = $campaign->name;

		$langNamespace = new Zend_Session_Namespace('Lang');
		$lang = $langNamespace->lang;
		if ($lang=='en'){
			$surveyId =	$campaign->post_campaign_survey_en;
		}else{
			$surveyId =	$campaign->post_campaign_survey;
		}

		//check static file
		$testEnv = Zend_Registry::get('testEnv');
		$file = "./surveys/".$surveyId.".phtml";
		// if static file not exist, go to the normal flow
		if ($testEnv != 0 || file_exists($file) == false) {
			// connect to webservice, get the page
			$indicate2Connect = new Indicate2_Connect();
			$accesscode = $indicate2Connect->createParticipation($consumer->email, $surveyId);

			$config = Zend_Registry::get('config');
			$this->view->filloutPage = $config->indicate2->home."/c/".$accesscode."/theme/wildfire";
			if ($testEnv == 0) {
    			//save the page to static file
    			if ($data = @file_get_contents($this->view->filloutPage)) {
    				//                Zend_Debug::dump($data);
    				set_time_limit(10);
    				ignore_user_abort(true);
    				Zend_Debug::dump(file_put_contents($file, $data));
    			} else {
    				Zend_Debug::dump('Get remote page error!');
    			}
			}
		} else {
			$this->view->file = $file;
			$this->view->surveyId = $surveyId;
		}

		$this->view->includeCrystalCss = true;
	}

	function postcampaignfinishedAction(){
		$this->view->activeTab = 'Campaign';
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("You_are_in");

		$id = (int)$this->_request->getParam('survey');
		$campaignModel = new Campaign();
		$this->view->campaign  = $campaignModel->fetchRow("post_campaign_survey=".$id." or "."post_campaign_survey_en=".$id);

		$db = Zend_Registry::get('db');
		$campaignId = $this->view->campaign->id;
		$this->view->campaign_id = $campaignId;
		$consumer = $this->_currentUser;
		if ($campaignId > 0) {
			//change campaign_participation state
			$db = Zend_Registry::get('db');
			$select2 = $db->select();
			$select2->from('campaign_participation','*')
			->join('campaign_invitation','campaign_invitation.id = campaign_participation.campaign_invitation_id', null)
			->where('campaign_invitation.consumer_id = ?', $consumer->id)
			->where('campaign_invitation.campaign_id = ?', $campaignId)
			->where("campaign_participation.state != 'COMPLETED'");
			$isExist = $db->fetchAll($select2);
			//	Zend_Debug::dump($isExist[0]['campaign_invitation_id']);
			if($isExist != null){
				$campaing_participateModel = new CampaignParticipation();
				$campaign_participation = $campaing_participateModel->fetchRow('campaign_invitation_id = '.$isExist[0]['campaign_invitation_id']);
				$campaign_participation->state = 'COMPLETED';
				$campaign_participation->save();
				//add 200 points for member in reward_point_transaction_record
				$rewardPointTransactionRecordModel = new RewardPointTransactionRecord();
				$rewardPointTransaction = array(
					"consumer_id" => $consumer->id,
					"date" => date("Y-m-d H:i:s"),
					"transaction_id" => "5",
					"point_amount" => "200"
					);
					$id = $rewardPointTransactionRecordModel->insert($rewardPointTransaction);
				//2011-05-13 change the rank of consumer 
				$rankModel = new Rank();
				$rankModel->changeConsumerRank($consumer->id);
			}
				
		}
	}
	
	
	function admindownloaddataAction(){
		$this->_helper->layout->setLayout("layout_admin");
		
		if ( $this->_request->isPost() ) {
			$formData = $this->_request->getPost();
			
			$campaignCriteria = $formData['campaign'];
			$reportCriteria = $formData['report'];
			$statusCriteria =  $formData['status'];
			$db = Zend_Registry::get ( 'db' );
			
			$sql = "SELECT c.* , b.num,ci.*
					FROM consumer c , campaign_invitation ci
					LEFT JOIN 
					(
					SELECT COUNT(report.id) AS num ,report.consumer_id FROM report 
					INNER JOIN campaign_invitation ci ON report.consumer_id = ci.consumer_id WHERE report.campaign_id = $campaignCriteria
					GROUP BY report.consumer_id ORDER BY num DESC
					)b ON b.consumer_id = ci.consumer_id
					WHERE ci.consumer_id = c.id AND ci.campaign_id = $campaignCriteria AND ci.state = '$statusCriteria'";
			
			//die($sql);
			$campaignUsers = $db->fetchAll($sql);
			if (($reportCriteria > 0)&&(count($campaignUsers))){
				$users = array();
				foreach($campaignUsers as $val){
					if($val['num'] >=$reportCriteria){
						$users[] = $val;
					}			
				}
			}else{
				$users = $campaignUsers;
			}
			$campaignUsers = array ();
			
			$file = $campaignCriteria . '_' . date ( 'Y-m-d_H_i_s' ) . "campaignusers.csv";
			
			if (count($users)) {
				$i = 0;
				foreach ( $users as $val ) {
					$campaignUsers [] = array ($i, $val ['name'], $val ['email'] . '/' . $val ['login_phone'], $val ['recipients_name'], $val ['province'], $val ['city'], $val ['address1'], $val ['phone'], $val ['id'], $val ['create_date'] ,$val['num'] , $val['campaign_id']);
					$i ++;
				}
				$header = array ('No.', 'Name', 'Email/Telephone', 'Recipients_name', 'Province', 'City', 'Address', 'Phone', 'UserID', 'Date' ,'Num','Campaign');
				
				$handle = fopen ( dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/public/csv/' . $file, "w" );
				fputcsv ( $handle, $header );
				foreach ( $campaignUsers as $line ) {
					fputcsv ( $handle, $line );
				}
				fclose ( $handle );
				$this->view->file = file_exists ( dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/public/csv/' . $file ) ? dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/public/csv/'.$file : false;
				$this->view->filename = $file;	
			}else{
				$this->view->nousers = true;
			}
		
		}
		
		$campaignModel = new Campaign();
		$this->view->campaigns = $campaignModel->fetchAll();


		
		
	}
	/**
	 * 
	 * add node to campaigninformation.xml
	 * @param $id
	 * @param $name
	 * @param $value
	 */
	function addNodeToCampaigninformation($id,$name,$value){
		$campaigninfomation_xml=new DOMDocument('1.0','utf-8');
				$bool=$campaigninfomation_xml->load(APPLICATION_PATH.'/language/campaigninformation.xml');
				if($bool){
					//获得根节点
					$root=$campaigninfomation_xml->documentElement;
					$body=$root->getElementsByTagName("body");
					//设置可以输出操作
					$campaigninfomation_xml->formatOutput=true;
					//add tu node
					$tu_name=$campaigninfomation_xml->createElement("tu");
							$tuid=$campaigninfomation_xml->createAttribute("tuid");
								$tuid_val=$campaigninfomation_xml->createTextNode($name.$id);
							$tuid->appendChild($tuid_val);
							$tuv_zh=$campaigninfomation_xml->createElement("tuv");
								$tuv_id=$campaigninfomation_xml->createAttribute("xml:lang");
									$tuv_id_val=$campaigninfomation_xml->createTextNode("zh");
								$tuv_id->appendChild($tuv_id_val);
								$seg=$campaigninfomation_xml->createElement("seg");
									$seg_val=$campaigninfomation_xml->createTextNode($value);
								$seg->appendChild($seg_val);
							$tuv_zh->appendChild($tuv_id);
							$tuv_zh->appendChild($seg);
							$tuv_en=$campaigninfomation_xml->createElement("tuv");
								$tuv_id=$campaigninfomation_xml->createAttribute("xml:lang");
									$tuv_id_val=$campaigninfomation_xml->createTextNode("en");
								$tuv_id->appendChild($tuv_id_val);
								$seg=$campaigninfomation_xml->createElement("seg");
									$seg_val=$campaigninfomation_xml->createTextNode($value);
								$seg->appendChild($seg_val);
							$tuv_en->appendChild($tuv_id);
							$tuv_en->appendChild($seg);
					$tu_name->appendChild($tuv_zh);
					$tu_name->appendChild($tuv_en);	
					$tu_name->appendChild($tuid);
					$body->item(0)->appendChild($tu_name);
					$int_n=$campaigninfomation_xml->save(APPLICATION_PATH.'/language/campaigninformation.xml');	
			}
	}

}


