<?php
include_once 'Indicate2Connect.php';

class HomeController extends MyController
{
	protected $_maxInvitation = 10;
	
	function indexAction()
	{
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("Index_HOME");
		$this->view->activeTab = 'Home';
		
		$consumer = $this->_currentUser;
		$this->view->currentUser = $consumer;
		$langNamespace = new Zend_Session_Namespace('Lang');
		$this->view->lang = $langNamespace->lang;		
		$currentTime = date("Y-m-d H:i:s");
		// save language pref
		$consumerModel = new Consumer();
		$consumer = $consumerModel->fetchRow('id = '.$this->_currentUser->id);
		$consumer->language_pref = $langNamespace->lang;
		$consumer->save();
		$db = Zend_Registry::get('db');
		//get badges of consumer 
		$badgeModel = new ConsumerBadge();
		$badges = $badgeModel->fetchAll("consumer = ".$consumer->id);
		$this->view->badges = $badges;
		// active campaigns
		$select = $db->select();
		$select->from('campaign', '*');
		$select->join('campaign_invitation', 'campaign.id = campaign_invitation.campaign_id');
		$select->join('campaign_participation','campaign_invitation.id = campaign_participation.campaign_invitation_id','accept_date');
		//$select->joinLeft('url_report','url_report.campaign_id = campaign.id and url_report.consumer_id = campaign_invitation.consumer_id ',array('url', 'state as url_state'));
		$select->where('campaign_invitation.consumer_id = ?',$consumer->id);
		$select->where('campaign.expire_date > ?',$currentTime);
		$select->where('campaign_participation.state != "COMPLETED" ');
		
		//$select->where('campaign.type = "campaign"');
		
		//url report 
		$url_report = new UrlReport();
		$url_reportData = $url_report->fetchAll('consumer_id='.$consumer->id ,'create_date desc');
		$this->view->urlreport = $url_reportData->toArray();
		//print_r($this->view->urlreport);die;
		
		$select->order('campaign.create_date desc');
		$this->view->activeCampaigns = $db->fetchAll($select);
		
		// active mission
		$select = $db->select();
		$select->from('campaign', 'campaign.*');
		$select->join('campaign_invitation', 'campaign.id = campaign_invitation.campaign_id' .' and campaign_invitation.consumer_id = '.$consumer->id,'campaign_id');
		$select->joinLeft('campaign_participation','campaign_invitation.id = campaign_participation.campaign_invitation_id','accept_date');
		$select->where('campaign.expire_date > ?',$currentTime);
		$select->where('campaign.type = "mission"  and campaign.public = "0"');
		
		$select->order('campaign.create_date desc');
		$this->view->activeMissions = $db->fetchAll($select);
		
		//var_dump($this->view->activeMissions);die;		
		//public missions
		$select = $db->select();
		$select->from('campaign', 'campaign.*');
		$select->where('campaign.expire_date > ?',$currentTime);
		$select->where('campaign.type = "mission"  and campaign.public = "1"');
		$select->order('campaign.create_date desc');
		$this->view->publicMissions = $db->fetchAll($select);
		
		//var_dump($this->view->activeMissions);die;
		
		
		$selectImageReportsForCampaign = $db->select();
		$selectImageReportsForCampaign->from('image_report', array('image_report.id', 'campaign_id', 'thumb_width', 'thumb_height', 'create_date', 'state'));
		$selectImageReportsForCampaign->joinLeft('reward_point_transaction_record', 'image_report.reward_point_transaction_record_id = reward_point_transaction_record.id','point_amount');
		$selectImageReportsForCampaign->where('image_report.consumer_id = ?', $this->_currentUser->id );
		$selectImageReportsForCampaign->order('image_report.create_date desc');
//      $this->view->activeImageReportsForCampaign = $db->fetchAll($selectImageReportsForCampaign);
//      Zend_Debug::Dump($this->view->activeImageReportsForCampaign);
        $campaignId = 0;
        $activeImageReportsForCampaign = array();
        foreach($db->fetchAll($selectImageReportsForCampaign) as $imageReport) {
        	$campaignId = $imageReport['campaign_id'];
        	if(array_key_exists($campaignId, $activeImageReportsForCampaign)) {
        		array_push($activeImageReportsForCampaign[$campaignId], $imageReport);
        	} else {
        		$activeImageReportsForCampaign[$campaignId] = array($imageReport);
        	}
        }
		$this->view->activeImageReportsForCampaign = $activeImageReportsForCampaign;
		   
		$selectAllCampaignReports = $db->select();
		$selectAllCampaignReports->from('report', "*")
		->joinLeft('reward_point_transaction_record', 'reward_point_transaction_record.id = report.reward_point_transaction_record_id', 'point_amount')
		->where('reward_point_transaction_record.point_amount > 0 or report.state="NEW"')
		->where('report.consumer_id = ?', $consumer->id);
		
		
		$selectApprovedCampaignReports = $db->select();
		$selectApprovedCampaignReports->from('report', "id")
		->where('report.consumer_id = ?', $consumer->id)
		->where('report.reward_point_transaction_record_id is not null')
		->where('reward_point_transaction_record.point_amount > 0')
		->join('reward_point_transaction_record', 'reward_point_transaction_record.id = report.reward_point_transaction_record_id', 'point_amount');
				
		if (count($this->view->activeCampaigns)>0){
			// get all reports of the first active campaigns
			// create where clause
			$activeCampaignId = "(";
			foreach ($this->view->activeCampaigns as $campaign){
				$activeCampaignId.=$campaign['campaign_id'].",";
			}
			$activeCampaignId = substr($activeCampaignId,0,strlen($activeCampaignId)-1);
			$activeCampaignId.=")";
			$selectAllCampaignReports->where('report.campaign_id in '.$activeCampaignId);
			$selectApprovedCampaignReports->where('report.campaign_id in '.$activeCampaignId);
		}	
		$this->view->allCampaignReports = $db->fetchAll($selectAllCampaignReports);
		$approvedCampaignReports = $db->fetchAll($selectApprovedCampaignReports);
		$this->view->approvedCampaignReportPoint = array();
		foreach($approvedCampaignReports as $approvedCampaignReport){
			$this->view->approvedCampaignReportPoint[$approvedCampaignReport['id']] = $approvedCampaignReport['point_amount'];
		}
		
		// total points
		$this->view->totalPoints =  $db->fetchOne(
    		"SELECT sum(point_amount) FROM reward_point_transaction_record WHERE transaction_id!=4 and consumer_id = :temp",
			array('temp' =>$consumer->id)
		);
		if (empty($this->view->totalPoints)){ 
			$this->view->totalPoints=0;
		}
		//redeem points 在本日之前的30天内的积分不可用，
		$today = date("Y-m-d" , time());
		$this->view->redeemPoints =  $db->fetchOne(
    		"SELECT sum(point_amount) FROM reward_point_transaction_record WHERE (consumer_id = :temp and date <:temp2) or (consumer_id = :temp and date >=:temp2 and transaction_id=4) ",
			array('temp' =>$consumer->id,'temp2'=>date("Y-m-d",strtotime("$today   -30   day")))
		);
		if (empty($this->view->redeemPoints)){ 
			$this->view->redeemPoints=0;
		}
		
		//get polls
//		$profileSurveyModel = new ProfileSurvey();
//		$this->view->surveys = $profileSurveyModel->fetchAll('state = "ACTIVE"', 'id DESC', null, null);

		$select_profilesurvey = $db->select();
		$select_profilesurvey->from('profile_survey','*');
		$select_profilesurvey->joinLeft('profilesurvey_invitation','profilesurvey_invitation.profile_id = profile_survey.id and profilesurvey_invitation.consumer_id='.$consumer->id,array('id as invitaitonId','consumer_id'));
		$select_profilesurvey->where('profile_survey.state = "ACTIVE"');
		$this->view->surveys = $db->fetchAll($select_profilesurvey);
		
		//var_dump($this->view->surveys);die;
		
		$select2 = $db->select();
		$select2->from('poll_participation', 'poll_id');
		$select2->where('poll_participation.consumer_id = ?',$consumer->id)
		->order('poll_participation.date DESC');
		$this->view->completedPolls = $db->fetchAll($select2);

		// get invitation
		$select3 = $db->select();
		$select3->from('campaign_invitation','*');
		$select3->join('campaign', 'campaign.id = campaign_invitation.campaign_id ', array('name','type','product_name','simple_description','invitation_description','invitation_description2'));
		$select3->where('campaign_invitation.consumer_id = ?', $this->_currentUser->id);
		$select3->where('campaign_invitation.state = ?', 'NEW');
		$select3->order('campaign_invitation.create_date DESC');
		$this->view->recentInvitation = $db->fetchRow($select3);
		$this->view->allInvitations = $db->fetchAll($select3);
		//print_r($this->view->allInvitations);die;
		
		//post-campaign survey popup
		$select4 = $db->select();
		$select4->from('campaign_participation','*');
		$select4->join('campaign_invitation', 'campaign_participation.campaign_invitation_id = campaign_invitation.id');
		$select4->join('campaign', 'campaign.id = campaign_invitation.campaign_id');
		$select4->where('campaign_invitation.consumer_id = ?', $this->_currentUser->id);
		$select4->where('campaign_participation.state = ?', 'FINISHING');
		$select4->order('campaign_invitation.create_date DESC');
		$this->view->postCampaignNotification = $db->fetchRow($select4);
		
		// shut down pop-up
		$appspace = new Zend_Session_Namespace('application');
		if (!isset($appspace->popup)||$appspace->popup){
			$this->view->popup = true;
			$appspace->popup = false;
		}
		// consumer info bar session
		$consumerExtraInfo = new Zend_Session_Namespace('consumerExtraInfo');
		if(!isset($consumerExtraInfo->data)){
			// The username and email are 20 points	
			$count = 20;
			$consumerinfoArray = $consumer->toArray();
			for($i = 0; $i < count($consumerinfoArray); $i++ ){
				$temp = each($consumerinfoArray);
				// Address1, phone, city and recipients_name is 5 points each
				if($temp['key'] == 'address1' || $temp['key'] == 'phone' || $temp['key'] == 'city' || $temp['key'] == 'recipients_name'){
					if($temp['value'] != null && $temp['value'] != ''){
							$count += 5;
					}
				}else{
					continue;
				}
			}
			$consumerextraModel = new ConsumerExtraInfo();
			$consumerextra = $consumerextraModel->fetchRow('consumer_id = '.$this->_currentUser->id);
			if($consumerextra != null){
				$extrainfoArray = $consumerextra->toArray();	
				$inc = round(60/(count($extrainfoArray)-3), 1);	
				for($i = 0; $i < count($extrainfoArray); $i++ ){
					$temp = each($extrainfoArray);
					// Ignore birth year of children
					if($temp['key'] == 'id' || $temp['key'] == 'consumer_id' || $temp['key'] == 'children_birth_year'){
						continue;
					}else{
						if($temp['value'] != null && $temp['value'] != ''){
							$count += $inc;
						}
					}
				}
				$count = floor($count);
				$count =  ($count + ($count%5 == 0 ? 0 : 5-$count%5)) > 100 ? 100 : ($count + ($count%5 == 0 ? 0 : 5-$count%5));
			}
			$consumerExtraInfo->data = $count;
		}
		$this->view->consumerextrainfo = $consumerExtraInfo->data;
		//consumer information 
		$select = $db->select();
		$select->from('consumer', '*');
		$select->joinLeft('rank','consumer.rank = rank.id',array('name as rname'));
		$select->where('consumer.id = ?' , $this->_currentUser->id);
		$this->view->consumer = $db->fetchAll($select);
		//var_dump($this->view->consumer);die;
		
	}
	function editAction()
	{
		$this->view->title = "Account Setting";
	}
	
}