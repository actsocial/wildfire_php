<?php
include_once 'Indicate2Connect.php';
class ReportController extends MyController
{
	 
	function createAction()
	{
		
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("Create_title");	
		$this->view->activeTab = 'Campaigns';
						
		$consumer = $this->_currentUser;
		$id = (int)$this->_request->getParam('id', 0);
		//whether participate in the campaign
		$campaigninvitationModel = new CampaignInvitation();
		$campaigninvitation = $campaigninvitationModel->fetchRow('campaign_id = '.$id.' and consumer_id'.' ='.$consumer->id);
		if($campaigninvitation == null){
			$this->_helper->redirector('index','home');
		}
		//get i2_survey_id
		$campaignModel = new Campaign();
		$campaign = $campaignModel->fetchRow("id=".$id);

		
		$langNamespace = new Zend_Session_Namespace('Lang');
		$lang = $langNamespace->lang;
		
		if ($lang=='en'){
			$surveyId =	$campaign->i2_survey_id_en;
		}else{
			$surveyId =	$campaign->i2_survey_id;
		}
		$this->view->campaing_name = $campaign->name;
		$this->view->id = $id;
		
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
            //Zend_Debug::dump($this->view->filloutPage);
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
		$this->view->includeCrystalCss = true;
		$this->_helper->layout->setLayout("layout_questionnaire");
		$this->view->consumer = $consumer->id;
//		
//		Zend_Debug::dump($campaigninvitation);
//		Zend_Debug::dump($this->view->campaing_name);
	}
	
    function saveuploaddataAction(){  
        $name      = $this->_request->getParam('name');
		$consumer  = $this->_request->getParam('consumer');
		$report    = $this->_request->getParam('report');
		$accesscode    = $this->_request->getParam('accesscode');		
        $this->_helper->layout->disableLayout();
		$reportImageModel = new ReportImages();
		$row = $reportImageModel->createRow();
		$row->consumer = $consumer;
		$row->report   = $report;
		$row->name     = $name ;
		$roe->accesscode = $accesscode;
		$row->crdate   = date('Y-m-d h:i:s');
		$row->save();
		//die;
	}

	function thankyouAction(){
		$adminAddSession = Zend_Session::namespaceGet("adminAddSession");
		$id = (int)$this->_request->getParam('survey', 0);	
		$currentTime = date("Y-m-d H:i:s");
		$code= $this->_request->getParam('c');
		

		if (isset($adminAddSession['consumer'])) {
			$consumer_id = $adminAddSession['consumer'];
		}else{
			$consumer = $this->_currentUser;
			$consumer_id = $consumer->id;


//		$id = 267;
		
		if ($consumer->getTableClass() == 'Admin') { // if admin get report from session (sms report)
		    if (Zend_Session::namespaceIsset("AgentReports")) {
			$session = Zend_Session::namespaceGet("AgentReports");
			if (isset($session[$code]) && $session[$code] != null) {
			    $consumer_id = $session[$code];
			    $session[$code] = null; // delete this accesscode
			    $this->view->adminredirect = true; // for admin redirect
			}
		    }
		}
        
		}
		
		$reportModel = new Report();
		$duplicatedReport = $reportModel->fetchAll('report.accesscode = "'.$code.'"');		
			
		$campaignModel = new Campaign();
			
		$campaign = $campaignModel->fetchRow("i2_survey_id =".$id." or "."i2_survey_id_en =".$id);
		//create a record in report table
			
		if (count($duplicatedReport)==0){		

			$report = $reportModel->createRow();
			$report->consumer_id = $consumer_id;
			$report->campaign_id = $campaign->id;
			$report->create_date = $currentTime;
			$session = Zend_Session::namespaceGet("AgentReports");
			if (isset($session[$code]) && $session[$code] != null) {
			  $report->source = $session[$code.'_source'];
			  $session[$code.'_source'] = null;
			}
			//ham.bao 2011/04/29 admin add the report
			$adminAddSession = Zend_Session::namespaceGet("adminAddSession");
			if (isset($adminAddSession['consumer'])) {
			  $this->view->adminredirect = true;
			  $report->source = $adminAddSession['source'];
			  $report->consumer_id =  $adminAddSession['consumer'];
			  $report->campaign_id =  $adminAddSession['campaign'];
			}
			$report->state = 'NEW';
			$report->accesscode = $code;
			$reportId = $report->save();
			$this->view->reportId = $reportId;
			if($this->view->adminredirect){
			    //ham.bao 2010-10-13 update the incoming_email state
			    if(Zend_Session::namespaceIsset("IncomingEmail")){
						$emailSession 		= new Zend_Session_Namespace('IncomingEmail');
						$incomingEmailModel = new IncomingEmail();
						$incomingEmailModel->update(array('report_id'=>$reportId),'id='.$emailSession->id);
						$this->_helper->redirector('successconvert','email');
			    }
				//ham.bao 2011/04/29 admin add the report
			    if (isset($adminAddSession['consumer'])) {
			    	$this->_helper->redirector('successconvert','email');
			    }				
			}
			//change state in campaign_particpation table
//			$invitationModel = new CampaignInvitation();
//			$invitation = $invitationModel->fetchRow("campaign_id =".$campaign->id." and consumer_id=".$consumer->id);
//			
//			$participationModel = new CampaignParticipation();
//			$participation = $participationModel->fetchRow('campaign_invitation_id = '.$invitation->id);
//			$participation->state = 'REPORT SUBMITTED';
//			$participation->save();
		}else{
			$this->view->reportId = $duplicatedReport[0]['id'];
		}
	    $option = array($this->view->reportId,$consumer_id);
		$form = new ReportForm($option);
		$this->view->form = $form;
		
		if( $this->_request->isPost() ){
				$image         = $form->getValue ( 'image' );
				if ( $image !='') {
					$reportImage   = new ReportImages();
					$row           = $reportImage->createRow();
					$row->name     = $image;
					$row->consumer = $consumer_id;
					$row->report   = $this->view->reportId ;
					$row->crdate   = date('Y-m-d H:i:s');
					$row->save();
					$this->view->saved = 1;
				}else{
					$this->view->saved = -1;
				}
				
				//var_dump($image);die;
		}
		$this->view->consumer = $consumer_id;
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("Thanks_For_report");	
	}
	
    function adminreplyAction(){
    	$reportId = $this->_request->getParam('report_id');
    	$reportModel = new Report();
    	$report = $reportModel->find($reportId)->current();
    	$this->view->report_id = $reportId;

    	$consumerModel = new Consumer();
    	$this->view->consumer = $consumerModel->find($report['consumer_id'])->current();
    	
    	$campaignModel = new Campaign();
    	$campaign = $campaignModel->find($report['campaign_id'])->current();
    	$this->view->campaign_name = $campaign->name;
    	$this->view->campaign_id = $campaign->id;
    	//get new report
    	$config = Zend_Registry::get('config');
    	$url = $config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']; 
		$contents = file_get_contents($url); 
	    $this->view->report_content = $contents;
 	    
		$this->view->title = "Reply Reports";
		$this->view->activeTab = "Reply Reports";
		$this->view->mailForm = new ReplyReportForm();
		$this->view->mailForm->email->setValue($this->view->consumer['email']);
		$db = Zend_Registry::get('db');
		$selectReportPoint = $db->select();
		$selectReportPoint->from('report',null)
		->joinLeft('reward_point_transaction_record', 'report.reward_point_transaction_record_id = reward_point_transaction_record.id','point_amount')
		->where('report.id = ?', $reportId);
		$reportPoint = $db->fetchOne($selectReportPoint);
		if($reportPoint != null){
			$this->view->mailForm->grade->setValue($reportPoint);
		}
		$replyModel = new Reply();
		$reply = $replyModel->fetchRow('report_id = '.$reportId);
		if($reply != null){
			$this->view->mailForm->message->setValue($reply['content']);
			$this->view->status = $reply['status'];
		}
		$this->view->mailForm->subject->setValue($this->view->translate('Admin_Reply_WOM_Report_Subject'));
		$this->_helper->layout->setLayout("layout_admin");
		
    	// get all old reports
	    
	    $select = $db->select();
		$select->from('report', array('id', 'accesscode','create_date'))
		->where('consumer_id = ?', $this->view->consumer['id'])
		->order('create_date desc');
		$oldreportArray = $db->fetchAll($select);
		
		$this->view->oldContents = "<h1 style='margin-left: 0px;'>Other Old Reports:</h1>";
		$i = 1;
		foreach($oldreportArray as $oldreport){
			if($report['accesscode'] != $oldreport["accesscode"]){
				$url = $config->indicate2->home."/report/showAnswer/accessCode/".$oldreport["accesscode"];
				$reply = $replyModel->fetchRow('report_id = '.$oldreport['id']);
				$this->view->oldContents .= "<p>Report ".$i."--Your answer(".$oldreport['create_date']."):</p><p>".file_get_contents($url).
				"</p><p>Report ".$i++."--Our response(".$reply['date']."):</p><p>".$reply['content']."</p><br><br><br><br>";
			}
		}
		// get participation state
//		$selectParticipation = $db->select();
//		$selectParticipation->from('campaign_participation', array('state'))
//		->join('campaign_invitation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id', null)
//		->where('campaign_invitation.campaign_id = ?', $campaign->id)
//		->where('campaign_invitation.consumer_id = ?', $this->view->consumer['id']);
//		$this->view->participationState = $db->fetchOne($selectParticipation);
	}

	function adminreplysendAction(){
		$this->_helper->layout->setLayout("layout_admin");
		
		if ($this->_request->isPost()) {
				$form = new ReplyReportForm();
				$formData = $this->_request->getPost();
				if ($form->isValid($formData)) {
					//1.
					$config = Zend_Registry::get('config');
					$smtpSender = new Zend_Mail_Transport_Smtp(
								$config->smtp->report->mail->server,
								array(
									'username'=> $config->smtp->report->mail->username,
									'password'=> $config->smtp->report->mail->password,
									'auth'=> $config->smtp->report->mail->auth,
									'ssl' => $config->smtp->report->mail->ssl,
			               			'port' => $config->smtp->report->mail->port));
					
//											$smtpSender = new Zend_Mail_Transport_Smtp(
//																				'smtp.163.com',array(
//																				'username'=>'yun_simon@163.com',
//																				'password'=>'19990402',
//																				'auth'=>'login'));
					Zend_Mail::setDefaultTransport($smtpSender);
					$mail = new Zend_Mail('utf-8');
					
					$db = Zend_Registry::get('db');
					$select = $db->select();
					$select->from('consumer', '*');
					$select->where('email = ?',$form->getValue('email'));
					$consumer = $db->fetchAll($select);
					if($consumer[0] != null){
						//2.get "Your story" from report
						$reportId = $formData['report_id'];
				    	$reportModel = new Report();
				    	$report = $reportModel->find($reportId)->current();
				    	$config = Zend_Registry::get('config');
				    	$url_zh = $config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/645";
						$url_en = $config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/707";
						$url_mission =$config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/3153";
						$url_mission1 =$config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/2769";
						$url_mission2 =$config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/3166";
						$url_mission3 =$config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/3171";
						$url_mission4 =$config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/2293";//童装
						$url_mission5 =$config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/3239";//电影大赏
						$contents = file_get_contents($url_zh).file_get_contents($url_en).file_get_contents($url_mission).file_get_contents($url_mission1).file_get_contents($url_mission2).file_get_contents($url_mission3).file_get_contents($url_mission4).file_get_contents($url_mission5);
				    	$contents = trim($contents);
						$contents = preg_replace('/\s(?=\s)/', '', $contents);
						$contents = preg_replace('/[\n\r\t]/', ' ', $contents);
						$contents = preg_replace('/&nbsp;/', '', $contents);	
						preg_match_all ("|<div class.*answer_content.*>(.*)</[^>]+>|U", $contents, $out, PREG_PATTERN_ORDER);
						
					    //3.create email and send
						$emailSubject = $this->view->translate('Admin_Reply_WOM_Report_Subject');
						if($consumer[0]['language_pref'] != null && $consumer[0]['language_pref'] == 'en'){
							$emailBody = $this->view->translate('Admin_Reply_WOM_Report_Body_en');
						}else{
							$emailBody = $this->view->translate('Admin_Reply_WOM_Report_Body_zh');
						}
						
						$stringChange = array(
							'?USERNAME?' => $consumer[0]['name'],
							'?YOURSTORY?' => $out[1][0],
							'?MYRESPONSE?' => $form->getValue('message'));
						$emailBody = strtr($emailBody,$stringChange);
							
						$langNamespace = new Zend_Session_Namespace('Lang');
							if($langNamespace->lang == 'en' || $langNamespace->lang == 'EN'){
								$mail->setSubject($emailSubject);
							}else{
								$mail->setSubject("=?UTF-8?B?".base64_encode($emailSubject)."?=");
							}
						$mail->setBodyText($emailBody);
						$mail->addTo($form->getValue('email'));
						$mail->setFrom($config->smtp->report->mail->username, $this->view->translate('Wildfire'));
//						$mail->setFrom('yun_simon@163.com',$this->view->translate('Wildfire'));
						//send!
						$mail->send();
		 									
						//4.save "reply email" into DB		
						$replyModel = new Reply();
						$reply = $replyModel->fetchRow('report_id = '.$this->_request->getParam('report_id'));
						//check reply condition!
						if($reply != null && $reply->status == 'SENT'){
							$this->view->showMessage = "Reply fail: the reply has been sent!";
							return;
						}
						if($reply == null){
							$replyModel = new Reply();
							$reply = $replyModel->createRow();		
						}
						$currentTime = date("Y-m-d H:i:s");		
						$reply->date = $currentTime;
						$reply->subject = $form->getValue('subject');
						$reply->content = $form->getValue('message');
						$reply->from = $config->smtp->report->mail->username;
	
						$reply->campaign_id = $formData['campaign_id'];
						$reply->report_id = $formData['report_id'];
						$reply->to = $form->getValue('email');
						$reply->status = 'SENT';
						//2011-04-08 ham.bao separate the sessions with admin
						$reply->admin_id = $this->_currentAdmin->id;
						$reply->save();
						//5. grade
						$rewordReocrdModel = new RewardPointTransactionRecord();
						$report_id = (int)$this->_request->getParam('report_id');
						$table = new Report();
						$row = $table->fetchRow('id = '.$report_id);
	
						if($row->reward_point_transaction_record_id == null){
							//create new grade for 
							$rewordReocrd = $rewordReocrdModel->createRow();
						
							$rewordReocrd->consumer_id = $consumer[0]['id'];
							$rewordReocrd->date = date("Y-m-d H:i:s");
							$rewordReocrd->transaction_id = 1;
							$rewordReocrd->point_amount =  $form->getValue('grade');
							$rewordReocrd->save();
						}else{
							//update grade
							$rewordReocrd = $rewordReocrdModel->fetchRow('id = '.$row->reward_point_transaction_record_id);
							$rewordReocrd->date = date("Y-m-d H:i:s");
							$rewordReocrd->point_amount = $form->getValue('grade');
							$rewordReocrd->save();
						}
						$row->reward_point_transaction_record_id = $rewordReocrd->id;
						$row->save();
						//2011-05-13 change the rank of consumer 
						$rankModel = new Rank();
						$rankModel->changeConsumerRank($consumer[0]['id']);
						
						$this->view->showMessage = $this->view->translate('Admin_Reply_the_report_successfully');
					}else{
						$this->view->showMessage = "Reply fail: this email doesn't exist in DB!";
					}			
				}else{
					$this->view->showMessage = 'Reply fail: please input the right data!';
				}
		
			
		}else{
			$this->view->showMessage = "err!";
		}
		
//		Zend_Debug::dump($formData);
//		$this->_helper->redirector('adminreply','report');
	}

	function updatebatchtotaltime($batchid,$addtive){
			$replybatchModel = new ReportBatch();
			$replybatch= $replybatchModel->fetchRow('id='.$batchid);
			$replybatch->totaltime += $addtive;
			$replybatch->save();		
	}
	
	function saveReportReward($reportid,$points){
			$rewardReocrdModel = new RewardPointTransactionRecord();
			$table = new Report();
			$row = $table->fetchRow('id = '.$reportid);
	
			if($row->reward_point_transaction_record_id == null){
				//create new grade for 
				$rewardReocrd = $rewardReocrdModel->createRow();
			
				$rewardReocrd->consumer_id = $row->consumer_id;
				$rewardReocrd->date = date("Y-m-d H:i:s");
				$rewardReocrd->transaction_id = 1;
				$rewardReocrd->point_amount =  $points;
				$rewardReocrd->save();
			}else{
				//update grade
				$rewardReocrd = $rewardReocrdModel->fetchRow('id = '.$row->reward_point_transaction_record_id);
				$rewardReocrd->date = date("Y-m-d H:i:s");
				$rewardReocrd->point_amount =  $points;
				$rewardReocrd->save();
			}
			$row->reward_point_transaction_record_id = $rewardReocrd->id;
			$row->save();
			// add notification
//			$notificationModel = new Notification();
//			$notificationModel->createRecord("REPORT_REPLY",$row->consumer_id,$points);
	}
	
	function saveTags($report_id,$tagArray){
		$reportTagModel = new ReportTag();
		$db = $reportTagModel->getAdapter ();
		$where = $db->quoteInto ( 'report_id = ?', $report_id );
		$rows_affected = $reportTagModel->delete ( $where );
		if(isset($tagArray)){
			foreach ( $tagArray as $tag ) {
				$reportTag = $reportTagModel->createRow ();
				$reportTag->report_id = $report_id;
				$reportTag->tag_id = $tag;;
				$reportTag->save ();
			}
		}
	}
	
	function adminsavereplyAction(){
		ini_set('display_errors', 1);
		$frontController = Zend_Controller_Front::getInstance();
		$frontController->throwExceptions(true);
		
		$report_id = $this->_request->getParam('report_id');
		$replyModel = new Reply();
		$reply = $replyModel->fetchRow('report_id = '.$report_id);
		// save reply email
		// if the email has been sent, pass it
		if($this->_request->getParam('message') != '' && ($reply == null || $reply->status != 'SENT') ){
			if($reply == null){
				$replyModel = new Reply();
				$reply = $replyModel->createRow();		
			}
			$currentTime = date("Y-m-d H:i:s");	
			$reply->date = $currentTime;
			$reply->subject = $this->_request->getParam('subject');
			$reply->content = $this->_request->getParam('message');
			$config = Zend_Registry::get('config');
			$reply->from = $config->smtp->report->mail->username;
			
			$reply->campaign_id = $this->_request->getParam('campaign_id');
			$reply->report_id = $this->_request->getParam('report_id');
			$reply->to = $this->_request->getParam('email');
			$reply->status = 'TEMP';
			//2011-04-08 ham.bao separate the sessions with admin
			$reply->admin_id = $this->_currentAdmin->id;
			$addtive = $this->_request->getParam('usetime') - $reply->usetime;
			$reply->usetime = $this->_request->getParam('usetime');
			$reply->save();
			
			$batchid = $this->_request->getParam('batch_id');
			$this->updatebatchtotaltime($batchid,$addtive);
			
		}
		// save point
		// if the email has been sent, pass it
		if($reply == null || $reply->status != 'SENT'){
			$this->saveReportReward($report_id, $this->_request->getParam('grade'));
		}
		
		// save tags
		$noteArray = $this->_request->getParam('note');
		$this->saveTags($report_id,$noteArray);
	}
	
	function adminreportAction(){
		$this->view->title = 'Reports';
		$this->view->activeTab = 'Reports';
//		Zend_Debug::dump($this->_currentAdmin);die();
		$this->view->currentAdmin=$this->_currentAdmin;
		$this->view->campaign_id = $this->_request->getParam('id');
		$this->state=$this->_request->getParam('state');
		$this->report_state=$this->_request->getParam('select_status');
		$this->view->select_status=$this->report_state;
		$curPage = 1;
		$rowsPerPage = 100;
		if($this->_request->getParam('page'))
        {
        	$curPage = $this->_request->getParam('page');
        }
		$db = Zend_Registry::get('db');
		//get report
		$select = $db->select();
		$select->from('report', 'report.*');
		$select->join('consumer', 'report.consumer_id = consumer.id',array('consumer.email','consumer.login_phone','consumer.name as consumername'));
		$select->join('campaign', 'campaign.id = '.$this->view->campaign_id, 'name');
		$select->join('campaign_invitation','campaign_invitation.consumer_id = consumer.id and campaign_invitation.campaign_id = '.$this->view->campaign_id,'campaign_invitation.state as cistate');
		$select->where('report.campaign_id = ? ', $this->view->campaign_id);
		if($this->report_state!=""&&$this->report_state!=null&&$this->report_state!="all"){
			$select->where('report.state = ? ', $this->report_state);
		}
		
		if($this->_request->getParam('pest') == '0'){
			$select->where('consumer.pest is null or consumer.pest != 1');
		}else{
			$select->where('consumer.pest = 1');
		}

		$select->order('report.create_date desc');
		$this->view->AllReports = $db->fetchAll($select);
		// add adminname to AllReports 
		
		//campagin 
		$campaignModel = new Campaign();
		$this->view->campaign = $campaignModel->fetchRow('id = '.$this->view->campaign_id);
		
		$num=0;
		
		$select_report_batch = $db->select();
		$select_report_batch->from('report_batch');
		$select_report_batch->join('admin','admin.id = report_batch.admin_id','admin.name as adminname');
		$select_report_batch->where('report_batch.campaign_id = '.$this->view->campaign_id );
		$report_batches = $db->fetchAll($select_report_batch);

		foreach ($this->view->AllReports as $rep):

            foreach ($report_batches as $batch):
            	if (strpos($batch["report_ids"],$rep['id'])>0){
            	  	$this->view->AllReports[$num]['adminname'] = $batch["adminname"];
            	}
            endforeach;
            
			$num++;
		endforeach;

//		Zend_Debug::dump($this->view->AllReports );die();
		$this->view->totalReports = count($this->view->AllReports);
		
		$selectcount = $db->select();
		$selectcount->from('report', 'count(*)');
		$selectcount->where('report.state != "UNAPPROVED"');
		$this->view->totalApprovedReports = $db->fetchAll($selectcount);
		//get report amount for each member
		$selectReportAmount = $db->select();
		$selectReportAmount->from('report',array('count(*)', 'consumer_id'))
		->group('consumer_id');
		$reportAmounts = $db->fetchAll($selectReportAmount);
		$this->view->reportAmountArray = array();
		foreach($reportAmounts as $reportAmonut):
			$i = $reportAmonut["consumer_id"];
			$this->view->reportAmountArray[$i] = $reportAmonut["count(*)"];
		endforeach;
		$selectReportAmount->where("campaign_id = ?", $this->view->campaign_id);
		$reportCampaignAmonuts = $db->fetchAll($selectReportAmount);
		$this->view->reportCampaignAmountArray = array();
		foreach($reportCampaignAmonuts as $reportCampaignAmonut):
			$i = $reportCampaignAmonut["consumer_id"];
			$this->view->reportCampaignAmountArray[$i] = $reportCampaignAmonut["count(*)"];
		endforeach;
//		Zend_Debug::dump($reportCampaignAmonuts);
		//get report point
		$select2 = $db->select();
		$select2->from('reward_point_transaction_record', 'reward_point_transaction_record.*');
		$select2->where('reward_point_transaction_record.transaction_id = 1');
		$AllRecords = $db->fetchAll($select2);
		
		$recordsArray = array();
		foreach($AllRecords as $record):
			$i = $record["id"];
			$recordsArray[$i] = $record["point_amount"];
		endforeach;
		
		$this->view->pointArray = array();
		$this->view->reportArray = array();
		foreach($this->view->AllReports as $report):
			if($report['reward_point_transaction_record_id'] != null && array_key_exists($report['reward_point_transaction_record_id'],$recordsArray)){
				$j = $report["id"];
				$k = $report['reward_point_transaction_record_id'];
				$this->view->pointArray[$j] = $recordsArray[$k];
			}		
		endforeach;
		//get report reply
		$select3 = $db->select();
		$select3->from('reply',array('report_id', 'status'));
		$allReplys = $db->fetchAll($select3);
		$this->view->allReplysArray = array();
		foreach($allReplys as $reply):
			$i = $reply["report_id"];
			$this->view->allReplysArray[$i] = $reply["status"];
		endforeach;
		//paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($this->view->AllReports));
		$paginator->setCurrentPageNumber($curPage)
		->setItemCountPerPage($rowsPerPage); 
		$this->view->paginator = $paginator; 
		//Zend_View_Helper_PaginationControl::setDefaultViewPartial('pagination/pagelist.phtml');
		$this->view->NoInitValue = ($curPage-1)*$rowsPerPage+1;
		
		$this->_helper->layout->setLayout("layout_admin");


	}
	
	function adminindexAction(){
		$this->view->title = 'Reports';
		$this->view->activeTab = 'Reports';
		
		$curPage = 1;
		$rowsPerPage = 50;
		if($this->_request->getParam('page'))
        {
        	$curPage = $this->_request->getParam('page');
        }
        
		$campaign = new Campaign();
		$order = "id desc";
		$this->view->campaigns = $campaign->fetchAll(null, $order, null, null);
		$db =  Zend_Registry::get('db');
		
		$selectNewReportAmount = $db->select();
		$selectNewReportAmount->from('report','count(*)')
		->join('campaign', 'campaign.id = report.campaign_id', 'id')
		->join('consumer', 'consumer.id = report.consumer_id', null)
		->where('consumer.pest is null')
		->where("report.state = 'NEW'")
		->group('report.campaign_id');
		$newReportAmount = $db->fetchAll($selectNewReportAmount);
		$this->view->newReportAmountArray = array();
		foreach($newReportAmount as $newreportamount){
			$this->view->newReportAmountArray[$newreportamount['id']] = $newreportamount['count(*)'];
		}
		$selectNewURLReportAmount = $db->select();
		$selectNewURLReportAmount->from('url_report','count(*)')
		->join('campaign', 'campaign.id = url_report.campaign_id', 'id')
		->where("url_report.state = 'NEW'")
		->group('url_report.campaign_id');
		$newURLReportAmount = $db->fetchAll($selectNewURLReportAmount);
		$this->view->newURLReportAmountArray = array();
		foreach($newURLReportAmount as $newreportamount){
			$this->view->newURLReportAmountArray[$newreportamount['id']] = $newreportamount['count(*)'];
		}
		$selectNewImageReportAmount = $db->select();
		$selectNewImageReportAmount->from('image_report','count(*)')
		->join('campaign', 'campaign.id = image_report.campaign_id', 'id')
		->where("image_report.state = 'NEW'")
		->group('image_report.campaign_id');
		$newImageReportAmount = $db->fetchAll($selectNewImageReportAmount);
		$this->view->newImageReportAmountArray = array();
		foreach($newImageReportAmount as $newreportamount){
			$this->view->newImageReportAmountArray[$newreportamount['id']] = $newreportamount['count(*)'];
		}
		
		$select = $db->select();
		$select->from('report_batch', '*')
		->join('admin', 'admin.id = report_batch.admin_id','name as admin_name')
		->join('campaign', 'campaign.id = report_batch.campaign_id', 'name as campaign_name')
		->order('report_batch.start_datetime desc');
			//2011-04-08 ham.bao separate the sessions with admin
		if($this->_currentAdmin->id != 2){
			$select->where('admin_id = '.$this->_currentAdmin->id);
		}
		
		$reportBatchs = $db->fetchAll($select);
		//paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($reportBatchs));
		$paginator->setCurrentPageNumber($curPage)
		->setItemCountPerPage($rowsPerPage); 
		$this->view->paginator = $paginator; 
        //set the No. inital value in view page
        $this->view->NoInitValue = ($curPage-1)*$rowsPerPage+1;
        //2011-04-08 ham.bao separate the sessions with admin
		$this->view->admin_id = $this->_currentAdmin->id;
		$this->_helper->layout->setLayout("layout_admin");
//		Zend_Debug::dump($this->view->paginator);
		$this->view->messageArray = $this->_flashMessenger->getMessages();
	}
	
	function adminupdatereportAction(){
		
		$rewordReocrdModel = new RewardPointTransactionRecord();
		
		$report_id = (int)$this->_request->getParam('set_report_id');
		$report_state = $this->_request->getParam('state');
		$db = Zend_Registry::get('db');
		//cant not be approved if it have not been replied, except pests 
//		if($report_state == 'APPROVED'){
//			$replyModel = new Reply();
//			$reply = $replyModel->fetchRow("report_id = ".$report_id." and status = 'SENT'");
//			if($reply == null){
//				$select = $db->select();
//				$select->from('report','*')
//				->join('consumer', 'consumer.id = report.consumer_id', null)
//				->where('report.id = ?', $report_id)
//				->where('consumer.pest is null');
//				$isPest = $db->fetchAll($select);
//				if($isPest != null){
//					return;
//				}
//			}
//		}
		
		$table = new Report();
		$row = $table->fetchRow('id = '.$report_id);
		
		if($row->reward_point_transaction_record_id == null){
			//create new grade for 
			$rewordReocrd = $rewordReocrdModel->createRow();
		
			$rewordReocrd->consumer_id = (int)$this->_request->getParam('consumer_id');
			$rewordReocrd->date = date("Y-m-d H:i:s");
			$rewordReocrd->transaction_id = 1;
			$rewordReocrd->point_amount =  (int)$this->_request->getParam('grade');
			$rewordReocrd->save();
		}else{
			//update grade
			$rewordReocrd = $rewordReocrdModel->fetchRow('id = '.$row->reward_point_transaction_record_id);
			$rewordReocrd->date = date("Y-m-d H:i:s");
			$rewordReocrd->point_amount = (int)$this->_request->getParam('grade');
			$rewordReocrd->save();
		}
		$row->reward_point_transaction_record_id = $rewordReocrd->id;
		$row->state = $report_state;
		$row->save();
		// for batch report
		$reportBatchModel = new ReportBatch();
		$reportBatch = $reportBatchModel->fetchRow("report_ids like '%,".$report_id.",%'");
		if($reportBatch == null){
			return;
		}else{
			
			$selectHaveNotReplied = $db->select();
			$selectHaveNotReplied->from('report','count(*)')
			->where('report.id in ('.substr($reportBatch['report_ids'],1,strlen($reportBatch['report_ids'])-2).')')
			->where("report.state = 'NEW' or report.state = 'LOCKED'");
			$notRepliedAmount = $db->fetchOne($selectHaveNotReplied);
			$reportBatch->state = $notRepliedAmount;
			if($notRepliedAmount == 0){
				$reportBatch->end_datetime = date("Y-m-d H:i:s");	
			}
			$reportBatch->save();
			
		}
//		$this->_helper->redirector('adminreport','report');
	}
	
	function ajaxreportAction(){
		$this->_helper->layout->disableLayout();
		
		$config = Zend_Registry::get('config');
		$this->view->answerset = $config->indicate2->home.$this->_request->getParam('url');
		
		$report_id = $this->_request->getParam('accessReportId');
		$replyModel = new Reply();
		$this->view->reply = $replyModel->fetchRow("report_id = ".$report_id);
		if($this->view->reply['admin_id'] != null){
			$adminModel = new Admin();
			$admin = $adminModel->fetchRow('id = '.$this->view->reply['admin_id']);
			$this->view->adminname = $admin['name'];
		}else{
			$this->view->adminname = '';
		}
//		Zend_Debug::dump($this->view->reply['admin_id']);
	}
	
		function ajaxstoreurlAction(){
		$currentTime = date("Y-m-d H:i:s");
		$campaign_id = (int)$this->_request->getParam('campaignId');
		$url = base64_decode($this->_request->getParam('url'));
		//looking for submitted URL
		$urlReportModel = new UrlReport();
		$urlreport = $urlReportModel->fetchRow("campaign_id = ".$campaign_id." and consumer_id=".$this->_currentUser->id ." and url like '".$url."'");
		//if it's audited then can't be changed
		if (isset($urlreport) && $urlreport->state=='APPROVED'){
			$this->_helper->json(false);
		}else{
			if (!isset($urlreport)){
				$urlreport = $urlReportModel->createRow();
				$urlreport->campaign_id = $campaign_id;
				$urlreport->consumer_id = $this->_currentUser->id;
				$urlreport->state = 'NEW';
				$urlreport->create_date = $currentTime;
			}
			$urlreport->url = $url;
			$urlreport->save();
			$this->_helper->json(true);
		}

	}

	function ajaxstoreimageAction(){
		 
		$currentTime = date("Y-m-d H:i:s");
		$uploaddir = 'images/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);

		$campaign_id = (int)$this->_request->getParam('campaign_id');

		$imgfile = $_FILES['userfile'];
		if (is_array($imgfile)) {
			$name = $imgfile['name'];
			$type = $imgfile['type'];
			$size = $imgfile['size'];
			if(!preg_match('/^image\//i', $type)?true:false) {
				$this->view->error = "璇蜂笂浼犳纭殑鍥剧墖";
			} else if($size > 2000000) {
				$this->view->error = "鍥剧墖涓嶅緱瓒呰�?M";
			} else {
				$tmpfile = $imgfile['tmp_name'];
							  if ($tmpfile && is_uploaded_file($tmpfile)) {
				$file = fopen($tmpfile, "rb");
				//$imgdata = bin2hex(fread($file,$size)); //bin2hex()灏嗕簩杩涘埗鏁版嵁杞崲鎴愬崄鍏繘鍒惰〃绀�?
				$imgdata = fread($file,$size);
				fclose($file);
				// save to db
				$imageReportModel = new ImageReport();
				$imageReport = $imageReportModel->createRow();
				$imageReport->campaign_id = $campaign_id;
				$imageReport->consumer_id = $this->_currentUser->id;
				$imageReport->state = 'NEW';
				$imageReport->create_date = $currentTime;
				$imageReport->file_name = $name;
				$imageReport->type = $type;
				$imageReport->image = $imgdata;

				$maxwidth=80;
				$maxheight=80;
				$im = imagecreatefromstring($imgdata);
				$width = imagesx($im);
				$height = imagesy($im);
				$newwidth = $width;
				$newheight = $height;
				if(($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight)){
					if($maxwidth && $width > $maxwidth){
						$widthratio = $maxwidth/$width;
						$RESIZEWIDTH=true;
					}
					if($maxheight && $height > $maxheight){
						$heightratio = $maxheight/$height;
						$RESIZEHEIGHT=true;
					}
					if($RESIZEWIDTH && $RESIZEHEIGHT){
						if($widthratio < $heightratio){
							$ratio = $widthratio;
						}else{
							$ratio = $heightratio;
						}
					}elseif($RESIZEWIDTH){
						$ratio = $widthratio;
					}elseif($RESIZEHEIGHT){
						$ratio = $heightratio;
					}
					$newwidth = $width * $ratio;
					$newheight = $height * $ratio;
				}
				$imageReport->thumb_width = round($newwidth);
				$imageReport->thumb_height = round($newheight);
				$imageReport->save();
				$this->view->imageReport = $imageReport;
			}
			}
	  };
	  $this->view->campaign_id = $campaign_id;
	  $this->_helper->layout->disableLayout();
	}
	
	function adminsetselectedreportAction(){
		
		$type = $this->_request->getParam('type');
		$db = Zend_Registry::get('db');
		if($type == 'reportState'){
			$idStr = $this->_request->getParam('reportids');
			$idStrArray = explode(',',$idStr);
			$table = new Report();
			$reportIdArray = array();
			$i = 0;
			foreach($idStrArray as $idAndState){
				if($idAndState == ''){
					continue;
				}
				$idAndStateArray = explode('@',$idAndState);
				//cant not be approved if it have not been replied, except pests 
//				if($idAndStateArray[1] == 'APPROVED'){
//					$replyModel = new Reply();
//					$reply = $replyModel->fetchRow("report_id = ".$idAndStateArray[0]." and status = 'SENT'");
//					if($reply == null){
//						$select = $db->select();
//						$select->from('report','*')
//						->join('consumer', 'consumer.id = report.consumer_id', null)
//						->where('report.id = ?', $idAndStateArray[0])
//						->where('consumer.pest is null');
//						$isPest = $db->fetchAll($select);
//						if($isPest != null){
//							return;
//						}
//					}
//				}
				
				$row = $table->fetchRow('id = '.$idAndStateArray[0]);
				$row->state = $idAndStateArray[1];
				if(!is_null($row->reward_point_transaction_record_id)){
					//update the grade 2010.08.30 HAM.BAO
					$grade = $db->prepare('update reward_point_transaction_record set point_amount= '.$idAndStateArray[2].' where id='.$row->reward_point_transaction_record_id);
					$grade->execute();				
					//update the grade 2010.08.30 HAM.BAO
					//2011-05-13 change the rank of consumer 
					$rankModel = new Rank();
					$rankModel->changeConsumerRank($row->consumer_id);	
				}				
				$row->save();
  			    $reportIdArray[$i++] = $idAndStateArray[0];
			}	
		}
		// for batch report
		foreach($reportIdArray as $report_id){
			$reportBatchModel = new ReportBatch();
			$reportBatch = $reportBatchModel->fetchRow("report_ids like '%,".$report_id.",%'");
			if($reportBatch == null){
				return;
			}else{
				
				$selectHaveNotReplied = $db->select();
				$selectHaveNotReplied->from('report','count(*)')
				->where('report.id in ('.substr($reportBatch['report_ids'],1,strlen($reportBatch['report_ids'])-2).')')
				->where("report.state = 'NEW' or report.state = 'LOCKED'");
				$notRepliedAmount = $db->fetchOne($selectHaveNotReplied);
				$reportBatch->state = $notRepliedAmount;
				if($notRepliedAmount == 0){
					$reportBatch->end_datetime = date("Y-m-d H:i:s");	
				}
				$reportBatch->save();
				
			}
		}
		
	}
	
	function admincreatebatchAction(){
		$this->_helper->layout->setLayout("layout_admin");
		$db = Zend_Registry::get('db');
		$this->view->adminid=$this->_currentAdmin->id;
		if ($this->_request->isPost()) {
			// validate report_id
			$displayedreport_ids = $report_ids = $this->_request->getParam('reportInBatch');
			$campaignId = $this->_request->getParam('id');
			$reportBatchModel = new ReportBatch();
			$len = count($report_ids);
			for($i = 0; $i<$len; $i++){
				$reportBatch = $reportBatchModel->fetchRow("report_ids like '%,".$report_ids[$i].",%'");
				if($reportBatch != null){
//					unset($report_ids[$i]);
					$this->_flashMessenger->addMessage("The Selected report conflicted, choose again please!");
					$this->_helper->redirector('adminindex','report');
//					return;
				}
			}	
			if(count($report_ids) > 0){
				$currentTime = date("Y-m-d H:i:s");
				// create batch record
				$reportBatchModel = new ReportBatch();
				$reportBatch = $reportBatchModel->createRow();
				$reportBatch->start_datetime = $currentTime;
				//2011-04-08 ham.bao separate the sessions with admin
				$reportBatch->admin_id = $this->_currentAdmin->id;
				$reportBatch->report_ids = ",".implode(",",$report_ids).",";
				$reportBatch->state = 'NEW';
				$reportBatch->campaign_id = $campaignId;
				$reportBatch->save();
				$this->view->batchId = $reportBatch->id;
				//rock selected reports
				$reportModel = new Report();
				$db2 = $reportModel->getAdapter();
				$set = array(
					    'state' => 'LOCKED'
					);
				$where = $db2->quoteInto('id in ('.implode(',',$report_ids).')');
				$rows_affected = $reportModel->update($set,$where);	
			}	
		}else{
			$batch_id = $this->_request->getParam('batch_id');
			$reportBatchModel = new ReportBatch();
			$reportBatch = $reportBatchModel->fetchRow('id  = '.$batch_id);
			$displayedreport_ids = explode(',', substr($reportBatch['report_ids'],1,strlen($reportBatch['report_ids'])-2));
			$campaignId = $reportBatch['campaign_id'];
			$this->view->batchId = $reportBatch['id'];
		}
		
//		Zend_Debug::dump($this->view->batchId);
		if(count($displayedreport_ids) == 0){
			return;
		}		

		//campagin 
		$campaignModel = new Campaign();
		$this->view->campaign = $campaignModel->fetchRow('id = ' .$campaignId);
						
		// show the selected reports
		
		$select = $db->select();
		$select->from('report', 'report.*');
		$select->join('consumer', 'report.consumer_id = consumer.id',array('consumer.email','consumer.login_phone','consumer.name'));
		$select->join('campaign_invitation','campaign_invitation.consumer_id = report.consumer_id and report.campaign_id = campaign_invitation.campaign_id','campaign_invitation.state as cistate');		
		$select->where('report.id in ('.implode(',',$displayedreport_ids).')');
		$select->order('report.create_date desc');
		$this->view->AllReports = $db->fetchAll($select);
		
		// 2011-09-21 bruce.liu show selected reports and admin_name (who approved the report )
		$num=0;
		foreach ($this->view->AllReports as $rep):
			$select_admin=$db->select();
			$select_admin->from('report_batch','report_batch.admin_id');
			$select_admin->join('admin','admin.id = report_batch.admin_id','admin.name');
			$select_admin->where('FIND_IN_SET('.$rep['id'].',report_batch.report_ids) > 0');
			$this->admin_name=$db->fetchAll($select_admin);
			$this->view->AllReports[$num]['name']=$this->admin_name[0]['name'];
			$num++;
		endforeach;
		//get report amount for each member
		$selectReportAmount = $db->select();
		$selectReportAmount->from('report',array('count(*)', 'consumer_id'))
		->group('consumer_id');
		$reportAmounts = $db->fetchAll($selectReportAmount);
		$this->view->reportAmountArray = array();
		foreach($reportAmounts as $reportAmonut):
			$i = $reportAmonut["consumer_id"];
			$this->view->reportAmountArray[$i] = $reportAmonut["count(*)"];
		endforeach;
		$selectReportAmount->where("campaign_id = ?", $campaignId);
		$reportCampaignAmonuts = $db->fetchAll($selectReportAmount);
		$this->view->reportCampaignAmountArray = array();
		foreach($reportCampaignAmonuts as $reportCampaignAmonut):
			$i = $reportCampaignAmonut["consumer_id"];
			$this->view->reportCampaignAmountArray[$i] = $reportCampaignAmonut["count(*)"];
		endforeach;
		//get report point
		$select2 = $db->select();
		$select2->from('reward_point_transaction_record', 'reward_point_transaction_record.*');
		$select2->where('reward_point_transaction_record.transaction_id = 1');
		$AllRecords = $db->fetchAll($select2);
		
		$recordsArray = array();
		foreach($AllRecords as $record):
			$i = $record["id"];
			$recordsArray[$i] = $record["point_amount"];
		endforeach;
		
		$this->view->pointArray = array();
		$this->view->reportArray = array();
		foreach($this->view->AllReports as $report):
			if($report['reward_point_transaction_record_id'] != null && array_key_exists($report['reward_point_transaction_record_id'],$recordsArray)){
				$j = $report["id"];
				$k = $report['reward_point_transaction_record_id'];
				$this->view->pointArray[$j] = $recordsArray[$k];
			}		
		endforeach;
		//get report reply
		$select3 = $db->select();
		$select3->from('reply',array('report_id', 'status'));
		$allReplys = $db->fetchAll($select3);
		$this->view->allReplysArray = array();
		foreach($allReplys as $reply):
			$i = $reply["report_id"];
			$this->view->allReplysArray[$i] = $reply["status"];
		endforeach;
	}
	
	function adminshowreportbatchAction(){
		$this->view->title = 'Reports';
		$this->view->activeTab = 'Reports';
		
		$batchId = $this->_request->getParam('batchId');
		$reportBatchModel = new ReportBatch();
		$reportBatch = $reportBatchModel->find($batchId)->current();
		$report_ids = explode(',',substr($reportBatch->report_ids,1,strlen($reportBatch->report_ids)-2));
		
		$curPage = 1;
		$rowsPerPage = 100;
		if($this->_request->getParam('page'))
        {
        	$curPage = $this->_request->getParam('page');
        }
		$db = Zend_Registry::get('db');
		//get report
		$select = $db->select();
		$select->from('report', 'report.*');
		$select->join('consumer', 'report.consumer_id = consumer.id','consumer.email');
		$select->where('report.id in ('.implode(',',$report_ids).')');
		
		
		$select->order('report.create_date desc');
		$this->view->AllReports = $db->fetchAll($select);
		$this->view->totalReports = count($this->view->AllReports);
		$select->where('report.state != "UNAPPROVED"');
		$this->view->totalApprovedReports = count($db->fetchAll($select));
		//get report amount for each member
		$selectReportAmount = $db->select();
		$selectReportAmount->from('report',array('count(*)', 'consumer_id'))
		->group('consumer_id');
		$reportAmounts = $db->fetchAll($selectReportAmount);
		$this->view->reportAmountArray = array();
		foreach($reportAmounts as $reportAmonut):
			$i = $reportAmonut["consumer_id"];
			$this->view->reportAmountArray[$i] = $reportAmonut["count(*)"];
		endforeach;
		$selectReportAmount->where("campaign_id = ?", $reportBatch->campaign_id);
		$reportCampaignAmonuts = $db->fetchAll($selectReportAmount);
		$this->view->reportCampaignAmountArray = array();
		foreach($reportCampaignAmonuts as $reportCampaignAmonut):
			$i = $reportCampaignAmonut["consumer_id"];
			$this->view->reportCampaignAmountArray[$i] = $reportCampaignAmonut["count(*)"];
		endforeach;
//		Zend_Debug::dump($reportCampaignAmonuts);
		//get report point
		$select2 = $db->select();
		$select2->from('reward_point_transaction_record', 'reward_point_transaction_record.*');
		$select2->where('reward_point_transaction_record.transaction_id = 1');
		$AllRecords = $db->fetchAll($select2);
		
		$recordsArray = array();
		foreach($AllRecords as $record):
			$i = $record["id"];
			$recordsArray[$i] = $record["point_amount"];
		endforeach;
		
		$this->view->pointArray = array();
		$this->view->reportArray = array();
		foreach($this->view->AllReports as $report):
			if($report['reward_point_transaction_record_id'] != null && array_key_exists($report['reward_point_transaction_record_id'],$recordsArray)){
				$j = $report["id"];
				$k = $report['reward_point_transaction_record_id'];
				$this->view->pointArray[$j] = $recordsArray[$k];
			}		
		endforeach;
		//get report reply
		$select3 = $db->select();
		$select3->from('reply',array('report_id', 'status'));
		$allReplys = $db->fetchAll($select3);
		$this->view->allReplysArray = array();
		foreach($allReplys as $reply):
			$i = $reply["report_id"];
			$this->view->allReplysArray[$i] = $reply["status"];
		endforeach;
		//paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($this->view->AllReports));
		$paginator->setCurrentPageNumber($curPage)
		->setItemCountPerPage($rowsPerPage); 
		$this->view->paginator = $paginator; 
		
		Zend_View_Helper_PaginationControl::setDefaultViewPartial('pagination/pagelist.phtml');
		$this->view->NoInitValue = ($curPage-1)*$rowsPerPage+1;
		
		$this->_helper->layout->setLayout("layout_admin");
		

	}
function adminreportbatchreplyAction(){
    	$reportId = $this->_request->getParam('report_id');
    	$this->view->batchId = $this->_request->getParam('batch_id');
    	
    	$reportModel = new Report();
    	$report = $reportModel->find($reportId)->current();
    	$this->view->report_id = $reportId;
		
    	$consumerModel = new Consumer();
    	$this->view->consumer = $consumerModel->find($report['consumer_id'])->current();
    	
    	$campaignModel = new Campaign();
    	$campaign = $campaignModel->find($report['campaign_id'])->current();
    	$this->view->campaign_name = $campaign->name;
    	$this->view->campaign_id = $campaign->id;
    	
    	//get new report
    	$config = Zend_Registry::get('config');
    	$url = $config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']; 
    	
//		$contents = file_get_contents($url); // deprecated by ice, for performance reason
//		$handle = @fopen($url, "r");
//		stream_set_timeout($handle, 0, 500);// 500  ms	
//		$contents = stream_get_contents($handle);
//		$info = stream_get_meta_data($handle);
//		fclose($handle);
		
	    $this->view->url = $url;
	    
		$this->view->title = "Reply Reports";
		$this->view->activeTab = "Reply Reports";
		$this->view->mailForm = new ReplyReportForm();
		$this->view->mailForm->email->setValue($this->view->consumer['email']);
		$db = Zend_Registry::get('db');
		$selectReportSourceAndPoint = $db->select();
		$selectReportSourceAndPoint->from('report',array('source','campaign_id'))
		->joinLeft('reward_point_transaction_record', 'report.reward_point_transaction_record_id = reward_point_transaction_record.id','point_amount')
		->where('report.id = ?', $reportId);
		$reportSourceAndPoint = $db->fetchAll($selectReportSourceAndPoint);
//		Zend_Debug::dump($reportSourceAndPoint);
		if(isset($reportSourceAndPoint) ){
			$this->view->reportSource = $reportSourceAndPoint[0]['source'];
			if($this->view->reportSource == 'sms' || empty($this->view->consumer['email'])){
				$this->view->reportSource = 'sms';
				$this->view->mailForm->email->setLabel($this->view->translate('Phone:'));
				$this->view->mailForm->email->setValue($this->view->consumer['phone']);
				$this->view->mailForm->message->setLabel($this->view->translate('Admin_Reply_Message_Sms_Note'));
				$this->view->mailForm->message->setAttribs(array('rows'=>2,'cols'=>50));
			}
		}
		if(isset($reportSourceAndPoint) && $reportSourceAndPoint[0]['point_amount'] != null){
			$this->view->mailForm->grade->setValue($reportSourceAndPoint[0]['point_amount']);
		}
		
		//tag for report
        $selectTags = $db->select();
        $selectTags->from('tags', array('name','id','sort'))
        ->where("module = 'REPORT' and (campaign_id is null or campaign_id=".$reportSourceAndPoint[0]['campaign_id'].")")
        //->where("module = 'REPORT'")       
        ->order('sort');
        $this->view->tags = $db->fetchAll($selectTags);
        $selectSelectedTags = $db->select();
        $selectSelectedTags->from('report_tag', array('tag_id'))
        ->where('report_id = ?', $reportId);
        $selectedTags = $db->fetchAll($selectSelectedTags);
		$this->view->selectedTagsArray = array ();
		foreach ( $selectedTags as $tag ) {
			$this->view->selectedTagsArray [$tag ['tag_id']] = '1';
		};   
//		Zend_Debug::dump($this->view->selectedTagsArray);
		$replyModel = new Reply();
		$reply = $replyModel->fetchRow('report_id = '.$reportId);
		if($reply != null){
			$this->view->mailForm->message->setValue($reply['content']);
			$this->view->status = $reply['status'];
		}
		$this->view->usetime =$reply['usetime'];
		var_dump($reply['usetime']);
		$this->view->mailForm->subject->setValue($this->view->translate('Admin_Reply_WOM_Report_Subject'));
		$this->_helper->layout->setLayout("layout_admin");
		
		
		//organize tag list
		$tagHash = array();
		foreach ($this->view->tags as $tag){
			$tagHash[$tag['id']] = $tag['name'];
		}
		
    	// get old reports of this campaign
	    $select = $db->select();
		$select->from('report', array('id', 'accesscode','create_date'))
		->where('consumer_id = ?', $this->view->consumer['id'])
		->where('campaign_id = ?', $campaign->id)
		->order('create_date desc');
		$oldreportArray = $db->fetchAll($select);
		
		$this->view->oldreports = array();
		$i = 1;
		foreach($oldreportArray as $oldreport){
			$oldTags = '';
			if($report['accesscode'] != $oldreport["accesscode"]){
				$this->view->oldreports[$oldreport["accesscode"]]['url'] = $config->indicate2->home."/report/showAnswer/accessCode/".$oldreport["accesscode"];
				$reply = $replyModel->fetchRow('report_id = '.$oldreport['id']);
				if($reply['admin_id'] != null){
					$adminModel = new Admin();
					$admin = $adminModel->fetchRow('id = '.$reply['admin_id']);
					$adminname = $admin['name'];
				}else{
					$adminname = '';
				}
				$this->view->oldreports[$oldreport["accesscode"]]['id'] = $oldreport['id'];
				$this->view->oldreports[$oldreport["accesscode"]]['create_date'] = $oldreport['create_date'];
				$this->view->oldreports[$oldreport["accesscode"]]['adminname'] = $adminname;
				$this->view->oldreports[$oldreport["accesscode"]]['replydate'] = $reply['date'];
				$this->view->oldreports[$oldreport["accesscode"]]['replycontent'] = $reply['content'];
				//tag
				$oldreportTagSelect = $db->select();
				$oldreportTagSelect ->from('report_tag','tag_id')
				->where('report_tag.report_id = ?',$oldreport['id']);
				$oldreportTag = $db->fetchAll($oldreportTagSelect);
				foreach($oldreportTag as $tag){
					$oldTags .= $this->view->translate('Report_Tag_'.$tagHash[$tag['tag_id']])." ";
				}
				$this->view->oldreports[$oldreport["accesscode"]]['tag'] = $oldTags;
			}
		}
		// get old reports for other campaigns
	    $select = $db->select();
		$select->from('report', array('id', 'accesscode','create_date'))
		->where('consumer_id = ?', $this->view->consumer['id'])
		->where('campaign_id != ?', $campaign->id)
		->order('create_date desc');
		$oldreportArray = $db->fetchAll($select);
		
		
		$this->view->otheroldreports = array();
		$i = 1;
		foreach($oldreportArray as $oldreport){
			$oldTags = '';
			if($report['accesscode'] != $oldreport["accesscode"]){
				$this->view->otheroldreports[$oldreport["accesscode"]]['url'] = $config->indicate2->home."/report/showAnswer/accessCode/".$oldreport["accesscode"];
				$reply = $replyModel->fetchRow('report_id = '.$oldreport['id']);
				if($reply['admin_id'] != null){
					$adminModel = new Admin();
					$admin = $adminModel->fetchRow('id = '.$reply['admin_id']);
					$adminname = $admin['name'];
				}else{
					$adminname = '';
				}
				$this->view->otheroldreports[$oldreport["accesscode"]]['id'] = $oldreport['id'];
				$this->view->otheroldreports[$oldreport["accesscode"]]['create_date'] = $oldreport['create_date'];
				$this->view->otheroldreports[$oldreport["accesscode"]]['adminname'] = $adminname;
				$this->view->otheroldreports[$oldreport["accesscode"]]['replydate'] = $reply['date'];
				$this->view->otheroldreports[$oldreport["accesscode"]]['replycontent'] = $reply['content'];
				//tag
				$oldreportTagSelect = $db->select();
				$oldreportTagSelect ->from('report_tag','tag_id')
				->where('report_tag.report_id = ?',$oldreport['id']);
				$oldreportTag = $db->fetchAll($oldreportTagSelect);
				foreach($oldreportTag as $tag){
					$oldTags .= $this->view->translate('Report_Tag_'.$tagHash[$tag['tag_id']])." ";
				}
				$this->view->otheroldreports[$oldreport["accesscode"]]['tag'] = $oldTags;
			}
		}
		
		$reportImages = new ReportImages();
		$reportImagesData = $reportImages->fetchAll('report='.$reportId.' and consumer='.$report['consumer_id']);
		$this->view->reportImages = $reportImagesData;
	}

	function adminreportbatchreplysendAction(){
		$this->_helper->layout->setLayout("layout_admin");
		ini_set('display_errors', 1);
		$frontController = Zend_Controller_Front::getInstance();
		$frontController->throwExceptions(true);
		if ($this->_request->isPost()) {
				$form = new ReplyReportForm();
				$formData = $this->_request->getPost();
				if ($form->isValid($formData)) {
					//print_r($formData);die;
					$reportSource = $this->_request->getParam('report_source');
					// sms report:
					if($reportSource == 'sms'){
						$db = Zend_Registry::get('db');
						$select = $db->select();
						$select->from('consumer', '*');
						$select->where('id = ?',$this->_request->getParam('consumer_id'));
						$consumer = $db->fetchAll($select);
						// 1.send reply
						$msmStr = $form->getValue('message');
						$len = strlen($msmStr);
						for($i=0,$msmStrLen = 0; $i<$len; $i++,$msmStrLen++){
							if(ord($msmStr[$i])>=128)
							{
							$i = $i + 2;
							}
						}
						if($msmStrLen > 70){
							$this->view->batchId = $formData['batch_id'];
							$this->view->showMessage = 'Reply fail: The sms should be short then 70 characters.';
							return;
						}
						include_once 'sms.inc.php';
						$newclient=new SMS();
						$apitype = 0;
						$msg = iconv("UTF-8","GB2312",$form->getValue('message'));
						$respxml=$newclient->sendSMS($form->getValue('email'), $msg, date("Y-m-d H:i:s"), $apitype);
						// 2.save reply 		
						$replyModel = new Reply();
						$reply = $replyModel->fetchRow('report_id = '.$this->_request->getParam('report_id'));
						//check reply condition!
						if($reply != null && $reply->status == 'SENT'){
							$this->view->showMessage = "Reply fail: the reply has been sent!";
							return;
						}
						if($reply == null){
							$replyModel = new Reply();
							$reply = $replyModel->createRow();		
						}
						$currentTime = date("Y-m-d H:i:s");		
						$reply->date = $currentTime;
						$reply->subject = $form->getValue('subject');
						$reply->content = $form->getValue('message');
						$reply->from = $config->smtp->report->mail->username;
	
						$reply->campaign_id = $formData['campaign_id'];
						$reply->report_id = $formData['report_id'];
						$reply->to = $form->getValue('email');
						$reply->status = 'SENT';
						//2011-04-08 ham.bao separate the sessions with admin
						$reply->admin_id = $this->_currentAdmin->id;
						//$reply->usetime =$formData['usetime'];
						$reply->save();
						
						// 3.grade
						$report_id = (int)$this->_request->getParam('report_id');
						$this->saveReportReward($report_id,$form->getValue('grade'));
						
						// 4.update notes for report
						//$this->saveTags($report_id,$formData ['report_id']);
						$this->saveTags($report_id,$formData ['note']);
						
						$this->view->batchId = $formData['batch_id'];
						$this->updateBatchTotaltime($formData['batch_id'],$addtive);
						$this->view->showMessage = $this->view->translate('Admin_Reply_the_report_successfully');
						return;
					}
					
					
					// email report:
					//1. config
					$config = Zend_Registry::get('config');
					/* 
					$smtpSender = new Zend_Mail_Transport_Smtp(
								$config->smtp->report->mail->server,
								array(
									'username'=> $config->smtp->report->mail->username,
									'password'=> $config->smtp->report->mail->password,
									'auth'=> $config->smtp->report->mail->auth,
									'ssl' => $config->smtp->report->mail->ssl,
			               			'port' => $config->smtp->report->mail->port));
					Zend_Mail::setDefaultTransport($smtpSender);
					$mail = new Zend_Mail('utf-8');
					*/
					$db = Zend_Registry::get('db');
					$select = $db->select();
					$select->from('consumer', '*');
					$select->where('email = ?',$form->getValue('email'));
					$consumer = $db->fetchAll($select);
					if($consumer[0] != null){
						/*
						//2.get "Your story" from report
						$reportId = $formData['report_id'];
				    	$reportModel = new Report();
				    	$report = $reportModel->find($reportId)->current();
				    	$config = Zend_Registry::get('config');
				    	$url_zh = $config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/645";
						$url_en = $config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/707";
						$contents = file_get_contents($url_zh).file_get_contents($url_en);
				    	$contents = trim($contents);
						$contents = preg_replace('/\s(?=\s)/', '', $contents);
						$contents = preg_replace('/[\n\r\t]/', ' ', $contents);
						$contents = preg_replace('/&nbsp;/', '', $contents);	
						preg_match_all ("|<div class.*answer_content.*>(.*)</[^>]+>|U", $contents, $out, PREG_PATTERN_ORDER);
						
					    //3.create email and send
						$emailSubject = $this->view->translate('Admin_Reply_WOM_Report_Subject');
						if($consumer[0]['language_pref'] != null && $consumer[0]['language_pref'] == 'en'){
							$emailBody = $this->view->translate('Admin_Reply_WOM_Report_Body_en');
						}else{
							$emailBody = $this->view->translate('Admin_Reply_WOM_Report_Body_zh');
						}
						
						$stringChange = array(
							'?USERNAME?' => $consumer[0]['name'],
							'?YOURSTORY?' => $out[1][0],
							'?MYRESPONSE?' => $form->getValue('message'));
						$emailBody = strtr($emailBody,$stringChange);
							
						$langNamespace = new Zend_Session_Namespace('Lang');
							if($langNamespace->lang == 'en' || $langNamespace->lang == 'EN'){
								$mail->setSubject($emailSubject);
							}else{
								$mail->setSubject("=?UTF-8?B?".base64_encode($emailSubject)."?=");
							}
						$mail->setBodyText($emailBody);
						$mail->addTo($form->getValue('email'));
						$mail->setFrom($config->smtp->report->mail->username, $this->view->translate('Wildfire'));
						//send!
						$mail->send();
						*/					
						//4.save reply 		
						$replyModel = new Reply();
						$reply = $replyModel->fetchRow('report_id = '.$this->_request->getParam('report_id'));
						//check reply condition!
						if($reply != null && $reply->status == 'SENT'){
							$this->view->showMessage = "Reply fail: the reply has been sent!";
							return;
						}
						if($reply == null){
							$replyModel = new Reply();
							$reply = $replyModel->createRow();		
						}
						$currentTime = date("Y-m-d H:i:s");		
						$reply->date = $currentTime;
						$reply->subject = $form->getValue('subject');
						$reply->content = $form->getValue('message');
						$reply->from = $config->smtp->report->mail->username;
	
						$reply->campaign_id = $formData['campaign_id'];
						$reply->report_id = $formData['report_id'];
						$reply->to = $form->getValue('email');
						$reply->status = 'SENT';
						//2011-04-08 ham.bao separate the sessions with admin
						$reply->admin_id = $this->_currentAdmin->id;
						$reply->usetime = $form->getValue('usetime');
						$reply->save();
						
						// 5.grade
						$report_id = (int)$this->_request->getParam('report_id');
						$this->saveReportReward($report_id,$form->getValue('grade'));
						
						// 6.update notes for report
						$this->saveTags($report_id,$formData ['note']);
						
						// 7.update batch reply time
						$addtive = $addtive = $formData['usetime'] - $reply->usetime;
						$this->updateBatchTotaltime($formData['batch_id'],$addtive);
						
						$this->view->showMessage = $this->view->translate('Admin_Reply_the_report_successfully');						//2.get "Your story" from report
						$reportId = $formData['report_id'];
				    	$reportModel = new Report();
				    	$report = $reportModel->find($reportId)->current();
				    	$config = Zend_Registry::get('config');
				    	$url_zh = $config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/645";
						$url_en = $config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/707";
						$url_mission =$config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/3153";
						$url_mission1 =$config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/2769";
						$url_mission2 =$config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/3166";
						$url_mission3 =$config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/3171";
						$url_mission4 =$config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/2293";//童装
						$url_mission5 =$config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/3239";//电影大赏
						$contents = file_get_contents($url_zh).file_get_contents($url_en).file_get_contents($url_mission).file_get_contents($url_mission1).file_get_contents($url_mission2).file_get_contents($url_mission3).file_get_contents($url_mission4).file_get_contents($url_mission5);
				    	$contents = trim($contents);
						$contents = preg_replace('/\s(?=\s)/', '', $contents);
						$contents = preg_replace('/[\n\r\t]/', ' ', $contents);
						$contents = preg_replace('/&nbsp;/', '', $contents);	
						preg_match_all ("|<div class.*answer_content.*>(.*)</[^>]+>|U", $contents, $out, PREG_PATTERN_ORDER);
						
					    //3.create email and send
						$smtpSender = new Zend_Mail_Transport_Smtp(
									$config->smtp->report->mail->server,
									array(
										'username'=> $config->smtp->report->mail->username,
										'password'=> $config->smtp->report->mail->password,
										'auth'=> $config->smtp->report->mail->auth,
										'ssl' => $config->smtp->report->mail->ssl,
				               			'port' => $config->smtp->report->mail->port));
						Zend_Mail::setDefaultTransport($smtpSender);
						$mail = new Zend_Mail('utf-8');
						
						$emailSubject = $this->view->translate('Admin_Reply_WOM_Report_Subject');
						if($consumer[0]['language_pref'] != null && $consumer[0]['language_pref'] == 'en'){
							$emailBody = $this->view->translate('Admin_Reply_WOM_Report_Body_en');
						}else{
							$emailBody = $this->view->translate('Admin_Reply_WOM_Report_Body_zh');
						}
						
						$stringChange = array(
							'?USERNAME?' => $consumer[0]['name'],
							'?YOURSTORY?' => $out[1][0],
							'?MYRESPONSE?' => $form->getValue('message'));
						$emailBody = strtr($emailBody,$stringChange);
							
						$langNamespace = new Zend_Session_Namespace('Lang');
							if($langNamespace->lang == 'en' || $langNamespace->lang == 'EN'){
								$mail->setSubject($emailSubject);
							}else{
								$mail->setSubject("=?UTF-8?B?".base64_encode($emailSubject)."?=");
							}
						$mail->setBodyText($emailBody);
						$mail->addTo($form->getValue('email'));
						$mail->setFrom($config->smtp->report->mail->username, $this->view->translate('Wildfire'));
						//send!
						$mail->send();
						
						$this->view->batchId = $formData['batch_id'];
						
						$this->view->showMessage = $this->view->translate('Admin_Reply_the_report_successfully');
					}else{
						$this->view->showMessage = "Reply fail: this email doesn't exist in DB!";
					}			
				}else{
					$this->view->showMessage = 'Reply fail: please input the right data!';
				}
		}else{
			$this->view->showMessage = "err!";
		}
		
	}
	
	public function adminurlreportAction(){
	$this->view->title = 'Reports';
		$this->view->activeTab = 'Reports';
		$this->_helper->layout->setLayout("layout_admin");
		//post
		if($this->_request->isPost()){
			
		}else{
			$this->view->campaign_id = $this->_request->getParam('id');
		
			$curPage = 1;
			$rowsPerPage = 50;
			if($this->_request->getParam('page'))
	        {
	        	$curPage = $this->_request->getParam('page');
	        }
			$db = Zend_Registry::get('db');
			$select = $db->select();
			$select->from('url_report',array('id', 'url', 'state', 'create_date', 'consumer_id'))
			->join('consumer', 'consumer.id = url_report.consumer_id', array('email', 'name', 'recipients_name', 'language_pref'))
			->joinLeft('url_report_reply', 'url_report_reply.url_report_id = url_report.id', 'content')
			->where('campaign_id = ?', $this->view->campaign_id)
			->where('consumer.pest is null')
			->order('create_date desc');
			$this->view->urlReports = $db->fetchAll($select);
			//
			$selectDuplicatedUrlReport = $db->select();
			$selectDuplicatedUrlReport->from('url_report', 'url')
			->group('url')
			->having('count(*) > 1');
			$duplicatedUrlReport = $db->fetchAll($selectDuplicatedUrlReport);
			$this->view->duplicatedUrlArray = array();
			foreach($duplicatedUrlReport as $urlReport){
				$this->view->duplicatedUrlArray[$urlReport['url']] = '0';
			}
			//paging
            $this->view->controller = $this->_request->getControllerName();
            $this->view->action = $this->_request->getActionName();
			$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($this->view->urlReports));
			$paginator->setCurrentPageNumber($curPage)
			->setItemCountPerPage($rowsPerPage); 
			$this->view->paginator = $paginator; 
//			Zend_Debug::dump($this->view->duplicatedUrlArray);
			Zend_View_Helper_PaginationControl::setDefaultViewPartial('pagination/pagelist.phtml');
			$this->view->NoInitValue = ($curPage-1)*$rowsPerPage+1;
		}
	}
	function adminsaveurlreportstateAction(){
		
	$type = $this->_request->getParam('type');
		$db = Zend_Registry::get('db');
		if($type == 'reportState'){
			$idStr = $this->_request->getParam('reportids');
			$idStrArray = explode(',',$idStr);
			$reportIdArray = array();
			$i = 0;
			
			$config = Zend_Registry::get('config');
			$smtpSender = new Zend_Mail_Transport_Smtp(
						$config->smtp->report->mail->server,
						array(
							'username'=> $config->smtp->report->mail->username,
							'password'=> $config->smtp->report->mail->password,
							'auth'=> $config->smtp->report->mail->auth,
							'ssl' => $config->smtp->report->mail->ssl,
	               			'port' => $config->smtp->report->mail->port));
//			$smtpSender = new Zend_Mail_Transport_Smtp(
//						'smtp.163.com',array(
//						'username'=>'yun_simon@163.com',
//						'password'=>'19990402',
//						'auth'=>'login'));
			Zend_Mail::setDefaultTransport($smtpSender);
			
			foreach($idStrArray as $idAndState){
				if($idAndState == ''){
					continue;
				}
				$idAndStateArray = explode('@',$idAndState);
				if($idAndStateArray[1] == 'NEW'){
					continue;
				}
				if($idAndStateArray[1] == 'APPROVED'){
					$urlreportModel = new UrlReport();
					$row = $urlreportModel->fetchRow('id = '.$idAndStateArray[0]);
					if($row->state != 'NEW'){
						continue;
					}
					$row->state = $idAndStateArray[1];
					if($row->reward_point_transaction_record_id == null || $row->reward_point_transaction_record_id == ''){
						$rewardModel = new RewardPointTransactionRecord();
						$reward = $rewardModel->createRow();
						$reward->consumer_id = $idAndStateArray[2];
						$reward->date = date("Y-m-d H:i:s");
						$reward->transaction_id = 8;
						$reward->point_amount = 300;
						$row->reward_point_transaction_record_id = $reward->save();
					}else{
						$rewardModel = new RewardPointTransactionRecord();
						$reward = $rewardModel->fetchRow('id = '.$row->reward_point_transaction_record_id);
						if($reward != null){
							$reward->date = date("Y-m-d H:i:s");
							$reward->point_amount = 300;
							$reward->save();
						}
					}
					$row->save();
				//2011-05-13 change the rank of consumer 
				$rankModel = new Rank();
				$rankModel->changeConsumerRank($idAndStateArray[1]);
					
				}
				if($idAndStateArray[1] == 'UNAPPROVED'){
					$urlreportModel = new UrlReport();
					$row = $urlreportModel->fetchRow('id = '.$idAndStateArray[0]);
					if($row == null){
						continue;
					}
					if($row->reward_point_transaction_record_id != null && $row->reward_point_transaction_record_id != ''){
						$rewardModel = new RewardPointTransactionRecord();
						$reward = $rewardModel->fetchRow('id = '.$row->reward_point_transaction_record_id);
						if($reward != null){
							$db2 = $rewardModel->getAdapter();
							$where = $db2->quoteInto('id = ?', $row->reward_point_transaction_record_id);			
							$rows_affected = $rewardModel->delete($where);
						}
					}
					$db2 = $urlreportModel->getAdapter();
					$where = $db2->quoteInto('id = ?', $idAndStateArray[0]);			
					$rows_affected = $urlreportModel->delete($where);
				}
				$consumerModel = new Consumer();
				$consumer = $consumerModel->fetchRow('id = '.$idAndStateArray[2]);
				
				
				//send mail...
				if($consumer->email == ''){
					continue;
				}
				$mail = new Zend_Mail('utf-8');
				if($consumer->language_pref != null && $consumer->language_pref == 'en'){
					$emailSubject = $this->view->translate('Admin_Reply_WOM_URLReport_Subject_en');
					$emailBody = $this->view->translate('Admin_Reply_WOM_URLReport_Body_en');
				}else{
					$emailSubject = $this->view->translate('Admin_Reply_WOM_URLReport_Subject_zh');
					$emailBody = $this->view->translate('Admin_Reply_WOM_URLReport_Body_zh');
				}
				$stringChange = array(
					'?USERNAME?' => $consumer->name,
					'?YOURSTORY?' => $row->url,
					'?MYRESPONSE?' => $idAndStateArray[3]);
				$emailBody = strtr($emailBody,$stringChange);
					
				$langNamespace = new Zend_Session_Namespace('Lang');
				if($langNamespace->lang == 'en' || $langNamespace->lang == 'EN'){
					$mail->setSubject($emailSubject);
				}else{
					$mail->setSubject("=?UTF-8?B?".base64_encode($emailSubject)."?=");
				}
				$mail->setBodyText($emailBody);
				$mail->addTo($consumer->email);
				$mail->setFrom($config->smtp->report->mail->username, $this->view->translate('Wildfire'));
//				$mail->setFrom('yun_simon@163.com',$this->view->translate('Wildfire'));
				$mail->send();
				
				// save email
				$urlreportreplyModel = new UrlReportReply();
				$urlreportreply = $urlreportreplyModel->createRow();
				$urlreportreply->date = date("Y-m-d H:i:s");
				$urlreportreply->subject = $emailSubject;
				$urlreportreply->content = $idAndStateArray[3];
				$urlreportreply->from = $config->smtp->report->mail->username;
				$urlreportreply->to = $consumer->email;
				$urlreportreply->url_report_id = $row->id;
				//2011-04-08 ham.bao separate the sessions with admin
				$urlreportreply->admin_id = $this->_currentAdmin->id;
				$urlreportreply->save();
			}	
		}
	}
	public function admindownloadindexAction()
	{
		$this->view->title = 'Reports';
		$this->view->activeTab = 'Reports';
		$this->_helper->layout->setLayout("layout_admin");
		$profileSurveyModel = new ProfileSurvey();
		$this->view->profileSurvey = $profileSurveyModel->fetchAll();
		$campaignModel = new Campaign();
		$this->view->campaigns = $campaignModel->fetchAll();
			
	}
	
	public function admindownloadreportAction(){
		ini_set('display_errors', 1);
		$frontController = Zend_Controller_Front::getInstance();
		$frontController->throwExceptions(true);
		$this->_helper->layout->disableLayout();
		$reportInforArray = array();
		//post
		if($this->_request->isPost()){
			$formData = $this->_request->getPost();
			$accessCodeList = array();
			// get access code from post
			if(isset($formData['accessCode'])){
//				Zend_Debug::dump($formData['accessCode']);
				$accessCodeArray = preg_split('/[;\s]+[\n\r\t]*/', trim($formData['accessCode']));
				$accessCodeString = '';
				foreach($accessCodeArray as $accessCode){
					$accessCodeString .= "'".$accessCode."',";
				}
				// get accesscode from campaign
				$db = Zend_Registry::get('db');
				$selectAccessCode = $db->select();
				$selectAccessCode->from('report', array('consumer_id','accesscode', 'create_date', 'source'))
				->joinLeft('reward_point_transaction_record', 'report.reward_point_transaction_record_id = reward_point_transaction_record.id', 'point_amount')
				->join('consumer', 'consumer.id = report.consumer_id', array('email,login_phone,recipients_name'))
				->where('report.accesscode in ('.substr($accessCodeString,0,strlen($accessCodeString)-1).")")
				->where("report.state = 'APPROVED'")
				->limit(0);
				
				$accessCodeArray = $db->fetchAll($selectAccessCode);
				foreach($accessCodeArray as $accessCode):
					array_push($accessCodeList,$accessCode['accesscode']);
					$reportInforArray[$accessCode['accesscode']]['consumer_id'] = $accessCode['consumer_id'];
					$reportInforArray[$accessCode['accesscode']]['email'] = $accessCode['email'];
					$reportInforArray[$accessCode['accesscode']]['login_phone'] = $accessCode['login_phone'];
					$reportInforArray[$accessCode['accesscode']]['recipients_name'] = $accessCode['recipients_name'];
					$reportInforArray[$accessCode['accesscode']]['createdate'] = $accessCode['create_date'];
					$reportInforArray[$accessCode['accesscode']]['source'] = $accessCode['source'];
					$reportInforArray[$accessCode['accesscode']]['point'] = $accessCode['point_amount'];
					$reportInforArray[$accessCode['accesscode']]['reply'] = '';
				endforeach;
				// get reply for report
				$selectReportReply = $db->select();
				$selectReportReply->from('report', 'accesscode')
				->joinLeft('reply', 'report.id = reply.report_id', 'content')
				->where('report.accesscode in ('.substr($accessCodeString,0,strlen($accessCodeString)-1).")")
				->limit(0);
				$reportReplyArray = $db->fetchAll($selectReportReply);
				foreach($reportReplyArray as $reply):
					$reportInforArray[$reply['accesscode']]['reply'] = $reply['content'];
				endforeach;
				// get tag for report
				$selectAllTag = $db->select();
				$selectAllTag->from('tags', array('id', 'name'))
				->where("tags.module ='REPORT'");
				$allTagArray = $db->fetchAll($selectAllTag);
				$selectReportTag = $db->select();
				
				$selectReportTag->from('report', 'accesscode')
				->join('report_tag', 'report.id = report_tag.report_id', null)
				->join('tags', 'tags.id = report_tag.tag_id', 'name')
				->where('report.accesscode in ('.substr($accessCodeString,0,strlen($accessCodeString)-1).")")
				->limit(0);
				$reportTag = $db->fetchAll($selectReportTag);
				$reportTagArray = array();
				foreach($reportTag as $tag):
					$reportTagArray[$tag['accesscode']][$tag['name']] = 1;
				endforeach;
				// get reports from ws
				$indicate2Connect = new Indicate2_Connect();
				$response = $indicate2Connect->getAnswerSetForAccessCode($accessCodeList);
			}else{
				// get survey_id for campaign
				$campaignModel = new Campaign();
				$campaign = $campaignModel->fetchRow('id = '.$formData['campaign_id']);
				switch ($formData['submittype']){
					case 'pre_campaign':
						$survey_id = $campaign['pre_campaign_survey'];
						if($formData['campaign_language'] == 'en'){
							$survey_id = $campaign['pre_campaign_survey_en'];
						}
						$accessCodeList = null;
						break;
					case 'post_campaign':
						$survey_id = $campaign['post_campaign_survey'];
						if($formData['campaign_language'] == 'en'){
							$survey_id = $campaign['post_campaign_survey_en'];
						}
						$accessCodeList = null;
						break;
					// get reports
					default:
						$survey_id = $campaign['i2_survey_id'];
						if($formData['campaign_language'] == 'en'){
							$survey_id = $campaign['i2_survey_id_en'];
						}
						// get accesscode from campaign
						$db = Zend_Registry::get('db');
						$selectAccessCode = $db->select();
						$selectAccessCode->from('report', array('consumer_id','accesscode', 'create_date', 'source'))
						->joinLeft('reward_point_transaction_record', 'report.reward_point_transaction_record_id = reward_point_transaction_record.id', 'point_amount')
						->join('consumer', 'consumer.id = report.consumer_id', array('email','login_phone','recipients_name'))
						->where('report.campaign_id = ?',$formData['campaign_id'])
						->where("report.state = 'APPROVED'")
						->limit(0);
						
						$accessCodeArray = $db->fetchAll($selectAccessCode);
						foreach($accessCodeArray as $accessCode):
							array_push($accessCodeList,$accessCode['accesscode']);
							$reportInforArray[$accessCode['accesscode']]['consumer_id'] = $accessCode['consumer_id'];
							$reportInforArray[$accessCode['accesscode']]['email'] = $accessCode['email'];
							$reportInforArray[$accessCode['accesscode']]['login_phone'] = $accessCode['login_phone'];
							$reportInforArray[$accessCode['accesscode']]['recipients_name'] = $accessCode['recipients_name'];
							$reportInforArray[$accessCode['accesscode']]['createdate'] = $accessCode['create_date'];
							$reportInforArray[$accessCode['accesscode']]['source'] = $accessCode['source'];
							$reportInforArray[$accessCode['accesscode']]['point'] = $accessCode['point_amount'];
							$reportInforArray[$accessCode['accesscode']]['reply'] = '';
						endforeach;
						// get reply for report
						$selectReportReply = $db->select();
						$selectReportReply->from('report', 'accesscode')
						->joinLeft('reply', 'report.id = reply.report_id', 'content')
						->where('reply.campaign_id = ?',$formData['campaign_id'])
						->limit(0);
						$reportReplyArray = $db->fetchAll($selectReportReply);
						foreach($reportReplyArray as $reply):
							$reportInforArray[$reply['accesscode']]['reply'] = $reply['content'];
						endforeach;
						// get tag for report
						$selectAllTag = $db->select();
						$selectAllTag->from('tags', array('id', 'name'))
						->where("tags.module ='REPORT'")
						->where('tags.campaign_id is null or tags.campaign_id ='.$formData['campaign_id']);
						$allTagArray = $db->fetchAll($selectAllTag);
						$selectReportTag = $db->select();
						
						$selectReportTag->from('report', 'accesscode')
						->join('report_tag', 'report.id = report_tag.report_id', null)
						->join('tags', 'tags.id = report_tag.tag_id', 'name')
						->where('report.campaign_id = ?',$formData['campaign_id'])
						->limit(0);
						$reportTag = $db->fetchAll($selectReportTag);
						$reportTagArray = array();
						foreach($reportTag as $tag):
							$reportTagArray[$tag['accesscode']][$tag['name']] = 1;
						endforeach;
						break;
				}
				// get reports from ws
				$indicate2Connect = new Indicate2_Connect();
				$response = $indicate2Connect->getAnswerSetForSurvey($survey_id, $accessCodeList, null, 0);
			}
			$this->view->reportExtraInfoArray = $reportInforArray;
			$this->view->surveyQuestionArray = $response->QuestionType;
			$this->view->surveyArray = $response->AnswerSetType;
			//Zend_Debug::dump($response);
			//die;

			// create phpexcel obj.
			require_once 'PHPExcel.php';
			require_once 'PHPExcel/IOFactory.php';
			require_once 'PHPExcel/Writer/Excel5.php';
			$objPHPExcel = new PHPExcel();
			$objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
									 ->setLastModifiedBy("Maarten Balliauw")
									 ->setTitle("Office 2007 XLSX Test Document")
									 ->setSubject("Office 2007 XLSX Test Document")
									 ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
									 ->setKeywords("office 2007 openxml php")
									 ->setCategory("Test result file");
			$styleThinBrownBorderOutline = array(
				'borders' => array(
					'outline' => array(
						'style' => PHPExcel_Style_Border::BORDER_THICK,
						'color' => array('argb' => 'FFB2A1C7'),
					),
				),
			);
			$objPHPExcel->setActiveSheetIndex(0);
			$objActSheet = $objPHPExcel->getActiveSheet();
			// create an excel column name Array: from A - DZ, you can enlarge this array if you need
			$baseColumnNameArray = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
			$columnNameArray = array();
			$i=0;
			while($i < 26*9){
				if($i < 26){
					array_push($columnNameArray,$baseColumnNameArray[$i]);
				}else{
					array_push($columnNameArray,$baseColumnNameArray[$i/26-1].$baseColumnNameArray[$i % 26]);
				}
				$i ++;
			}
			$columnNumber = 0;
			$tag = array();
			$i = -1;
			// print excel file
			// print line 1: mainly include Accesscode,questions...
			$objActSheet->setCellValue($columnNameArray[$columnNumber]."1", "AccessCode");
			$objActSheet->getStyle($columnNameArray[$columnNumber]."1")->applyFromArray($styleThinBrownBorderOutline);
			$objActSheet->getColumnDimension($columnNameArray[$columnNumber++])->setWidth(20);
			foreach($this->view->surveyQuestionArray as $surveyQuestion):
				$mergeStart = $columnNameArray[$columnNumber];
				$objActSheet->setCellValue($columnNameArray[$columnNumber++]."1", $surveyQuestion->QuestionText);
				
				if(isset($surveyQuestion->SelectionQuestionOptionType) && is_array($surveyQuestion->SelectionQuestionOptionType) && !empty($surveyQuestion->SelectionQuestionOptionType)){
					for($temp = 0; $temp < count($surveyQuestion->SelectionQuestionOptionType)-1; $temp++){
						$objActSheet->setCellValue($columnNameArray[$columnNumber++]."1", " ");
					}
					$mergeEnd = $columnNameArray[$columnNumber-1];
					$objActSheet->mergeCells($mergeStart.'1:'.$mergeEnd.'1');
					$objActSheet->getStyle($mergeStart.'1:'.$mergeEnd.'1')->applyFromArray($styleThinBrownBorderOutline);
				}else{
					$objActSheet->getStyle($mergeStart."1")->applyFromArray($styleThinBrownBorderOutline);
					$objActSheet->getColumnDimension($mergeStart)->setWidth(50);
				}
			endforeach;
			// print line 2: mainly include accesscode,options of questions,user info,tag names...
			$columnNumber = 0;
			$textQuestionArray = array();
			$objActSheet->setCellValue($columnNameArray[$columnNumber++]."2", "AccessCode");
			foreach($this->view->surveyQuestionArray as $surveyQuestion):
				if(isset($surveyQuestion->SelectionQuestionOptionType) && is_array($surveyQuestion->SelectionQuestionOptionType) && !empty($surveyQuestion->SelectionQuestionOptionType)){
					foreach($surveyQuestion->SelectionQuestionOptionType as $selectionQuestionOptionTypeTemp){
						$objActSheet->setCellValue($columnNameArray[$columnNumber++]."2", $selectionQuestionOptionTypeTemp->OptionText);
						$tag[++$i] = $selectionQuestionOptionTypeTemp->OptionId;
					}
				}else{
					$objActSheet->setCellValue($columnNameArray[$columnNumber++]."2", $surveyQuestion->QuestionText);
					$tag[++$i] = $surveyQuestion->QuestionId;
					$textQuestionArray[$surveyQuestion->QuestionId] = 1;
				}
			endforeach;
			if($formData['submittype'] == 'report'){
				$objActSheet->setCellValue($columnNameArray[$columnNumber++]."2", "ConsumerId");
				$objActSheet->setCellValue($columnNameArray[$columnNumber++]."2", "ConsumerEmail");
				$objActSheet->setCellValue($columnNameArray[$columnNumber++]."2", "login_phone");
				$objActSheet->setCellValue($columnNameArray[$columnNumber++]."2", "recipients_name");
				$objActSheet->setCellValue($columnNameArray[$columnNumber++]."2", "Create_date");
				$objActSheet->setCellValue($columnNameArray[$columnNumber++]."2", "Source");
				$objActSheet->setCellValue($columnNameArray[$columnNumber++]."2", "Point");
				$objActSheet->setCellValue($columnNameArray[$columnNumber]."2", "Reply");
				$objActSheet->getColumnDimension($columnNameArray[$columnNumber++])->setWidth(50);
				$objActSheet->setCellValue($columnNameArray[$columnNumber++]."2", " ");
				foreach($allTagArray as $tagName){
					$objActSheet->setCellValue($columnNameArray[$columnNumber++]."2", $tagName['name']);
				}
			}
			// print line 3~X: include answers from ws, user info, tag values...
			$lineNumber = 3;
			if(isset($this->view->surveyArray) && is_array($this->view->surveyArray) && !empty($this->view->surveyArray)){
				// if get more than one surveys from ws
				foreach($this->view->surveyArray as $surveys):
					$columnNumber = 0;
					$temp = array();
					$objActSheet->setCellValue($columnNameArray[$columnNumber++].$lineNumber, $surveys->AccessCode);
					// more than one answer in the survey
					if(isset($surveys->AnswerType) && is_array($surveys->AnswerType) && !empty($surveys->AnswerType)){
						foreach($surveys->AnswerType as $question):
							if(isset($question->AnswerText) && is_array($question->AnswerText) && !empty($question->AnswerText)){
								foreach($question->AnswerText as $text):
									$temp[$text] =  "1";
								endforeach;
							}else{
								$decodeValue = base64_decode($question->AnswerText);
								if(array_key_exists($question->QuestionId, $textQuestionArray)){
									if(isset($question->AnswerText)){
										$temp[$question->QuestionId] = $decodeValue; 
									}
								}else{
									$temp[$decodeValue] = "1";
								}
							}
						endforeach;
					}else{
					// only one answer in the survey
						if(isset($surveys->AnswerType->AnswerText) && is_array($surveys->AnswerType->AnswerText) && !empty($surveys->AnswerType->AnswerText)){
							foreach($surveys->AnswerType->AnswerText as $text):
								$temp[$text] =  "1";
							endforeach;
						}else{
							$decodeValue = base64_decode($surveys->AnswerType->AnswerText);
							if(array_key_exists($surveys->AnswerType->QuestionId, $textQuestionArray)){
								$temp[$surveys->AnswerType->QuestionId] = isset($surveys->AnswerType->AnswerText)? $decodeValue:""; 
							}else{
								$temp[$decodeValue] = "1";
							}
						}
					}
                    

					// print answers from ws
					for($i = 0; $i<count($tag); $i++){
						if(isset($temp[$tag[$i]])){ 
                            if($temp[$tag[$i]]=="1" ||$temp[$tag[$i]]=="0" )
                                $objActSheet->setCellValue($columnNameArray[$columnNumber].$lineNumber, $temp[$tag[$i]]);
                            else
                                $objActSheet->setCellValue($columnNameArray[$columnNumber].$lineNumber, "\"".$temp[$tag[$i]]."\"");
                            //Zend_Debug::dump($i); 
                            //Zend_Debug::dump($temp[$tag[$i]]);
                            //Zend_Debug::dump("\"".$temp[$tag[$i]]."\"");
						}
						$columnNumber++;
					}
                    //die;
					// print user extra info
					foreach($this->view->reportExtraInfoArray[$surveys->AccessCode] as $reportExtraInfo):
						if(isset($reportExtraInfo)){
							$objActSheet->setCellValue($columnNameArray[$columnNumber].$lineNumber, $reportExtraInfo);
						}
						$columnNumber++;
					endforeach;
					// if it is report, print tags
					$objActSheet->setCellValue($columnNameArray[$columnNumber++].$lineNumber, " ");
					if($formData['submittype'] == 'report'){
						foreach($allTagArray as $tagName){
							if(isset($reportTagArray[$surveys->AccessCode][$tagName['name']])){
								$objActSheet->setCellValue($columnNameArray[$columnNumber].$lineNumber, "1");
							}
							$columnNumber++;
						}
					}
					$lineNumber ++;
				endforeach;
				
			}else{
				// if get only one survey from ws
				$columnNumber = 0;
				$survey = $this->view->surveyArray;
				$objActSheet->setCellValue($columnNameArray[$columnNumber++].$lineNumber, $surveys->AccessCode);
				// more than one answer in the survey
				if(isset($survey->AnswerType) && is_array($survey->AnswerType) && !empty($survey->AnswerType)){
					foreach($survey->AnswerType as $question):
						if(isset($question->AnswerText) && is_array($question->AnswerText) && !empty($question->AnswerText)){
							foreach($question->AnswerText as $text):
								$temp[$text] =  "1";
							endforeach;
						}else{
							if(array_key_exists($question->QuestionId, $textQuestionArray)){
								$decodeValue = preg_replace('/[\n\r\t]/', ' ', base64_decode($question->AnswerText));
								$temp[$question->QuestionId] = isset($question->AnswerText)? $decodeValue:""; 
							}else{
								$temp[$decodeValue] = "1";
							}
						}
					endforeach;
				}else{
				// only one answer in the survey
					if(isset($survey->AnswerType->AnswerText) && is_array($survey->AnswerType->AnswerText) && !empty($survey->AnswerType->AnswerText)){
						foreach($surveys->AnswerType->AnswerText as $text):
							$temp[$text] =  "1";

						endforeach;
					}else{
						if(array_key_exists($surveys->AnswerType->QuestionId, $textQuestionArray)){
							$decodeValue = preg_replace('/[\n\r\t]/', ' ', base64_decode($surveys->AnswerType->AnswerText));
							$temp[$surveys->AnswerType->QuestionId] = isset($surveys->AnswerType->AnswerText)? $decodeValue:""; 
						}else{
							$temp[$decodeValue] = "1";
						}
					}
				}
				// print answers from ws
				for($i = 0; $i<count($tag); $i++){
					if(isset($temp[$tag[$i]])){
						$objActSheet->setCellValue($columnNameArray[$columnNumber].$lineNumber, "\"".$temp[$tag[$i]]."\"");
					}
					$columnNumber++;
				}
				// print extra info
				foreach($this->view->reportExtraInfoArray[$surveys->AccessCode] as $reportExtraInfo):
					if(isset($reportExtraInfo)){
						$objActSheet->setCellValue($columnNameArray[$columnNumber].$lineNumber, $reportExtraInfo);
					}
					$columnNumber++;
				endforeach;
				// if it is report, print tags
				$objActSheet->setCellValue($columnNameArray[$columnNumber++].$lineNumber, " ");
				if($formData['submittype'] == 'report'){
					foreach($allTagArray as $tagName){
						if(isset($reportTagArray[$surveys->AccessCode][$tagName['name']])){
							$objActSheet->setCellValue($columnNameArray[$columnNumber].$lineNumber, "1");
						}
						$columnNumber++;
					}
				}
			}
			// download...
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			header("Pragma: public"); 
	        header("Expires: 0"); 
	        header("Cache-Control:must-revalidate, post-check=0, pre-check=0"); 
	        header("Content-Type:application/force-download"); 
	        header("Content-Type:application/vnd.ms-execl"); 
	        header("Content-Type:application/octet-stream"); 
	        header("Content-Type:application/download");
	        header('Content-Disposition:attachment;filename="report.xlsx"'); 
	        header("Content-Transfer-Encoding:binary"); 
	        $objWriter->save('php://output'); 
		} 
	}
	
	
	public function admindownloadsurveyAction()
	{	
		// post
		if ($this->_request->isPost()) {
			$formData = $this->_request->getPost();
			//get survey
			$surveyId = $this->view->surveyId = $formData['survey_id'];
			if($formData['language'] == 'en'){
				$surveyId = $formData['survey_'.$surveyId.'_en'];
			}
			$db = Zend_Registry::get('db');
			$selectEmail = $db->select();
			$selectEmail ->from('consumer', array('email', 'province', 'city', 'address1', 'phone'))
			->join('poll_participation', 'poll_participation.consumer_id = consumer.id', null)
			->join('profile_survey', 'profile_survey.id = poll_participation.poll_id', null)
			->joinLeft('consumer_extra_info', 'consumer_extra_info.consumer_id = consumer.id')
			->limit(0);
			if($formData['language'] == 'en'){
				$selectEmail->where("language_pref = 'en'")
				->where('profile_survey.i2_survey_id_en = ?', $surveyId);
			}else{
				$selectEmail->where("language_pref != 'en'")
				->where('profile_survey.i2_survey_id = ?', $surveyId);
			}
			$emailArray = $db->fetchAll($selectEmail);
			
			$emailList = array();
			$this->view->consumerExtraInfoArray = array();
			foreach($emailArray as $email){
				array_push($emailList, $email['email']);
				$this->view->consumerExtraInfoArray[$email['email']] = array($email['province'],$email['city'],$email['address1'],$email['phone'],
				$email['gender'],$email['birthdate'],$email['profession'],$email['education'],$email['have_children'],$email['children_birth_year'],
				$email['income'],$email['online_shopping'],$email['use_extra_bonus_for']);
			}
			if(count($emailList) > 0){
				$indicate2Connect = new Indicate2_Connect();
				$response = $indicate2Connect->getAnswerSetForParticipant($surveyId, $emailList);
				$this->view->surveyQuestionArray = $response->QuestionType;
				$this->view->surveyArray = $response->AnswerSetType;
			}else{
				$this->view->surveyQuestionArray = null;
				$this->view->surveyArray = null;
			}
//			Zend_Debug::dump($response);
//			return;
			$this->_helper->layout->disableLayout();
			//download...
			header("Content-type:application/vnd.ms-excel; charset=gb18030");   
			header("Content-Disposition:filename=survey_".$this->view->surveyId."_".date("Y-m-d H-i-s").".xls"); 
			
			$tag = array();
			$i = -1;
			// print qestions:
			echo "Email"."\t";
			foreach($this->view->surveyQuestionArray as $surveyQuestion):
				echo iconv("UTF-8","gb18030",$surveyQuestion->QuestionText)."\t"; 
				if(isset($surveyQuestion->SelectionQuestionOptionType) && is_array($surveyQuestion->SelectionQuestionOptionType) && !empty($surveyQuestion->SelectionQuestionOptionType)){
					for($temp = 0; $temp < count($surveyQuestion->SelectionQuestionOptionType)-1; $temp++){
						echo "\t";
					}
				}
			endforeach;
			echo "\n";
			$textQuestionArray = array();
			echo "Email"."\t";
			foreach($this->view->surveyQuestionArray as $surveyQuestion):
				if(isset($surveyQuestion->SelectionQuestionOptionType) && is_array($surveyQuestion->SelectionQuestionOptionType) && !empty($surveyQuestion->SelectionQuestionOptionType)){
					foreach($surveyQuestion->SelectionQuestionOptionType as $selectionQuestionOptionTypeTemp){
						echo iconv("UTF-8","gb18030",$selectionQuestionOptionTypeTemp->OptionText)."\t";
						$tag[++$i] = $selectionQuestionOptionTypeTemp->OptionId;
					}
				}else{
					echo iconv("UTF-8","gb18030",$surveyQuestion->QuestionText)."\t"; 
					$tag[++$i] = $surveyQuestion->QuestionId;
					$textQuestionArray[$surveyQuestion->QuestionId] = 1;
				}
			endforeach;
			echo "province"."\t";
			echo "city"."\t";
			echo "address1"."\t";
			echo "phone"."\t";
			echo "gender"."\t";
			echo "birthdate"."\t";
			echo "profession"."\t";
			echo "education"."\t";
			echo "have_children"."\t";
			echo "children_birth_year"."\t";
			echo "income"."\t";
			echo "online_shopping"."\t";
			echo "use_extra_bonus_for"."\t";
			echo "\n";
			// print answers:
			// include more than one survey
		if(isset($this->view->surveyArray) && is_array($this->view->surveyArray) && !empty($this->view->surveyArray)){
				foreach($this->view->surveyArray as $surveys):
					$temp = array();
					echo $surveys->ParticipantEmail."\t";
					// more than one answer in the survey
					if(isset($surveys->AnswerType) && is_array($surveys->AnswerType) && !empty($surveys->AnswerType)){
						foreach($surveys->AnswerType as $question):
							if(isset($question->AnswerText) && is_array($question->AnswerText) && !empty($question->AnswerText)){
								foreach($question->AnswerText as $text):
									$temp[$text] =  "1\t";
								endforeach;
							}else{
								if(array_key_exists($question->QuestionId, $textQuestionArray)){
									$temp[$question->QuestionId] = isset($question->AnswerText)? iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ', base64_decode($question->AnswerText)))."\t":"\t"; 
								}else{
									$temp[iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ', base64_decode($question->AnswerText)))] = "1\t";
								}
							}
						endforeach;
					}else{
					// only one answer in the survey
						if(isset($surveys->AnswerType->AnswerText) && is_array($surveys->AnswerType->AnswerText) && !empty($surveys->AnswerType->AnswerText)){
							foreach($surveys->AnswerType->AnswerText as $text):
								$temp[$text] =  "1\t";
							endforeach;
						}else{
							if(array_key_exists($surveys->AnswerType->QuestionId, $textQuestionArray)){
								$temp[$surveys->AnswerType->QuestionId] = isset($surveys->AnswerType->AnswerText)? iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ', base64_decode($surveys->AnswerType->AnswerText)))."\t":"\t"; 
							}else{
								$temp[iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ', base64_decode($surveys->AnswerType->AnswerText)))] = "1\t";
							}
						}
					}
					// print
					for($i = 0; $i<count($tag); $i++){
						echo isset($temp[$tag[$i]])? $temp[$tag[$i]]:"\t";
					}
					//
					foreach($this->view->consumerExtraInfoArray[$surveys->ParticipantEmail] as $consumerExtraInfo):
						echo isset($consumerExtraInfo) ? iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ',$consumerExtraInfo))."\t" : "\t";
					endforeach;
				echo "\n";
				endforeach;
			}else{
			// include only one survey
				$survey = $this->view->surveyArray;
				echo $surveys->ParticipantEmail."\t";
				// more than one answer in the survey
				if(isset($survey->AnswerType) && is_array($survey->AnswerType) && !empty($survey->AnswerType)){
					foreach($survey->AnswerType as $question):
						if(isset($question->AnswerText) && is_array($question->AnswerText) && !empty($question->AnswerText)){
							foreach($question->AnswerText as $text):
								$temp[$text] =  "1\t";
							endforeach;
						}else{
							if(array_key_exists($question->QuestionId, $textQuestionArray)){
								$temp[$question->QuestionId] = isset($question->AnswerText)? iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ', base64_decode($question->AnswerText)))."\t":"\t"; 
							}else{
								$temp[iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ', base64_decode($question->AnswerText)))] = "1\t";
							}
						}
					endforeach;
				}else{
				// only one answer in the survey
					if(isset($survey->AnswerType->AnswerText) && is_array($survey->AnswerType->AnswerText) && !empty($survey->AnswerType->AnswerText)){
						foreach($surveys->AnswerType->AnswerText as $text):
							$temp[$text] =  "1\t";
						endforeach;
					}else{
						if(array_key_exists($surveys->AnswerType->QuestionId, $textQuestionArray)){
							$temp[$surveys->AnswerType->QuestionId] = isset($surveys->AnswerType->AnswerText)? iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ', base64_decode($surveys->AnswerType->AnswerText)))."\t":"\t"; 
						}else{
							$temp[iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ', base64_decode($surveys->AnswerType->AnswerText)))] = "1\t";
						}
					}
				}
				// print
				for($i = 0; $i<count($tag); $i++){
					echo isset($temp[$tag[$i]])? $temp[$tag[$i]]:"\t";
				}
				//
				foreach($this->view->consumerExtraInfoArray[$surveys->ParticipantEmail] as $consumerExtraInfo):
					echo isset($consumerExtraInfo) ? iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ',$consumerExtraInfo))."\t" : "\t";
				endforeach;
				echo "\n";
			}
		}
	}
	
	
	function adminreportimagesAction(){
		$this->_helper->layout->setLayout ( "layout_admin" );

		if($this->_request->getParam( 'report' )){
			$this->_helper->layout->disableLayout();
			$reportModel = new Report();
			$report = $reportModel->find($this->_request->getParam( 'report' ))->current();
		
			//get new report
			$config = Zend_Registry::get('config');
			$url = $config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']; 
			$this->view->url = $url;	   
			$this->view->url = $url;	
			die($url);   
		}else{
			$campaignModel = new Campaign();
			$campaigns = $campaignModel->fetchAll();
			$campaignsArray = array();
			foreach($campaigns as $val){
				$campaignsArray[$val->id] = $val->name;
			}
			$db = Zend_Registry::get ( 'db' );
			$select = $db->select ();
			$select->from ( 'report_images', array( 'report_images.name as images' ) );
			$select->join ( 'report', 'report.id = report_images.report' , array('report.campaign_id as campaign','report.id as report'));
			$select->join ( 'consumer', 'consumer.id = report_images.consumer' , array('consumer.name','consumer.email','consumer.phone'));
			$select->order ( 'report.campaign_id desc' );
			$this->view->reportImages = $db->fetchAll( $select );
			$this->view->campaigns    = $campaignsArray;
     	
        }
   }			
		//print_r($campaignsArray);die;
		
   function adminaddreportAction(){
   	   $this->_helper->layout->setLayout ( "layout_admin" );
   	   $consumer = $this->_request->getParam( 'uid' );
   	   if($this->_request->getParam('file')) {  	   	   
		   $file  = explode('&',$this->_request->getParam('file'));
		   $file  = $file[0];
		   $this->view->file = "./surveys/".$file.".phtml";
		   $this->_helper->layout->setLayout("layout_questionnaire");
	   } 
   	   
	   $campaignModel = new Campaign();
	   $campaignData = $campaignModel->fetchAll();
	   $this->view->campaigns = $campaignData;
	   
	   $this->view->sources = array('application','phone','email','sms');
	   
	   $postData = $this->_request->getPost();	   
	   if(count($postData)){
	   		$this->view->campaign = $postData['campaign'];
	   		$this->view->source   = $postData['source'];
	   		$adminAddSession 	  = new Zend_Session_Namespace('adminAddSession');
	   		$adminAddSession->consumer = $consumer;
	   		$adminAddSession->source   = $postData['source'];
	   		$adminAddSession->campaign = $postData['campaign'];
	   		foreach ($campaignData as $campaign){
	   			if($campaign->id == $postData['campaign']){
	   				$this->view->link     = $campaign->i2_survey_id;
	   				$this->view->surveyId = $campaign->i2_survey_id;
	   			}
	   		}	   		
	   }
	   if($this->_request->getParam('survey')){
	   	  $this->view->surveyId = $this->_request->getParam('survey');
	   }
	   $consumerModel = new Consumer();
	   $this->view->consumer = $consumerModel->fetchRow('id='.$consumer);
	   $this->view->uid = $consumer;
   }
   /**
    * 
    * analyze the report statistics replied by admin group
    */
   
   function adminreportcaculateAction(){  
   	$this->_helper->layout->setLayout ( "layout_admin" );
   	$curPage = 1;
	$rowsPerPage = 99999;
	if($this->_request->getParam('page'))
    {
        $curPage = $this->_request->getParam('page');
    }
   	$campaignModel = new Campaign();
   	$campaignData  = $campaignModel->fetchAll();   	
   	$adminModel    = new Admin();
   	$adminData     =  $adminModel->fetchAll();  	
   	$postData = $this->_request->getPost();	 
   	if(count($postData)){
   		//var_dump($postData);die;
   		$this->view->campaigns = $postData['campaign'];
   		$this->view->admins    = $postData['admin'];
   		$this->view->start_date    = $postData['start_date'];
   		$this->view->end_date    = $postData['end_date'];
   		$db = Zend_Registry::get ( 'db' );
		$select = $db->select();
		$select->from('report_batch', '*')
			   ->join('admin', 'admin.id = report_batch.admin_id','name as admin_name')
		       ->join('campaign', 'campaign.id = report_batch.campaign_id', 'name as campaign_name')
		       ->order('report_batch.start_datetime desc');
		if(!in_array('all',$this->view->admins)&&count($this->view->admins)){
			$select->where('admin_id    in( '.implode(',', $this->view->admins).')');
		}
		
		if(!in_array('all',$this->view->campaigns)&&count($this->view->campaigns)){
			$select->where('campaign_id in( '.implode(',', $this->view->campaigns).')');
		}
   		if($this->view->start_date != ''){
			$select->where('start_datetime >= "'.$this->view->start_date.'"');
		}
   	   	if($this->view->end_date != ''){
			$select->where('end_datetime <= "'.$this->view->end_date.'"');
		}
		$reportBatchs = $db->fetchAll($select);
		//var_dump($reportBatchs);die;
		//paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($reportBatchs));
		$paginator->setCurrentPageNumber($curPage)
		->setItemCountPerPage($rowsPerPage); 
		$this->view->paginator = $paginator; 
   	}
   	$this->view->campaignData = $campaignData;
   	$this->view->adminData    = $adminData;
   }
   
   
   function admincompletemissionAction(){
   	    $consumer = $this->_request->getParam('consumer');
   	    $campaign = $this->_request->getParam('campaign');   	    
   	    $currentTime = date("Y-m-d H:i:s");
   	    
   	    $campaignInvitation = new CampaignInvitation();
   	    $campaignInvitation->update(array('state'=>'COMPLETED'),'consumer_id ='.$consumer .' and campaign_id ='.$campaign);
   	    
   	    $campainInvitationRow = $campaignInvitation->fetchRow('consumer_id ='.$consumer .' and campaign_id ='.$campaign);
   	    
   	    $campaignParticipation = new CampaignParticipation();
   	    $row = $campaignParticipation->createRow();
   	    $row->campaign_invitation_id = $campainInvitationRow->id;
   	    $row->accept_date = $currentTime;
   	    $row->state = 'COMPLETED';
   	    $row->save();
   	    $this->_helper->layout->disableLayout();
   	    die('结束');
   	    
   	    
   }
}
