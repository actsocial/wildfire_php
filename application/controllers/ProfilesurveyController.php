<?php
include_once 'Indicate2Connect.php';
class ProfilesurveyController extends MyController
{
	function indexAction()
	{
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("POLLS");
		$this->view->activeTab = 'Polls';
		$consumer = $this->_currentUser;
		
		$langNamespace = new Zend_Session_Namespace('Lang');
		$this->view->lang = $langNamespace->lang;
		
//		$profileSurveyModel = new ProfileSurvey();
//		$this->view->surveys = $profileSurveyModel->fetchAll('state = "ACTIVE"', 'id DESC', null, null);	
		
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from ('profile_survey','*');		
		$select->joinLeft('profilesurvey_invitation','profilesurvey_invitation.profile_id = profile_survey.id and profilesurvey_invitation.consumer_id='.$consumer->id,array('id as invitaitonId','consumer_id'));		
		$select->where('profile_survey.state = "ACTIVE" ');
		$select->order('id DESC');	
		$this->view->surveys = $db->fetchAll($select);
		
		 
		
		$consumer = $this->_currentUser;

		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('poll_participation', 'poll_id');
		$select->where('poll_participation.consumer_id = ?',$consumer->id);
		$select->join('reward_point_transaction_record', 'reward_point_transaction_record.date = poll_participation.date ', 'point_amount');
		$select->where('reward_point_transaction_record.transaction_id =3');
		
		$this->view->completedPolls = $db->fetchAll($select);
//		Zend_Debug::dump($this->view->completedPolls);
	}
	
	function participateAction()
	{
		$this->view->activeTab = 'Polls';
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("Participate_title");
		
		$consumer = $this->_currentUser;
		$surveyId = (int)$this->_request->getParam('id', 0);	
		
		//precampaignsurvey的css使用的是layout_survey
		$this->_helper->layout->setLayout("layout_survey");
		
		//check history to prevent multiple participation
		$db = Zend_Registry::get('db');
		$select1 = $db->select();
		$select1->from("poll_participation","count(*)");
		$select1->where("poll_participation.poll_id = ?", $surveyId);
		$select1->where("poll_participation.consumer_id = ?", $consumer->id);
		$participationCount = $db->fetchOne($select1);
		if ($participationCount > 0){
			$this->_helper->redirector('index','home');
		}
		
		$profileSurveyModel = new ProfileSurvey();
		$profile = $profileSurveyModel->fetchRow("id=".$surveyId);
		
		if($profile->public == 0){
			$profileSurveyInvitation  = new ProfileSurveyInvitation();
			$profileInvitation = $profileSurveyInvitation->fetchRow('consumer_id ='.$consumer->id.' and profile_id ='.$surveyId);
			if(!count($profileInvitation)){
				$this->_helper->redirector('index','home');
			}				
		}
		
		$langNamespace = new Zend_Session_Namespace('Lang');
		$lang = $langNamespace->lang;
		
		if ($lang=='en'){
			$id =	$profile->i2_survey_id_en;
		}else{
			$id =	$profile->i2_survey_id;
		}
        
		//check static file
		$testEnv = Zend_Registry::get('testEnv');
    	$file = "./surveys/".$id.".phtml";
    	// if static file not exist, go to the normal flow
        if ($testEnv != 0 || file_exists($file) == false) { 
            // connect to webservice, get the page
            $indicate2Connect = new Indicate2_Connect();
            $accesscode = $indicate2Connect->createParticipation($consumer->email,$id);
        
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
            $this->view->surveyId = $id;
        }
		
		$this->view->includeCrystalCss = true;
		$this->view->user = $this->_currentUser;
		
//		Zend_Debug::dump($consumer->email);
//		Zend_Debug::dump($result);
	}
	
	function thankyouAction(){
		$this->view->activeTab = 'Polls';
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("Thanks_For_Poll");
		
		$consumer = $this->_currentUser;
		
		$id = (int)$this->_request->getParam('survey', 0);	
		
		$profileSurveyModel = new ProfileSurvey();
		$profileSurvey = $profileSurveyModel->fetchRow("i2_survey_id =".$id." or "."i2_survey_id_en =".$id);
		// $this->view->point = $profileSurvey->points;
		//check history to prevent multiple participation
		$db = Zend_Registry::get('db');
		$select1 = $db->select();
		$select1->from("poll_participation","count(*)");
		$select1->where("poll_participation.poll_id = ?", $profileSurvey->id);
		$select1->where("poll_participation.consumer_id = ?", $consumer->id);
		$participationCount = $db->fetchOne($select1);
		
		if ($participationCount==0){
			// check ws
			$indicate2Connect = new Indicate2_Connect();
			$ids = array($id);
			$wsResult = $indicate2Connect->getAnswerSetCount($consumer->email,$ids);	
			Zend_Debug::dump($wsResult."------------".$profileSurvey->points);die;
			if ($wsResult>0){
				// add poll participation
				$currentTime = date("Y-m-d H:i:s");
				$pollParticipationModel = new PollParticipation();
				$pollParticipation = $pollParticipationModel->createRow();
				$pollParticipation->poll_id = $profileSurvey->id;
				$pollParticipation->consumer_id = $consumer->id;
				$pollParticipation->date = $currentTime;
				$pollParticipation->save();
				
				// add points
	    	$pointRecordModel = new RewardPointTransactionRecord();
  			$point = $pointRecordModel->createRow();
  			$point->consumer_id =  $consumer->id;
  			$point->transaction_id = 3;
  			$point->date = $currentTime;
  			$point->point_amount = $profileSurvey->points;
  			$point->save();
				//2011-05-13 change the rank of consumer 
				$rankModel = new Rank();
				$rankModel->changeConsumerRank($consumer->id);
    			$this->view->point = $point->point_amount;
			}
		}

	}
	
    function admincreategroupAction() {
        
        $request = $this->getRequest();
        //1.get all campaign
        $campaign = new Campaign();
        $order = "expire_date desc";
        $this->view->campaigns = $campaign->fetchAll(null, $order, null, null);
        //2.get all profile_survey
        $profileSurveyModel = new ProfileSurvey();
        $this->view->profilesurveys = $profileSurveyModel->find_by_condition();
        
        //3.execute sql        
        $profileSurveyGroupModel = new ProfileSurveyGroup();
        if ($request->isPost()) {
            $formData = $request->getPost();
            $sql = $formData['sql'];
            if ($sql == null || $sql == '') {
                return;
            } else {
                $sql = 'select consumer.id ' . $sql;
            }
            
            $campaignId = $formData['campaign_id'];
            $profileSurveyId = $formData['profile_survey_id'];
            $comment = $formData['comment'];
            $currentTime = date("Y-m-d H:i:s");
            
            //get all consumer id
            $db = Zend_Registry::get('db');
            $result = $db->query($sql);
            $consumers = $result->fetchAll();
            
            //4.save ProfileSurveyGroup
            $newProfileSurveyGroupId = $profileSurveyGroupModel->createRecord(count($consumers),
                                            $campaignId, $profileSurveyId, $currentTime, $comment);
            
            //5.insert profile survey group consumer
            $profileSurveyGroupConsumerModel = new ProfileSurveyGroupConsumer();
            foreach ($consumers as $row) {
                $profileSurveyGroupConsumerModel->createRecord($newProfileSurveyGroupId, $row['id']);
            }
        }
        
        //4.get all profile survey group
        $this->view->profilesurveygroups = $profileSurveyGroupModel->find_by_condition('id asc');
        
        //Zend_Debug::dump($this->view->profilesurveygroups);
        $this->_helper->layout->setLayout ("layout_admin");

    }
    
    function admintestsqlAction() {
        $result = '';
        if($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            $sql = $formData['sql'];
            if($sql == '' || $sql == null) {
                return;
            } else {
                $sql = 'select count(*) ' . $sql;
            }
            
            //1.get all consumers
            $db = Zend_Registry::get('db');
            $result = $db->fetchOne($sql);
        }
        $this->_helper->json($result);
    }
}