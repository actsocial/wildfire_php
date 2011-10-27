<?php
require_once 'Pagination/Pagination.php';

class HistoryController extends MyController
{
	protected $_rowsPerPage = 10;
	protected $_curPage = 1;
	
	function indexAction(){
		
	}
	
	function campaignsAction(){
		$this->view->title = $this->view->translate('His_Campaigns');
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
		$select->where("campaign_invitation.consumer_id = ?", $this->_currentUser->id);
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

	function referralsAction(){
		$this->view->title = $this->view->translate('His_Referrals');
		$this->view->activeTab = "History";
		// get current page(default page = 1)
		if($this->_request->getParam('page'))
        {
        	$this->_curPage = $this->_request->getParam('page');
        }
		$db = Zend_Registry::get('db');
		$select = $db->select();
		//get data from invitation_email
		$select->from("invitation_email", array('date', 'to'))
		->where("consumer_id = ?", $this->_currentUser->id)
		->join('signup_auth_code', 'invitation_email.signup_auth_code_id = signup_auth_code.id', 'receiver')
		->group('signup_auth_code.id')
		->order('invitation_email.date desc');
		$referralsAll = $db->fetchAll($select);
        //paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($referralsAll));
		$paginator->setCurrentPageNumber($this->_curPage)
		->setItemCountPerPage($this->_rowsPerPage); 
		$this->view->paginator = $paginator;
		
        //set the No. inital value in view page
        $this->view->NoInitValue = ($this->_curPage-1)*$this->_rowsPerPage+1;
        
//        Zend_Debug::dump($referralsAll);
		
		
	}
	
	function reportsAction(){
		$this->view->title = $this->view->translate('His_Reports');
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
		$select->joinLeft("reward_point_transaction_record","reward_point_transaction_record.id = report.reward_point_transaction_record_id", 'point_amount');
		$select->where("report.consumer_id = ".$this->_currentUser->id);
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
//		Zend_Debug::dump($reportsAll);
	}
	
	function reportAction(){
		$this->view->title = $this->view->translate('His_Reports');
		$this->view->activeTab = "History";
		$id = $this->_request->getParam('id');
		
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('report', 'report.*');
		$select->join('campaign','campaign.id = report.campaign_id','name');
//		$select->join('reward_point_transaction_record','reward_point_transaction_record.id = report.reward_point_transaction_record_id','point_amount');
		$select->where("report.id = ".$id);
		$report = $db->fetchRow($select);
		
		$this->view->report = $report;
		$this->view->point = 0;
		if (isset($report['reward_point_transaction_record_id'])){
			$select2 = $db->select();
			$select2->from('reward_point_transaction_record', 'reward_point_transaction_record.point_amount');
			$select2->where('reward_point_transaction_record.id = '.$report['reward_point_transaction_record_id']);
			$this->view->point = $db->fetchOne($select2);
		}
		
		$select3 = $db->select();
		$select3->from('reply', 'reply.content');
		$select3->where('reply.report_id = '.$report['id']);
		$this->view->replyContent = $db->fetchOne($select3);

		
		$config = Zend_Registry::get('config');
				
		$this->view->reportPage1 = $config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/645";
		$this->view->reportPage2 = $config->indicate2->home."/report/showAnswer/accessCode/".$report['accesscode']."/questionId/707";
//		Zend_debug::dump($report['reward_point_transaction_record_id']);
	}
	
	function surveysAction(){
		$this->view->title = $this->view->translate('His_Surveys');
		$this->view->activeTab = "History";
		// get current page(default page = 1)
		if($this->_request->getParam('page'))
        {
        	$this->_curPage = $this->_request->getParam('page');
        }
        $langNamespace = new Zend_Session_Namespace('Lang');
		$this->view->lang = $langNamespace->lang;
		
		$db = Zend_Registry::get('db');
		$select = $db->select();	
		$select->from('profile_survey', array('name', 'english_name'));
		$select->join('poll_participation', 'poll_participation.poll_id = profile_survey.id', 'poll_participation.date');
		$select->join('reward_point_transaction_record', 'reward_point_transaction_record.date = poll_participation.date and reward_point_transaction_record.consumer_id = poll_participation.consumer_id', 'point_amount');
		$select->where('reward_point_transaction_record.transaction_id =3');
		$select->where('poll_participation.consumer_id = ?', $this->_currentUser->id);
		$select->order('poll_participation.date desc');
		$surveysAll = $db->fetchAll($select);
		//paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($surveysAll));
		$paginator->setCurrentPageNumber($this->_curPage)
		->setItemCountPerPage($this->_rowsPerPage); 
		$this->view->paginator = $paginator;
		
        //set the No. inital value in view page
        $this->view->NoInitValue = ($this->_curPage-1)*$this->_rowsPerPage+1;
	}
	
	function stuffAction(){	
		$this->view->title = $this->view->translate('His_Stuff');	
		$this->view->activeTab = "History";
		// get current page(default page = 1)
		if($this->_request->getParam('page'))
        {
        	$this->_curPage = $this->_request->getParam('page');
        }
        $langNamespace = new Zend_Session_Namespace('Lang');
		$this->view->lang = $langNamespace->lang;
		
		//get point record from DB
        $db = Zend_Registry::get('db');
		//transaction_id =1: Report
		$selectReport = $db->select();
		$selectReport->from('report', 'create_date as date')
		->where('report.consumer_id = ?', $this->_currentUser->id)
		->join('campaign', 'report.campaign_id = campaign.id', 'name')
		->join('reward_point_transaction_record', 'report.reward_point_transaction_record_id = reward_point_transaction_record.id', 'point_amount')
		->where('reward_point_transaction_record.point_amount != 0')
		->order('report.create_date desc');
		$reportArray = $db->fetchAll($selectReport);
		//transaction_id =2: Invitation(useless)
		
		//transaction_id =3: Poll
		$selectSurvey = $db->select();
		$selectSurvey->from('poll_participation', 'date')
		->where('poll_participation.consumer_id = ?', $this->_currentUser->id)
		->join('profile_survey', 'poll_participation.poll_id = profile_survey.id', array('name', 'english_name'))
		->join('reward_point_transaction_record', 'reward_point_transaction_record.date = poll_participation.date and reward_point_transaction_record.consumer_id = poll_participation.consumer_id', 'point_amount')
		->where('reward_point_transaction_record.transaction_id =3')
		->order('poll_participation.date desc'); 
		$surveyArray = $db->fetchAll($selectSurvey);
		//transaction_id =4: Exchange
		$selectExchange = $db->select();
		$selectExchange->from('reward_point_transaction_record',array('id', 'date','point_amount'))
		->where('reward_point_transaction_record.consumer_id = ?', $this->_currentUser->id)
		->where('reward_point_transaction_record.transaction_id = 4')
		->where('reward_point_transaction_record.point_amount != 0')
		->order('reward_point_transaction_record.date desc');
		$exchangeArray = $db->fetchAll($selectExchange);
		$giftSelect = $db->select();
		$giftSelect->from('product_order','reward_point_transaction_record_id')
		->join('product', 'product_order.product_id = product.id', 'name')
		->where('product_order.consumer_id = ?', $this->_currentUser->id)
		->where("product_order.state != 'CANCEL'");
		$gifts = $db->fetchAll($giftSelect);
		$giftArray = array();
		foreach($gifts as $gift){
			$giftArray[$gift['reward_point_transaction_record_id']] = $gift['name'];
		}
		//transaction_id =5: Post-campaign
		$selectpostCampaignSurvey = $db->select();
		$selectpostCampaignSurvey->from('reward_point_transaction_record',array('date','point_amount'))
		->where('reward_point_transaction_record.consumer_id = ?', $this->_currentUser->id)
		->where('reward_point_transaction_record.transaction_id = 5')
		->where('reward_point_transaction_record.point_amount != 0')
		->order('reward_point_transaction_record.date desc');
		$postCampaignSurveyArray = $db->fetchAll($selectpostCampaignSurvey);
		//transaction_id =6: Test
		
		//transaction_id =8: Url_Report
		$selectURLReport = $db->select();
		$selectURLReport->from('url_report', 'create_date as date')
		->where('url_report.consumer_id = ?', $this->_currentUser->id)
		->join('campaign', 'url_report.campaign_id = campaign.id', 'name')
		->join('reward_point_transaction_record', 'url_report.reward_point_transaction_record_id = reward_point_transaction_record.id', 'point_amount')
		->where('reward_point_transaction_record.point_amount != 0')
		->order('url_report.create_date desc');
		$urlreportArray = $db->fetchAll($selectURLReport);
		//transaction_id =9: Image_Report(Not public)
		
		//transaction_id =7: Reward
		//or transaction_id =10: Event
		$selectRewardAndEvent = $db->select();
		$selectRewardAndEvent->from('reward_point_transaction_record', array('point_amount', 'date', 'transaction_id'))
		->where('reward_point_transaction_record.consumer_id = ?', $this->_currentUser->id)
		->where('reward_point_transaction_record.transaction_id = 10 or reward_point_transaction_record.transaction_id = 7')
		->where('reward_point_transaction_record.point_amount != 0')
		->order('date desc');
		$rewardandeventArray = $db->fetchAll($selectRewardAndEvent);
//		Zend_Debug::dump($eventArray);
		// sort by date
		$stuffCount = 0;
		$stuffsAll = array();
		$sCount = 0;
		$iCount = 0;
		$rCount = 0;
		$pCount = 0;
		$uCount = 0;
		$eCount = 0;
		while(array_key_exists($sCount, $surveyArray) || array_key_exists($rCount, $reportArray)
		 || array_key_exists($iCount, $exchangeArray) || array_key_exists($pCount, $postCampaignSurveyArray)
		 || array_key_exists($uCount, $urlreportArray) || array_key_exists($eCount, $rewardandeventArray)){
	 		// look for Max date
	 		$timeCompare = null;
			$signArray = null;
			if(array_key_exists($sCount, $surveyArray) && $surveyArray[$sCount] !=null){
				$timeCompare = $surveyArray[$sCount]['date'];
				$signArray = 'survey';
			}
			if(array_key_exists($rCount, $reportArray) && $reportArray[$rCount] != null){
				if($timeCompare == null || strtotime($reportArray[$rCount]['date'])>strtotime($timeCompare)){
					$timeCompare = $reportArray[$rCount]['date'];
					$signArray = 'report';
				}					
			}
			if(array_key_exists($iCount, $exchangeArray) && $exchangeArray[$iCount] != null){
				if($timeCompare == null || strtotime($exchangeArray[$iCount]['date'])>strtotime($timeCompare)){
					$timeCompare = $exchangeArray[$iCount]['date'];
					$signArray = 'exchange';
				}
			}
			if(array_key_exists($pCount, $postCampaignSurveyArray) && $postCampaignSurveyArray[$pCount] != null){
				if($timeCompare == null || strtotime($postCampaignSurveyArray[$pCount]['date'])>strtotime($timeCompare)){
					$timeCompare = $postCampaignSurveyArray[$pCount]['date'];
					$signArray = 'post-campaign';
				}
			}
			if(array_key_exists($uCount, $urlreportArray) && $urlreportArray[$uCount] != null){
				if($timeCompare == null || strtotime($urlreportArray[$uCount]['date'])>strtotime($timeCompare)){
					$timeCompare = $urlreportArray[$uCount]['date'];
					$signArray = 'url-report';
				}
			}
			if(array_key_exists($eCount, $rewardandeventArray) && $rewardandeventArray[$eCount] != null){
				if($timeCompare == null || strtotime($rewardandeventArray[$eCount]['date'])>strtotime($timeCompare)){
					$timeCompare = $rewardandeventArray[$eCount]['date'];
					$signArray = 'reward and event';
				}
			}
			// push into array
			switch ($signArray){
				case 'survey':
					if($this->view->lang == 'en'){
						$addArray = array('sign' => 'survey',
									  'action' => $surveyArray[$sCount]['english_name'].$this->view->translate('His_(Survey)'), 
									  'date' => $surveyArray[$sCount]['date'],
									  'points' => $surveyArray[$sCount]['point_amount']);		
					}else{
						$addArray = array('sign' => 'survey',
									  'action' => $surveyArray[$sCount]['name'].$this->view->translate('His_(Survey)'), 
									  'date' => $surveyArray[$sCount]['date'],
									  'points' => $surveyArray[$sCount]['point_amount']);
					}
					$sCount++;
					break;
				case 'report':
					$addArray = array('sign' => 'report',
									  'action' => $reportArray[$rCount]['name'].$this->view->translate('His_(Report)'), 
									  'date' => $reportArray[$rCount]['date'],
									  'points' => $reportArray[$rCount]['point_amount']);
					$rCount++;
					break;
				case 'exchange':
					if(array_key_exists($exchangeArray[$iCount]['id'],$giftArray)){
						$addArray = array('sign' => 'exchange',
									  'action' => $giftArray[$exchangeArray[$iCount]['id']].$this->view->translate('His_(Exchange)'), 
									  'date' => $exchangeArray[$iCount]['date'],
									  'points' => abs($exchangeArray[$iCount]['point_amount']));
					}else{
						$addArray = array('sign' => 'exchange',
									  'action' => $this->view->translate('Jifentong').$this->view->translate('His_(Exchange)'), 
									  'date' => $exchangeArray[$iCount]['date'],
									  'points' => abs($exchangeArray[$iCount]['point_amount']));
					}	
					$iCount++;
					break;
				case 'post-campaign':
					$addArray = array('sign' => 'post-campaign',
									  'action' => $this->view->translate('Post-campaign_Survey').$this->view->translate('His_(Survey)'), 
									  'date' => $postCampaignSurveyArray[$pCount]['date'],
									  'points' => $postCampaignSurveyArray[$pCount]['point_amount']);
					$pCount++;
					break;
				case 'url-report':
					$addArray = array('sign' => 'url-report',
									  'action' => $urlreportArray[$uCount]['name'].$this->view->translate('His_(URLReport)'), 
									  'date' => $urlreportArray[$uCount]['date'],
									  'points' => $urlreportArray[$uCount]['point_amount']);
					$uCount++;
					break;
				case 'reward and event':
					if($rewardandeventArray[$eCount]['transaction_id'] == '7'){
						$addArray = array('sign' => 'event',
									  'action' => $this->view->translate('His_(Reward)'), 
									  'date' => $rewardandeventArray[$eCount]['date'],
									  'points' => $rewardandeventArray[$eCount]['point_amount']);
					}
					if($rewardandeventArray[$eCount]['transaction_id'] == '10'){
						$addArray = array('sign' => 'event',
									  'action' => $this->view->translate('His_(Event)'), 
									  'date' => $rewardandeventArray[$eCount]['date'],
									  'points' => $rewardandeventArray[$eCount]['point_amount']);
					}
					$eCount++;
					break;
				default:
					break;
			}
			if($signArray != null){
				$stuffsAll[$stuffCount++] = $addArray;
			}		
		}
		//paging
		$this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($stuffsAll));
		$paginator->setCurrentPageNumber($this->_curPage)
		->setItemCountPerPage($this->_rowsPerPage); 
		$this->view->paginator = $paginator;
		//set the No. inital value in view page
        $this->view->NoInitValue = ($this->_curPage-1)*$this->_rowsPerPage+1;
	}
}