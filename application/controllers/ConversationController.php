<?php
include_once 'Indicate2Connect.php';
class ConversationController extends MyController {
	function adminindexAction() {
		$this->_helper->layout->setLayout("layout_admin");
		$fileForm = new TelephonePlanForm();
		$this->view->form = $fileForm;
		$dir = dirname ( dirname ( dirname ( __FILE__ ) ) );
		$this->view->isSubmit = false;
		$this->view->exits    = 0;
		if ($this->_request->isPost()){
			$this->view->isSubmit = true;
			 $formData = $this->_request->getPost();
			 if ($fileForm->isValid($formData)) {
			
				 /* Uploading Document File on Server */
				 $upload = new Zend_File_Transfer_Adapter_Http();
				 $upload->setDestination($dir.'/public/csv/');

				 $name = basename($upload->getFileName('doc_path'));
				 if(!file_exists($upload->getFileName('doc_path'))){
				 		 try {
						 // upload received file(s)
						 $upload->receive();
						 } catch (Zend_File_Transfer_Exception $e) {
						 	$e->getMessage();
						 }
					 $telephonePlanModel = new TelephonePlan();
				     $row = $telephonePlanModel->createRow();
				     //2011-04-08 ham.bao separate the sessions with admin
				     $row->editor_id = $this->_currentAdmin->id;
				     $row->file      = $name;
				     $row->purpose   = $formData['detail'];
				     $planId = $row->save();
					 $this->view->isSubmit = true;
					 $handle = fopen($upload->getFileName('doc_path'),"r");
					 while ($data = fgetcsv($handle, 1000, "\n")) {
	                    $telephoneLog[] = explode(',',$data[0]);
					 }
					 fclose($handle);
					 
					 if(count($telephoneLog)){
					 	$telephoneLogModel = new TelephoneLog();
					 	foreach($telephoneLog as $log){
					 		if($log[0]!=''){
						 		$row = $telephoneLogModel->createRow();
						 		$row->consumer_id = $log[0];
						 		//2011-04-08 ham.bao separate the sessions with admin
						 		$row->admin_id    = $this->_currentAdmin->id;
						 		$row->plan_id     = $planId;
						 		$row->contents     = implode('$',array_slice($log,1));
						 		$row->save();
					 		}
					 	}
					 }
				 }else{
				 	$this->view->exits = 1;
				 }	 
			 }
		 }


		
		
/**
 * original logic
 */
//		$request = $this->getRequest();
//		$campaign = new Campaign();
//		$order = "expire_date desc";
//		$this->view->campaigns = $campaign->fetchAll(null, $order, null, null);
//
//		$telephonePlan = new TelephonePlan();
//		$order = "edit_time desc";
//		$this->view->telephonePlans = $telephonePlan->fetchAll(null, $order, null, null);
//
//		//1.get each plan call back consumers number
//		$undoneArray = array ();
//		$comesbackArray = array ();
//		$db = Zend_Registry :: get('db');
//		foreach ($this->view->telephonePlans as $telephonePlan) {
//			if ($telephonePlan->type == 'TELEPHONE') {
//				$rs = $db->fetchAll("SELECT COUNT(*) as doneNum, SUM(busy) as busyNum FROM telephone_log WHERE plan_id=:t1 and state<>'New'", array (
//					't1' => $telephonePlan->id
//				));
//				if (count($rs) > 0) {
//					$undoneArray[$telephonePlan->id] = $telephonePlan->total_consumers - $rs[0]["doneNum"];
//					$comesbackArray[$telephonePlan->id] = $rs[0]["busyNum"];
//				}
//			} else
//				if ($telephonePlan->type == 'SMS') {
//					$rs = $db->fetchAll("SELECT COUNT(*) as undoneNum FROM short_message WHERE plan_id=:t1 and state='New'", array (
//						't1' => $telephonePlan->id
//					));
//					if (count($rs) > 0) {
//						$undoneArray[$telephonePlan->id] = $rs[0]["undoneNum"];
//					}
//				}
//
//		}
//		$this->view->undoneArray = $undoneArray;
//		$this->view->comesbackArray = $comesbackArray;
//		$this->_helper->layout->setLayout("layout_admin");

	}
	
	function admintelephoneAction(){
		$this->_helper->layout->setLayout("layout_admin");				
		if($this->_request->getParam('plan')){
			$this->view->log  = array();
			$this->_helper->layout->disableLayout();
			$idValue = explode('&',$this->_request->getParam('plan'));
			$id = $idValue[0];	
	        $telephoneLogModel = new TelephoneLog();
	        $rows = $telephoneLogModel->fetchAll('plan_id='.$id)->toArray();
	        $this->view->log  = $rows;
		}else{
			$this->view->plan = array();
			$telephonePlanModel = new TelephonePlan();
			$rows = $telephonePlanModel->fetchAll()->toArray();	
			$this->view->plan = $rows;
			//var_dump($rows);die;
		}	
	}

	function admintestAction() {
		$result = '';
		if ($this->_request->isPost()) {
			$formData = $this->_request->getPost();
			$sql = $formData['sql'];
			if ($sql == '' || $sql == null) {
				return;
			} else {
				$sql = 'select count(*) ' . $sql;
			}

			//1.get all consumers
			$db = Zend_Registry :: get('db');
			$result = $db->fetchOne($sql);
		}
		$this->_helper->json($result);
	}

	function adminlistAction() {
		$this->_helper->layout->setLayout("layout_admin");
		$request = $this->getRequest();
		$db = Zend_Registry :: get('db');

		// get: show current plan
		if (!$request->isPost()) {
			$plan_id = $request->getParam('plan_id');
			// get spark's telephone log
			$telephoneLogModel = new TelephoneLog();
			$messageArray = $this->_flashMessenger->getMessages();
			if ($messageArray != null) {
				// show pre page
				if ($messageArray[0][0] == 'preView') {
					$telephoneLog = $telephoneLogModel->fetchRow("id < " . $messageArray[0][1] . "  and plan_id = " . $plan_id, 'id desc');
				}
				// show first call back page
				if ($messageArray[0][0] == 'firstcallbackView') {
					$telephoneLog = $telephoneLogModel->fetchRow("busy = 1  and plan_id = " . $plan_id);
				}
				// show pre call back page
				if ($messageArray[0][0] == 'precallbackView') {
					$telephoneLog = $telephoneLogModel->fetchRow("id < " . $messageArray[0][1] . " and busy = 1  and plan_id = " . $plan_id, 'id desc');
				}
				//show next call back page
				if ($messageArray[0][0] == 'nextcallbackView') {
					$telephoneLog = $telephoneLogModel->fetchRow("id > " . $messageArray[0][1] . " and busy = 1  and plan_id = " . $plan_id);
				}
			}
			if (!isset ($telephoneLog)) {
				//show the next unhandled page
				$telephoneLog = $telephoneLogModel->fetchRow("state = 'NEW'  and plan_id = " . $plan_id);
			}
			if (!isset ($telephoneLog)) {
				//if plan is finished, show the first page
				$telephoneLog = $telephoneLogModel->fetchRow("plan_id = " . $plan_id);
			}
			if (isset ($telephoneLog)) {
				$selectTag = $db->select();
				$selectTag->from('telephone_log_tag', 'tag_id')->where('telephone_log_id = ?', $telephoneLog->id);
				$selectedTags = $db->fetchAll($selectTag);
				$this->view->selectedTagsArray = array ();
				foreach ($selectedTags as $tag) {
					$this->view->selectedTagsArray[$tag['tag_id']] = '1';
				}
			}
			$consumer_id = $telephoneLog->consumer_id;
			$campaign = new Campaign();
			$order = "expire_date desc";
			$this->view->campaigns = $campaign->fetchAll(null, $order, null, null);
			$this->view->plan_id = $plan_id;
			$this->view->telephoneLog_id = $telephoneLog->id;
			$this->view->telephoneLog = $telephoneLog;
			// post: create new plan
		} else {
			$formData = $request->getPost();
			$sql = $formData['sql'];
			if ($sql == '' || $sql == null) {
				return;
			} else {
				$sql = 'select consumer.* ' . $sql;
			}
			$type = $formData['type'];
			$content = $formData['content'];

			//1.execute sql
			$db = Zend_Registry :: get('db');
			$result = $db->query($sql);
			$consumers = $result->fetchAll();

			//2.create new telephone plan
			$telephonePlanModel = new TelephonePlan();
			$row = $telephonePlanModel->createRow();
			$row->sql = $sql;
			$row->type = $type;
			$row->total_consumers = count($consumers);
			$row->content = $content;
			//2011-04-08 ham.bao separate the sessions with admin
			$row->admin_id = $this->_currentAdmin->id;
			$row->edit_time = date("Y-m-d H:i:s");
			$plan_id = $row->save();

			//3.create new telephone logs
			$db = Zend_Registry :: get('db');
			$result = $db->query($sql);
			$consumers = $result->fetchAll();

			$telephoneLogModel = new TelephoneLog();
			$temp = 1;
			foreach ($consumers as $row) {
				$newlog = $telephoneLogModel->createRow();
				$newlog->consumer_id = $row['id'];
				//2011-04-08 ham.bao separate the sessions with admin
				$newlog->admin_id = $this->_currentAdmin->id;
				$newlog->state = 'New';
				$newlog->plan_id = $plan_id;
				$newlog->edit_time = date("Y-m-d H:i:s");
				if ($temp == 1) {
					$telephoneLog_id = $newlog->save();
					$temp = 0;
				} else {
					$newlog->save();
				}

			}
			$consumer_id = $consumers[0]['id'];
			$this->view->plan_id = $plan_id;
			$this->view->telephoneLog_id = $telephoneLog_id;
			$this->view->offset = 0;
		}
		// get spark's info
		$consumerModel = new Consumer();
		$this->view->consumerBaseInfo = $consumerModel->fetchRow('id = ' . $consumer_id);

		$consumerExtraInfoModel = new ConsumerExtraInfo();
		$this->view->consumerExtraInfo = $consumerExtraInfoModel->fetchRow('consumer_id = ' . $consumer_id);
		$this->view->gender = (isset ($this->consumerExtraInfo) && isset ($this->consumerExtraInfo->gender)) ? $this->consumerExtraInfo->gender : "";

		$consumerLogModel = new Log();
		$this->view->consumerLog = $consumerLogModel->fetchRow('consumer_id = ' . $consumer_id, 'date desc');

		$selectTotalReport = $db->select();
		$selectTotalReport->from('report', 'count(*)')->where('consumer_id = ?', $consumer_id);
		$this->view->totalReport = $db->fetchOne($selectTotalReport);

		$selectTotalCampaign = $db->select();
		$selectTotalCampaign->from('campaign_invitation', null)->join('campaign', 'campaign.id = campaign_invitation.campaign_id', 'name')->where('consumer_id = ?', $consumer_id)->where("state = 'ACCEPTED'");
		$this->view->totalCampaigns = $db->fetchAll($selectTotalCampaign);
		//tag for telephone
		$select = $db->select();
		$select->from('tags', array (
			'name as key',
			'id as tag_id'
		))->where("module = 'TELEPHONE'")->order('sort');
		$this->view->tags = $db->fetchAll($select);
		// show page number
		$selectTotalPage = $db->select();
		$selectTotalPage->from('communicate_plan', 'total_consumers')->where('id = ?', $this->view->plan_id);
		$this->view->totalPage = $db->fetchOne($selectTotalPage);
		$selectCurrentPage = $db->select();
		$selectCurrentPage->from('telephone_log', 'count(*)')->where('plan_id = ?', $this->view->plan_id)->where('id <= ?', $this->view->telephoneLog_id);
		$this->view->currentPage = $db->fetchOne($selectCurrentPage);
		// show call back page number
		$selectTotalCallbackPage = $db->select();
		$selectTotalCallbackPage->from('telephone_log', 'count(*)')->where('plan_id = ?', $this->view->plan_id)->where('busy = 1');
		$this->view->totalCallbackPage = $db->fetchOne($selectTotalCallbackPage);
		if (isset ($messageArray) && isset ($messageArray[0][0]) && ($messageArray[0][0] == 'nextcallbackView' || $messageArray[0][0] == 'precallbackView' || $messageArray[0][0] == 'firstcallbackView' || $messageArray[0][0] == 'finishcallbackView')) {
			if ($messageArray[0][0] == 'finishcallbackView') {
				$this->view->currentCallbackPage = 0;
			} else {
				$selectCurrentCallbackPage = $db->select();
				$selectCurrentCallbackPage->from('telephone_log', 'count(*)')->where('plan_id = ?', $this->view->plan_id)->where('id <= ?', $this->view->telephoneLog_id)->where('busy = 1');
				$this->view->currentCallbackPage = $db->fetchOne($selectCurrentCallbackPage);
				$this->view->callbackPageTitle = 'Call Back ';
			}
		} else {
			$this->view->currentCallbackPage = 0;
		}
		// show old telephone log
		$selectOldTelephoneLog = $db->select();
		$selectOldTelephoneLog->from('telephone_log', '*')->where('consumer_id = ?', $consumer_id)->where("state != 'NEW'")->where('plan_id != ?', $this->view->plan_id);
		$this->view->oldTelephoneLogs = $db->fetchAll($selectOldTelephoneLog);
		$this->view->oldTelephoneLogTagArray = array ();
		foreach ($this->view->oldTelephoneLogs as $oldTelephoneLog) {
			$selectOldTelephoneLogTag = $db->select();
			$selectOldTelephoneLogTag->from('tags', 'name')->join('telephone_log_tag', 'tags.id = telephone_log_tag.tag_id', null)->where('telephone_log_tag.telephone_log_id = ?', $oldTelephoneLog['id']);
			$this->view->oldTelephoneLogTagArray[$oldTelephoneLog['id']] = $db->fetchAll($selectOldTelephoneLogTag);
		}
	}

	function adminshownextAction() {
		$request = $this->getRequest();
		$formData = $request->getPost();
		if ($formData['callback_next'] == 0) {
			// show first call back
			$this->_flashMessenger->addMessage(array (
				'0' => 'firstcallbackView'
			));
		} else {
			//show next telephone log
			$telephoneLogModel = new TelephoneLog();
			$telephoneLog = $telephoneLogModel->fetchRow('id = ' . $formData['telephonelog_id']);
			if (!isset ($telephoneLog)) {
				$telephoneLog = $telephoneLogModel->createRow();
			}
			$telephoneLog->consumer_id = $formData['consumer_id'];
			$telephoneLog->score = $formData['score'];
			$telephoneLog->state = 'Called';
			$telephoneLog->busy = $formData['busy'];
			$telephoneLog->duration = (isset ($formData['duration']) && $formData['duration'] != '') ? $formData['duration'] : 0;
			$telephoneLog->comments = $formData['comments'];
			//2011-04-08 ham.bao separate the sessions with admin
			$telephoneLog->admin_id = $this->_currentAdmin->id;
			$telephoneLog->edit_time = date("Y-m-d H:i:s");
			$telephoneLog->plan_id = $formData['plan_id'];
			$telephoneLog->save();
			//telephone_log_tag 
			$telephoneLogTagModel = new TelephoneLogTag();
			$db = $telephoneLogTagModel->getAdapter();
			$where = $db->quoteInto('telephone_log_id = ?', $formData['telephonelog_id']);
			$rows_affected = $telephoneLogTagModel->delete($where);

			foreach ($formData['note'] as $note) {
				$telephoneLogTag = $telephoneLogTagModel->createRow();
				$telephoneLogTag->telephone_log_id = $formData['telephonelog_id'];
				$telephoneLogTag->tag_id = $note;
				$telephoneLogTag->save();
			}
			// show next call back
			if ($formData['callback_next'] == 1) {
				$this->_flashMessenger->addMessage(array (
					'0' => 'nextcallbackView',
					'1' => $formData['telephonelog_id']
				));
			}
			// finish call back
			if ($formData['callback_next'] == 2) {
				$this->_flashMessenger->addMessage(array (
					'0' => 'finishcallbackView',
					'1' => $formData['telephonelog_id']
				));
			}
		}

		$this->_redirect('conversation/adminlist/plan_id/' . $formData['plan_id']);
	}

	function adminshowpreAction() {
		$request = $this->getRequest();
		$telephoneLog_id = $request->getParam('telephoneLog_id');
		$this->_flashMessenger->addMessage(array (
			'0' => 'preView',
			'1' => $telephoneLog_id
		));
		$this->_redirect('conversation/adminlist/plan_id/' . $request->getParam('plan_id'));
	}
	function adminshowprecallbackAction() {
		$request = $this->getRequest();
		$telephoneLog_id = $request->getParam('telephoneLog_id');
		$this->_flashMessenger->addMessage(array (
			'0' => 'precallbackView',
			'1' => $telephoneLog_id
		));
		$this->_redirect('conversation/adminlist/plan_id/' . $request->getParam('plan_id'));
	}
	function admindeleteAction() {
		$this->_helper->layout->setLayout("layout_admin");
		$this->view->title = "Delete Plan";

		if(!$this->_request->isPost()) {
			$id = (int) $this->_request->getParam('plan_id');
			$type = $this->_request->getParam('type');
			if($id > 0) {
				//first, delete the telephone log or sms
				if($type == "TELEPHONE") {
					$telephoneLogModel = new TelephoneLog();
					$telephoneLogModel->deleteByPlan($id);
				} else if ($type == "SMS") {
					$shortMessageModel = new ShortMessage();
					$shortMessageModel->deleteByPlan($id);
				} else {
					
				}
				//second, delete the plan
				$telephonePlanModel = new TelephonePlan();
                $where = 'id = ' . $id;
				$telephonePlanModel->delete($where);
			}
		}
        //$this->_helper->json($type);
		$this->_redirect('conversation/adminindex');
	}

	function admintelephoneloglistAction() {
		$this->_helper->layout->setLayout("layout_admin");
		$this->view->title = "TelephoneLog List";

		if ($this->_request->isPost()) {

		} else {
			$plan_id = ( int ) $this->_request->getParam('plan_id');
			if ($plan_id > 0) {
				$db = Zend_Registry :: get('db');
				$select = $db->select();
				$select->from('telephone_log', '*')->join('consumer', 'consumer.id = telephone_log.consumer_id', array (
					'name as consumer_name',
					'recipients_name'
				))->join('admin', 'admin.id = telephone_log.admin_id', array (
					'name as admin_name'
				))->where('telephone_log.plan_id = ?', $plan_id)->order('telephone_log.edit_time desc');
				$this->view->telephoneLogs = $db->fetchAll($select);
			}
		}
		//tag for telephone
		$db = Zend_Registry :: get('db');
		$this->view->oldTelephoneLogTagArray = array ();
		foreach ($this->view->telephoneLogs as $oldTelephoneLog) {
			$selectOldTelephoneLogTag = $db->select();
			$selectOldTelephoneLogTag->from('tags', 'name')->join('telephone_log_tag', 'tags.id = telephone_log_tag.tag_id', null)->where('telephone_log_tag.telephone_log_id = ?', $oldTelephoneLog['id']);
			$this->view->oldTelephoneLogTagArray[$oldTelephoneLog['id']] = $db->fetchAll($selectOldTelephoneLogTag);
		}
		//        Zend_Debug::dump($this->view->telephoneLogs);
	}

	function adminreportAction() {
		$request = $this->getRequest();
		if ($request->isPost()) {
			$formData = $this->_request->getPost();
			$consumer_id = $formData['consumer_id'];
			$campaign_id = $formData['campaign_id'];
			$comment = $formData['phone_comments'];
			$source = $formData['source'];

			//whether participate in the campaign
			$campaigninvitationModel = new CampaignInvitation();
			$campaigninvitation = $campaigninvitationModel->fetchRow('campaign_id = ' . $campaign_id . ' and consumer_id' . ' =' . $consumer_id);
			if ($campaigninvitation == null) {
				//$this->_helper->redirector('index','home');
			}
			//get i2_survey_id
			$campaignModel = new Campaign();
			$campaign = $campaignModel->fetchRow("id=" . $campaign_id);

			$langNamespace = new Zend_Session_Namespace('Lang');
			$lang = $langNamespace->lang;

			if ($lang == 'en') {
				$surveyId = $campaign->i2_survey_id_en;
			} else {
				$surveyId = $campaign->i2_survey_id;
			}
			$this->view->campaing_name = $campaign->name;
			$this->view->id = $campaign_id;


		    $indicate2Connect = new Indicate2_Connect();
		    $accesscode = $indicate2Connect->createParticipation('', $surveyId);

			$config = Zend_Registry :: get('config');
			$this->view->filloutPage = $config->indicate2->home . "/c/" . $accesscode . "/theme/wildfire";
			
			//save list in session
            $reportNamespace = new Zend_Session_Namespace('AgentReports');
            $source_key = $accesscode.'_source';
            $reportNamespace->$source_key = $source;
            $reportNamespace->$accesscode = $consumer_id;
			
			$this->view->includeCrystalCss = true;
		}
	}

	function adminphoneinAction() {
		$campaign = new Campaign();
		$order = "expire_date desc";
		$this->view->campaigns = $campaign->fetchAll(null, $order, null, null);
	}
}