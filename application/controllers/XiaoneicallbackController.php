<?php
include_once 'Indicate2Connect.php';
class XiaoneicallbackController extends Zend_Controller_Action {
	
	protected $_rowsPerPage = 10;
	protected $_curPage = 1;
	
	protected $_debug;
	protected $_api_key = '763bf27664cf4d4088907ea38be7ad61';
	protected $_secret = 'd54d8975cfe1441a841d68177f0c7cb7';
	
	public $_currentUser;
	public $_source_id;
	
	function xiaoneiAccountsMapping($xn_uid) {
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('external_consumer', '*');
		$select->where('source_id = ?', $xn_uid);
		$external_consumer = $db->fetchRow($select);

		if (isset($external_consumer['consumer_id'])) {
			$select_consumer = $db->select();
			$select_consumer->from('consumer', '*');
			$select_consumer->where('id=?', $external_consumer['consumer_id']);
			$consumer = $db->fetchRow($select_consumer);
			return $consumer;
		} else {
			return NULL;
		}
	}

	public function init()
	{
		$config = Zend_Registry::get('config');
		$this->_debug = $config->xiaonei->debug;
		
		if ($this->_debug) {
			$this->_helper->layout->setLayout("layout_xiaonei_debug");
			$this->view->imageUrl = $this->view->baseUrl();
		} else {
			$this->_helper->layout->setLayout("layout_xiaonei");
			$this->view->imageUrl = 'http://home1.xingxinghuo.com/public';
		}
		
		//init xiaonei class lib
		$xn_uid = '000000';
		if ($this->_debug) {
			$xn_uid = '228838245';
		} else {
			require_once('xiaonei.class.php');
			$api_key = $this->_api_key;
			$secret	= $this->_secret;
			$xn = new XNapp($api_key,$secret);
			$xn_uid = $_REQUEST['xn_sig_user'];
		}
		$this->consumer = $this->xiaoneiAccountsMapping($xn_uid);
		$this->_source_id = $xn_uid;
	}
	
	function autoAccountBinding() {
		if (!isset($this->consumer)) {
			$this->render('bindaccount');
			return false;
		} else {
			$this->_currentUser = $this->consumer;
			return true;
		}
	}
	
	function indexAction() {

	}
	
	function createaccountAction() {
		$xn_uid = $this->_source_id;
		$currentTime = date("Y-m-d H:i:s");
		// save new consumer
	    $consumerModel = new Consumer();
		$row = $consumerModel->createRow();
		$row->name = "校内用户";
		$row->email = "xiaonei_".$xn_uid;
		$row->password = md5($xn_uid);
		$row->save();
		
		$db = Zend_Registry::get('db');
		$select_new_consumer = $db->select();
		$select_new_consumer->from('consumer', '*');
		$select_new_consumer->where('email=?', 'xiaonei_'.$xn_uid);
		$new_consumer = $db->fetchRow($select_new_consumer);
		
		$externalConsumerModel = new ExternalConsumer();
		$row = $externalConsumerModel->createRow();
		$row->source_id = $xn_uid;
		$row->source_type = 'xiaonei';
		$row->consumer_id = $new_consumer['id'];
		$row->start_date = $currentTime;
		$row->save();
		
		$this->_currentUser = $new_consumer;
	}
	
	function htmlAction() {
		
	}
	
	function homeAction() {
		if ($this->autoAccountBinding() == false) {
			return;
		}
		$this->view->consumer = $this->_currentUser;
		
		$db = Zend_Registry::get('db');
		
		//Get Points
		$points_amounts = $db->fetchOne("SELECT sum(point_amount) FROM reward_point_transaction_record WHERE transaction_id!=4 and consumer_id=".$this->view->consumer['id']);
		$points_available = $db->fetchOne("SELECT sum(point_amount) FROM reward_point_transaction_record WHERE consumer_id=".$this->view->consumer['id']);
		
		$current_time = date("Y-m-d H:i:s");
		//Get Invitations
		$select_invitations = $db->select();
		$select_invitations->from('campaign','*');
		$select_invitations->join('campaign_invitation', 'campaign.id = campaign_invitation.campaign_id');
		$select_invitations->where('campaign_invitation.consumer_id = ?', $this->view->consumer['id']);
		$select_invitations->where('campaign_invitation.state = ?', 'NEW');
		$select_invitations->order('campaign_invitation.create_date DESC');
		$campaign_invitations = $db->fetchAll($select_invitations);
		
		//Get Campaigns
		$select_campaigns = $db->select();
		$select_campaigns->from('campaign', '*');
		$select_campaigns->where('campaign_invitation.consumer_id = ?',$this->view->consumer['id']);
		$select_campaigns->where('campaign.expire_date > ?',$current_time);
		$select_campaigns->join('campaign_invitation', 'campaign.id = campaign_invitation.campaign_id');
		$select_campaigns->join('campaign_participation','campaign_invitation.id = campaign_participation.campaign_invitation_id','accept_date');
		$campaign_accepted = $db->fetchAll($select_campaigns);
		
		$select_active_campaigns = $db->select();
		$select_active_campaigns->from('campaign', '*');
		$select_active_campaigns->where('campaign_invitation.consumer_id = ?',$this->view->consumer['id']);
		$select_active_campaigns->where('campaign.expire_date > ?',$current_time);
		$select_active_campaigns->join('campaign_invitation', 'campaign.id = campaign_invitation.campaign_id');
		$select_active_campaigns->join('campaign_participation','campaign_invitation.id = campaign_participation.campaign_invitation_id','accept_date')->order('campaign.create_date desc');
		$this->view->activeCampaigns = $db->fetchAll($select_active_campaigns);
		
		$select_recent_invitation = $db->select();
		$select_recent_invitation->from('campaign_invitation','*');
		$select_recent_invitation->join('campaign', 'campaign.id = campaign_invitation.campaign_id', 'name');
		$select_recent_invitation->where('campaign_invitation.consumer_id = ?', $this->view->consumer['id']);
		$select_recent_invitation->where('campaign_invitation.state = ?', 'NEW');
		$select_recent_invitation->order('campaign_invitation.create_date DESC');
		$this->view->recentInvitation = $db->fetchRow($select_recent_invitation);
		$this->view->allInvitations = $db->fetchAll($select_recent_invitation);
		
		//post-campaign survey popup
		$select_post_campaign_notification = $db->select();
		$select_post_campaign_notification->from('campaign_participation','*');
		$select_post_campaign_notification->join('campaign_invitation', 'campaign_participation.campaign_invitation_id = campaign_invitation.id');
		$select_post_campaign_notification->join('campaign', 'campaign.id = campaign_invitation.campaign_id', 'name');
		$select_post_campaign_notification->where('campaign_invitation.consumer_id = ?', $this->view->consumer['id']);
		$select_post_campaign_notification->where('campaign_participation.state = ?', 'FINISHING');
		$select_post_campaign_notification->order('campaign_invitation.create_date');
		$this->view->postCampaignNotification = $db->fetchRow($select_post_campaign_notification);
		
		$selectAllCampaignReports = $db->select();
		$selectAllCampaignReports->from('report', "*")
		->joinLeft('reward_point_transaction_record', 'reward_point_transaction_record.id = report.reward_point_transaction_record_id', 'point_amount')
		->where('reward_point_transaction_record.point_amount > 0 or report.state="NEW"')
		->where('report.consumer_id = ?', $this->view->consumer['id']);
		
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
			//$selectApprovedCampaignReports->where('report.campaign_id in '.$activeCampaignId);
		}
			
		$this->view->allCampaignReports = $db->fetchAll($selectAllCampaignReports);
		$this->view->points_amounts = $points_amounts;
		$this->view->points_available = $points_available;
		$this->view->campaign_invitations = $campaign_invitations;
		$this->view->campaign_accepted = $campaign_accepted;
		$this->view->source_id = $this->_source_id;
		
		$this->view->xiaonei_name = $this->xiaonei_name();
		$this->view->xiaonei_picture = $this->xiaonei_picture();
	}
	function reportAction() {
		if ($this->autoAccountBinding()==false) {
			return;
		}
		$this->view->consumer = $this->_currentUser;
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("Create_title");
						
		$consumer = $this->_currentUser;
		$id = (int)$this->_request->getParam('id', 0);

		$campaigninvitationModel = new CampaignInvitation();
		$campaigninvitation = $campaigninvitationModel->fetchRow('campaign_id = '.$id.' and consumer_id'.' ='.$this->view->consumer['id']);
		if($campaigninvitation == null){
			$this->_helper->redirector('index','home');
		}

		$campaignModel = new Campaign();
		$campaign = $campaignModel->fetchRow("id=".$id);

		
		$langNamespace = new Zend_Session_Namespace('Lang');
		$lang = $langNamespace->lang;
		
		$surveyId =	$campaign->i2_survey_id;
		//$surveyId = 405; // debug
		
		$this->view->campaing_name = $campaign->name;
		$indicate2Connect = new Indicate2_Connect();
		$accesscode = $indicate2Connect->createParticipation($this->view->consumer['email'],$surveyId);
		$config = Zend_Registry::get('config');
		$this->view->filloutPage = $config->indicate2->home."/core/".$accesscode."/theme/wildfire/callback/reportdo";
		$this->view->id = $id;
		$this->view->includeCrystalCss = true;
		Zend_Debug::dump($accesscode);
		Zend_Debug::dump($this->view->filloutPage);
		Zend_Debug::dump($id);
	}
	function reportdoAction() {
		if ($this->autoAccountBinding()==false) {
			return;
		}
		if ($this->_request->isPost()) {
			$formData = $this->_request->getPost();
			if ($this->postToIndicate($formData)) {
				//save report successfully
				$consumer = $this->_currentUser;
				$survey_id = (int)$this->_request->getParam('i2_survey_id', 0);
				
				$code= $this->_request->getParam('id');
				$currentTime = date("Y-m-d H:i:s");
				
				$reportModel = new Report();
				$duplicatedReport = $reportModel->fetchAll('report.accesscode = "'.$code.'"');
				
				if (count($duplicatedReport)==0){		
					$campaignModel = new Campaign();
					$campaign = $campaignModel->fetchRow("i2_survey_id =".$survey_id." or "."i2_survey_id_en =".$survey_id);

					$report = $reportModel->createRow();
					$report->consumer_id = $consumer['id'];
					$report->campaign_id = $campaign['id'];
					$report->create_date = $currentTime;
					$report->state = 'NEW';
					$report->accesscode = $code;
					$report->save();
				}
				$this->view->message = "Success";
			} else {
				//failed
				$this->view->message = "Failed";
				$this->render('failed');
			}
		} else {
			$this->view->xiaonei_redirect = $this->xiaonei_redirect('home');
			$this->render('redirect2home');
		}
	}
	
	function postcampaignAction() {
		if ($this->autoAccountBinding()==false) {
			return;
		}
		$consumer = $this->_currentUser;
		
		$campaignModel = new Campaign();
		$id =  (int)$this->_request->getParam('id', 0);
		$campaign = $campaignModel->fetchRow("id=".$id);
		
		$surveyId =	$campaign->post_campaign_survey;
				
		$indicate2Connect = new Indicate2_Connect();
		$accesscode = $indicate2Connect->createParticipation($consumer['email'],$surveyId);
		
		$config = Zend_Registry::get('config');
		$this->view->filloutPage = $config->indicate2->home."/core/".$accesscode."/theme/wildfire/callback/postcampaigndo";
		$this->view->id = $surveyId;
		$this->view->name = $campaign->name;
	}
	
	function postcampaigndoAction() {
		if ($this->autoAccountBinding()==false) {
			return;
		}
		if ($this->_request->isPost()) {
			$formData = $this->_request->getPost();
			if ($this->postToIndicate($formData)) {
				//save report successfully
				$consumer = $this->_currentUser;
				$survey_id = (int)$this->_request->getParam('i2_survey_id', 0);
				
				$code= $this->_request->getParam('id');
				$currentTime = date("Y-m-d H:i:s");
				
				$campaignModel = new Campaign();
				$this->view->campaign  = $campaignModel->fetchRow("post_campaign_survey=".$survey_id." or "."post_campaign_survey_en=".$survey_id);
				
				$db = Zend_Registry::get('db');
				$campaignId = $this->view->campaign->id;
				$this->view->campaign_id = $campaignId;
	
				if ($campaignId > 0) {	
					//change campaign_participation state
					$db = Zend_Registry::get('db');	
					$select2 = $db->select();
					$select2->from('campaign_participation','*')
					->join('campaign_invitation','campaign_invitation.id = campaign_participation.campaign_invitation_id', null)
					->where('campaign_invitation.consumer_id = ?', $consumer['id'])
					->where('campaign_invitation.campaign_id = ?', $campaignId)
					->where("campaign_participation.state != 'COMPLETED'");
					$isExist = $db->fetchAll($select2);
					if($isExist != null){	
						$campaing_participateModel = new CampaignParticipation();
						$campaign_participation = $campaing_participateModel->fetchRow('campaign_invitation_id = '.$isExist[0]['campaign_invitation_id']);
						$campaign_participation->state = 'COMPLETED';
						$campaign_participation->save();
						//add 200 points for member in reward_point_transaction_record
						$rewardPointTransactionRecordModel = new RewardPointTransactionRecord();
						$rewardPointTransaction = array(
							"consumer_id" => $consumer['id'],
							"date" => date("Y-m-d H:i:s"),
							"transaction_id" => "5",
							"point_amount" => "200"
						);
						$id = $rewardPointTransactionRecordModel->insert($rewardPointTransaction);
					}
					$this->view->message = "Success";
				}
			} else {
				//failed
				$this->view->message = "Failed";
				$this->render('failed');
			}
		} else {
			$this->view->xianei_redirect = $this->xiaonei_redirect('home');
			$this->render('redirect2home');
		}
	}
	
	function acceptinvitationAction() {
		if ($this->autoAccountBinding()==false) {
			return;
		}
		$consumer = $this->_currentUser;
		
		$campaignModel = new Campaign();
		$campaign_id =  (int)$this->_request->getParam('campaign_id', 0);	
		$campaign = $campaignModel->fetchRow("id=".$campaign_id);
		$surveyId =	$campaign->pre_campaign_survey;
		
		$indicate2Connect = new Indicate2_Connect();
		$accesscode = $indicate2Connect->createParticipation($consumer['email'],$surveyId);
		
		$this->view->survey_id = $surveyId;
		$this->view->campaign_name = $campaign->name;
		$this->view->campaign_id = $campaign_id;
		$this->view->accesscode = $accesscode;
		
		$config = Zend_Registry::get('config');
		$this->view->filloutPage = $config->indicate2->home."/core/".$accesscode."/theme/wildfire/callback/acceptinvitationdo";
	}
	
	function acceptinvitationdoAction() {
		if ($this->autoAccountBinding()==false) {
			return;
		}
		if ($this->_request->isPost()) {
			$formData = $this->_request->getPost();
			if ($this->postToIndicate($formData)) {
				
				//save report successfully
				$consumer = $this->_currentUser;
				$survey_id = (int)$this->_request->getParam('i2_survey_id', 0);
				
				$code= $this->_request->getParam('id');
				$currentTime = date("Y-m-d H:i:s");
				
				$this->saveacceptinvitation($survey_id, $consumer, $currentTime);
				$this->view->message = "Success";
			} else {
				//failed
				$this->view->message = "Failed";
				$this->render('failed');
			}
		} else {
			$this->view->xianei_redirect = $this->xiaonei_redirect('home');
			$this->render('redirect2home');
		}
	}

	function saveacceptinvitation($survey_id, $consumer, $currentTime) {
		$campaignModel = new Campaign();
		$this->view->campaign  = $campaignModel->fetchRow("pre_campaign_survey=".$survey_id." or "."pre_campaign_survey_en=".$survey_id);
		
		$db = Zend_Registry::get('db');
		if($this->view->campaign != null){
			$campaignId = $this->view->campaign->id;
		}

		if ($this->view->campaign != null && $campaignId != null && $campaignId > 0) {
			$campaignInvitationModel = new CampaignInvitation();
			$campaignInvitation = $campaignInvitationModel->fetchRow("campaign_id=".$campaignId." and consumer_id=".$consumer['id']);
			$id = $campaignInvitation->id;

			$campaignInvitation->state = "ACCEPTED";
			$campaignInvitation->save();

			$result = $db->fetchOne(
    					"SELECT COUNT(*) FROM campaign_participation WHERE campaign_invitation_id=:t1",
						array('t1' => $id)
						);
						
			if($result==0) {
				//create participation
				$campaignParticipationModel = new CampaignParticipation();
				$currentTime = date("Y-m-d H:i:s");
				$row = $campaignParticipationModel->createRow();
				$row->campaign_invitation_id = $survey_id;
				$row->accept_date = $currentTime;
				$row->state = 'NEW';
				$row->save();
			}
		}
	}
	
	function postToIndicate($formData) {
		if ($this->_debug) {
			$host = "192.168.0.194";
			$port = "8280";
			$dir = "/fillout";
		} else {
			$host = "q.xingxinghuo.cn";
			$port = "8080";
			$dir = "/fillout";
		}
		//command.submit=Submit&id=AeC2j3VucVJZ&theme=wildfire&answers[5014].id=&answers[5014].value=1198&answers[5015].id=&answers[5015].value=1215&answers[5016].id=&answers[5016].value=1242&answers[5017].id=&answers[5017].value=1232&answers[5018].id=&answers[5018].value=0000000&answers[5019].id=&answers[5019].value=1959&command_submit=Submit
		$answers = $formData["answers"];
		$accesscode = $formData["id"];
		
		$req = 'command.submit=Submit';
		foreach ($formData as $ipnkey => $ipnvalue) { 
			if (is_array($ipnvalue)) {
				foreach ($ipnvalue as $subkey => $subvalue) {
					$value = urlencode(stripslashes($subvalue));
					$req .= "&answers[$subkey].id=&answers[$subkey].value=$value";
				}
			} else {
				$value = urlencode(stripslashes($ipnvalue));
				$req .= "&$ipnkey=$value";
			}
		}
		//for debug
		//echo $req;
		 
		$header = "POST ".$dir." HTTP/1.1\r\n";
		$header .= "Host: ".$host.":".$port."\r\n";
		$header .= "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.2; zh-CN; rv:1.9.0.13) Gecko/2009073022 Firefox/3.0.13 (.NET CLR 3.5.30729)\r\n";
		$header .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
		$header .= "Accept-Language: zh-cn;q=0.3\r\n";
		$header .= "Accept-Encoding: gzip,deflate\r\n";
		$header .= "Accept-Charset: utf-8\r\n";
		$header .= "Referer	http://".$host.":".$port."/fillout/".$accesscode."\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: ".strlen($req)."\r\n\r\n";
		//for debug
		//echo '<pre>'.$header.'</pre>';
		$fp = fsockopen ($host, $port, $errno, $errstr, 30);
		    
		if (!$fp) {
			echo "http error";
			return false; // http error
		} else {   
			fputs ($fp, $header . $req);
			$is_success = false;
			while (!feof($fp)) {
				//for debug
//				do {
//					$res = fgets ($fp, 2048);
//					Zend_Debug::dump($res);
//				} while ($res);
				
				$res = fgets ($fp, 1024);
				if (strcmp($res, "HTTP/1.1 302")) {
					$is_success = true;
					break;
				}
			}
			fclose($fp);
			return $is_success;
		}
	}
	
	function saveprecampaignAction() {
		if ($this->autoAccountBinding()==false) {
			return;
		}
		if ($this->_request->isPost()) {
			$campaign_id =  (int)$this->_request->getParam('campaign_id', 0);
			$formData = $this->_request->getPost();
			$this->view->postSuccess = $this->saveprecampaign($formData);
			
			$db = Zend_Registry::get('db');
			$select_invitations = $db->select();
			$select_invitations->from('campaign_invitation', '*');
			$select_invitations->where('campaign_id=?', $campaign_id);
			$select_invitations->where('consumer_id=?', $this->_currentUser['id']);
			$select_invitations->where('state=?', 'NEW');
			$invitations = $db->fetchRow($select_invitations);
			
			if (!isset($invitations['id'])) {
				Zend_Debug::dump('The consumer have no right to reject this invitation');
				return;
			}
			$consumer = $this->_currentUser;
			$invitation_model = new CampaignInvitation();
			$invitation_model_row = $invitation_model->fetchRow('id='.$invitations['id']);
			$invitation_model_row->state = 'ACCEPTED';
			$invitation_model_row->save();

		} else {
			$this->render('redirect2home');
		}	
	}
	
	function saveprecampaign($formData) {	
		$host = "q.xingxinghuo.cn";
		$dir = "/fillout";
		//command.submit=Submit&id=AeC2j3VucVJZ&theme=wildfire&answers[5014].id=&answers[5014].value=1198&answers[5015].id=&answers[5015].value=1215&answers[5016].id=&answers[5016].value=1242&answers[5017].id=&answers[5017].value=1232&answers[5018].id=&answers[5018].value=0000000&answers[5019].id=&answers[5019].value=1959&command_submit=Submit
		$answers = $formData["answers"];
		$accesscode = $formData["id"];
		$req = 'command.submit=Submit';
		$req .= '&answers[5030].id=';
		$req .= '&answers[5030].value='.$answers["5030"];
		$req .= '&answers[5031].id=';
		$req .= '&answers[5031].value='.$answers["5031"];
		$req .= '&answers[5032].id=';
		$req .= '&answers[5032].value='.$answers["5032"];
		$req .= '&answers[5033].id=';
		$req .= '&answers[5033].value='.$answers["5033"];
		$req .= '&answers[5034].id=';
		$req .= '&answers[5034].value='.$answers["5034"];
		$req .= '&answers[5035].id=';
		$req .= '&answers[5035].value='.$answers["5035"];
		$req .= '&theme=wildfire';
		$req .= '&id='.$accesscode;
		 
		$header = "POST ".$dir." HTTP/1.0\r\n";   
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";   
		$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";   
		$fp = fsockopen ($host, 8080, $errno, $errstr, 30);
		    
		if (!$fp) {  
			return false; // http error
		} else {   
			fputs ($fp, $header . $req);
			$is_success = false;
			while (!feof($fp)) {
				$res = fgets ($fp, 1024);
				if (strcmp($res, "HTTP/1.1 302")) {
					$is_success = true;
					break;
				}
			}
			fclose($fp);
			return $is_success;
		}
	}
	
	function bindaccountAction() {
	}
	
	function bindaccountdoAction() {
		$this->view->flashMessenger = null;
		$formData = $this->_request->getPost();
		$email = $formData['email'];
		$password = $formData['password'];
		
		$db = Zend_Registry::get('db');
		$select_consumer = $db->select();
		$select_consumer->from('consumer', '*');
		$select_consumer->where('email=?', $email);
		$select_consumer->where('password=?', md5($password));
		$consumer = $db->fetchRow($select_consumer);
		
		if (!isset($consumer['id'])) {
			$this->view->flashMessenger = '验证失败';
			$this->render('bindaccount');
			return;
		}
		
		$currentTime = date("Y-m-d H:i:s");
		$external_consumer_model = new ExternalConsumer();
		$external_consumer_row = $external_consumer_model->createRow();
		$external_consumer_row->consumer_id = $consumer['id'];
		$external_consumer_row->source_id = $this->_source_id;
		$external_consumer_row->source_type = 'xiaonei';
		$external_consumer_row->start_date = $currentTime;
		$external_consumer_row->save();
		
		$this->_currentUser = $consumer;
		
		$this->view->xiaonei_redirect = $this->xiaonei_redirect('home');
	}
	
	function bindaccountdoneAction() {
	}

	function invitefriendAction() {
		
	}
	

	
	function rejectinvitationAction() {
		if ($this->autoAccountBinding()==false) {
			return;
		}
		$consumer = $this->_currentUser;
		$campaign_id = $this->_request->getParam('campaign_id', 0);
		
		if ($campaign_id == 0) {
			Zend_Debug::dump('it should redirect to an error page');
			return;
		}
			
		$db = Zend_Registry::get('db');
		$select_invitations = $db->select();
		$select_invitations->from('campaign_invitation', '*');
		$select_invitations->where('campaign_id=?', $campaign_id);
		$select_invitations->where('consumer_id=?', $consumer['id']);
		$select_invitations->where('state=?', 'NEW');
		$invitations = $db->fetchRow($select_invitations);
		
		if (!isset($invitations['id'])) {
			Zend_Debug::dump('The consumer have no right to reject this invitation');
			return;
		}
		
		$invitation_model = new CampaignInvitation();
		$invitation_model_row = $invitation_model->fetchRow('id='.$invitations['id']);
		$invitation_model_row->state = 'REJECTED';
		$invitation_model_row->save();
	}
	
	function campaignhistoryAction() {
		//$this->view->title = $this->view->translate('His_Campaigns');
		$this->view->activeTab = "History";
		// get current page(default page = 1)
		if($this->_request->getParam('page'))
        {
        	$this->_curPage = $this->_request->getParam('page');
        }
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from("campaign", array('name', 'expire_date'));	
		$select->join("campaign_invitation", "campaign.id=campaign_invitation.campaign_id", null);
		$select->join("campaign_participation", "campaign_invitation.id=campaign_participation.campaign_invitation_id", "campaign_participation.accept_date");
		$select->where("campaign_invitation.state = 'ACCEPTED'");
		$select->where("campaign_invitation.consumer_id = ?", $this->view->consumer['id']);
		$select->order('campaign_participation.accept_date desc');
		$campaignsAll = $db->fetchAll($select);
		//paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($campaignsAll));
		$paginator->setCurrentPageNumber($this->_curPage)
		->setItemCountPerPage($this->_rowsPerPage); 
		$this->view->paginator = $paginator; 

        //set the No. inital value in view page
        $this->view->NoInitValue = ($this->_curPage-1)*$this->_rowsPerPage+1;
	}
	
	function reporthistoryAction() {
		$this->view->activeTab = "History";
		// get current page(default page = 1)
		if($this->_request->getParam('page'))
        {
        	$this->_curPage = $this->_request->getParam('page');
        }
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from("report", '*');
		$select->join("campaign", "report.campaign_id = campaign.id","name");
		$select->joinLeft("reward_point_transaction_record","reward_point_transaction_record.id = report.reward_point_transaction_record_id");
		$select->where("report.consumer_id = ".$this->view->consumer['id']);
		$select->order('report.create_date desc');
		$reportsAll = $db->fetchAll($select);
		//paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($reportsAll));
		$paginator->setCurrentPageNumber($this->_curPage)
		->setItemCountPerPage($this->_rowsPerPage); 
		$this->view->paginator = $paginator;
		
        //set the No. inital value in view page
        $this->view->NoInitValue = ($this->_curPage-1)*$this->_rowsPerPage+1;
//		Zend_Debug::dump($this->_currentUser->id);
	}
	
	function sendAction() {
		require_once('xiaonei.class.php');
		$api_key = $this->_api_key;
		$secret	= $this->_secret;
		$xn = new XNapp($api_key,$secret);
		$xn->notifications('send',array('to_ids'=>'31445092','notification'=>'1111111'));
	}
	
	function xiaonei_redirect($actionName) {
		if (!$this->_debug) {
			return '<xn:redirect url="http://apps.renren.com/xingxinghuo/'.$actionName.'" />';
		} else {
			$html = '此处将在校内网的框架内,自动转向. [';
			$html.= '<a href="'.$this->view->baseUrl().'/xiaoneicallback/'.$actionName.'">点此跳转</a>]';
			return $html;
		}
	}
	function xiaonei_name() {
		if (!$this->_debug) {
			return '<xn:name uid="loggedinuser" linked="true" shownetwork="true"/>';
		} else {
			return '<a title="测试模式:在校内框架中将显示实际的登录用户" href="">王伟琪</a>(吉林大学)';
		}
	}
	function xiaonei_picture() {
		if (!$this->_debug) {
			return '<xn:profile-pic uid="loggedinuser" linked="true" size="tiny" />';
		} else {
			return '<img src="'.$this->view->imageUrl.'/images/new_xiaonei/face.gif" />';
		}
	}
}