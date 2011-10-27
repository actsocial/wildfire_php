<?php

class PointController extends MyController
{
	protected $_rowsPerPage = 50;
	protected $_curPage = 1;
	/**
	 * 点击积分兑换执行的方法
	 */
	function showAction()
	{
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("Show_Title");
		
		$db = Zend_Registry::get('db');
		$consumer = $this->_currentUser;
		//redeem points 在本日之前的30天内的积分不可用，
		$today = date("Y-m-d" , time());
		$this->view->totalPoints =  $db->fetchOne(
    		"SELECT sum(point_amount) FROM reward_point_transaction_record WHERE (consumer_id = :temp and date <:temp2) or (consumer_id = :temp and date >=:temp2 and transaction_id=4) ",
			array('temp' =>$consumer->id,'temp2'=>date("Y-m-d",strtotime("$today   -30   day")))
		);
		
		if (empty($this->view->totalPoints)) $this->view->totalPoints=0;
		//total completed campaigns
		$selectTotalCompletedCampaign = $db->select();
		$selectTotalCompletedCampaign->from('campaign_participation', 'count(*)')
		->join('campaign_invitation', 'campaign_participation.campaign_invitation_id = campaign_invitation.id', null)
		->where('campaign_participation.state = "COMPLETED"')
		->where('campaign_invitation.consumer_id = ?', $consumer->id);
		$this->view->completedCampaignAmount = $db->fetchOne($selectTotalCompletedCampaign);
		//total submitted reports
//		$selectTotalSubmittedReport = $db->select();
//		$selectTotalSubmittedReport->from('report', 'count(*)')
//		->where('state = "APPROVED"')
//		->where('consumer_id = ?', $consumer->id);
//		$this->view->submittedReportAmount = $db->fetchOne($selectTotalSubmittedReport);
		if($this->view->completedCampaignAmount < 1 ||($consumer->pest != null && $consumer->pest = 1)){
			$this->view->exchangePointsAuth = 'No';
			// finishing campaign
			$selectFinishingCampaign = $db->select();
			$selectFinishingCampaign->from('campaign_participation', null)
			->join('campaign_invitation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id', null)
			->join('campaign', 'campaign.id = campaign_invitation.campaign_id', array('name','id'))
			->where("campaign_participation.state = 'FINISHING'")
			->where('campaign_invitation.consumer_id = ?', $consumer->id)
			->order('campaign.expire_date desc');
			$this->view->finishingCampaign = $db->fetchAll($selectFinishingCampaign);		
			if($this->view->finishingCampaign == null || $this->view->finishingCampaign == ''){
			// campaign is still running	
				$selectRunningCampaign = $db->select();
				$selectRunningCampaign->from('campaign_participation', null)
				->join('campaign_invitation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id', null)
				->join('campaign', 'campaign.id = campaign_invitation.campaign_id', array('name','expire_date'))
				->where('campaign_invitation.consumer_id = ?', $consumer->id);
				$this->view->runningCampaign = $db->fetchAll($selectRunningCampaign);	
//				Zend_Debug::dump($this->view->runningCampaign);		
				if($this->view->runningCampaign != null){
					$this->view->runningCampaigName = $this->view->runningCampaign[0]['name'];
					//count days left before expire
					$expire_date = $this->view->runningCampaign[0]["expire_date"];
					$expire_date_year = substr($expire_date,0,4);
					$expire_date_month = substr($expire_date,5,2);
					$expire_date_day = substr($expire_date,8,2);
					$expire_date_hour = substr($expire_date,11,2);
					$expire_date_min = substr($expire_date,14,2);
					$expire_date_sec = substr($expire_date,17,2);
					$expire = mktime($expire_date_hour,$expire_date_min,$expire_date_sec,$expire_date_month,$expire_date_day,$expire_date_year); 			
					$currentTime = time(); 
					$this->view->daysLeftBeforeExpire = round(($expire - $currentTime)/3600/24);
					if ($this->view->daysLeftBeforeExpire  <= 0){
						$this->view->daysLeftBeforeExpire  = 0;
					}
				}else{
			// not join any campaigns at all
					$selectInvitationCampaign = $db->select();
					$selectInvitationCampaign->from('campaign_invitation', 'campaign_id')
					->where("state = 'ACCEPTED'")
					->where('consumer_id = ?', $consumer->id);
					$invitationCampaigId = $db->fetchAll($selectInvitationCampaign);
					if($invitationCampaigId == null || $invitationCampaigId == ''){
						$this->view->notJoinAnyCampaigns = true;
					}
				}
			}
//			Zend_Debug::dump($this->view->finishingCampaign);
			return;
		}
		//
		$exchangeRecordModel = new ExchangeRecord();
		$exchangeRecord = $exchangeRecordModel->fetchRow('consumer_id = '.$consumer->id);
		if($exchangeRecord == null){
			$config = Zend_Registry::get('config');
	    	$key = $config->mcrypt->tripledes->key; 
			$td = mcrypt_module_open('tripledes', '', 'ecb', ''); 
			$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND); 
			mcrypt_generic_init($td, $key, $iv); 
			$this->view->site = base64_encode(mcrypt_generic($td, "xingxinghuo"));
			$this->view->userName = base64_encode(mcrypt_generic($td, $consumer['email']));
			$this->view->password = base64_encode(mcrypt_generic($td, $consumer['password']));
			if($consumer['recipients_name'] != null && $consumer['recipients_name'] != ''){
				$this->view->trueName = base64_encode(mcrypt_generic($td, $consumer['recipients_name']));
			}else{
				$this->view->trueName = base64_encode(mcrypt_generic($td, $consumer['name']));
			}
			mcrypt_generic_deinit($td); 
	        mcrypt_module_close($td);
	        
	        $this->view->form = new Points99Form();
	        $this->view->form->setDefault('site',$this->view->site);
	        $this->view->form->setDefault('userName',$this->view->userName);
	        $this->view->form->setDefault('password',$this->view->password);
	        $this->view->form->setDefault('realName',$this->view->trueName);
		}else{
			$this->view->exchangeRecord = true;
		}
//        Zend_Debug::dump($this->view->password);
	}
	/**
	 * $this->view->totalPoints 属性在登录成功的页面显示。
	 */
	function exchangeAction(){
		$this->view->title = $this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("Exchange_Title");
		
		$db = Zend_Registry::get('db');
		$consumer = $this->_currentUser;
		
		$this->view->totalPoints =  $db->fetchOne(
    		"SELECT sum(point_amount) FROM reward_point_transaction_record WHERE consumer_id = :temp",
			array('temp' =>$consumer->id)
		);
		if (empty($this->view->totalPoints)) $this->view->totalPoints=0;
		
//		Zend_Debug::dump($consumer);
	}
	
	function adminshowAction(){
		$this->_helper->layout->setLayout("layout_admin");
		$this->view->activeTab = 'Points';
		
		$db = Zend_Registry::get('db');
		$selectAmountPoint = $db->select();
		$selectAmountPoint->from('consumer', array('id', 'email', 'name', 'recipients_name'))
		->join('reward_point_transaction_record', 'consumer.id = reward_point_transaction_record.consumer_id', 'sum(point_amount)')
		->where('point_amount > 0')
		->group('reward_point_transaction_record.consumer_id')
		->order('sum(point_amount) desc');
		if($this->_request->getParam('pest') == '0'){
			$selectAmountPoint->where('consumer.pest is null or consumer.pest != 1');
			$this->view->title = $this->view->translate('Admin_Show_Points');
		}else{
			$selectAmountPoint->where('consumer.pest = 1');
			$this->view->title = $this->view->translate('Admin_Show_Pest_Points');
		}
		$this->view->amountPoints = $db->fetchAll($selectAmountPoint);
//		Zend_Debug::dump($this->view->amountPoints);
		$selectTotaltPoints = $db->select();
		$selectTotaltPoints->from('reward_point_transaction_record','sum(point_amount)')
		->where('point_amount > 0')
		->join('consumer', 'consumer.id = reward_point_transaction_record.consumer_id', null);
		if($this->_request->getParam('pest') == '0'){
			$selectTotaltPoints->where('consumer.pest is null or consumer.pest != 1');
		}else{
			$selectTotaltPoints->where('consumer.pest = 1');
		}
		$this->view->totaltPoints = $db->fetchOne($selectTotaltPoints);
		
		$selectExchangePoint = $db->select();
		$selectExchangePoint->from('consumer', array('id'))
		->join('reward_point_transaction_record', 'consumer.id = reward_point_transaction_record.consumer_id', 'sum(point_amount)')
		->where('point_amount < 0')
		->group('reward_point_transaction_record.consumer_id')
		->order('sum(point_amount)');
		if($this->_request->getParam('pest') == '0'){
			$selectExchangePoint->where('consumer.pest is null or consumer.pest != 1');
		}else{
			$selectExchangePoint->where('consumer.pest = 1');
		}
		$exchangePoints = $db->fetchAll($selectExchangePoint);
		$this->view->exchangePointArray = array();
		$this->view->totalExchangePoints = 0;
		foreach($exchangePoints as $exchangePoint){
			$this->view->exchangePointArray[$exchangePoint['id']] = abs($exchangePoint['sum(point_amount)']);
			$this->view->totalExchangePoints += abs($exchangePoint['sum(point_amount)']);
		}
//		Zend_Debug::dump($this->view->exchangePointArray);
		
		$selectTotalPoll = $db->select();
		$selectTotalPoll->from('poll_participation', array('consumer_id' ,'count(*)'))
		->group('consumer_id');
		$totalPolls = $db->fetchAll($selectTotalPoll);
		$this->view->totalPollsArray = array();
		foreach($totalPolls as $totalPoll){
			$this->view->totalPollsArray[$totalPoll['consumer_id']] = $totalPoll['count(*)'];
		}
//		Zend_Debug::dump($this->view->totalPollsArray);
		
		$selectTotalReport = $db->select();
		$selectTotalReport->from('report', array('consumer_id' ,'count(*)'))
		->group('consumer_id');
		$totalReports = $db->fetchAll($selectTotalReport);
		$this->view->totalReportsArray = array();
		foreach($totalReports as $totalReport){
			$this->view->totalReportsArray[$totalReport['consumer_id']] = $totalReport['count(*)'];
		}
//		Zend_Debug::dump($this->view->totalReportsArray);
	}
	
	function adminshowexchangehistoryAction(){
		$this->_helper->layout->setLayout("layout_admin");
		$this->view->activeTab = 'Points';
		
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('exchange_record','*')
		->join('consumer', 'consumer.id = exchange_record.consumer_id', array('email', 'name'))
		->where('create_date>date_sub(now(),interval 2 day)')
		->where('exchange_point_amount != 0')
		->order('exchange_record.create_date desc');
		
		$this->view->records = $db->fetchAll($select);

//		Zend_Debug::dump($this->view->records);
	}
	
	function adminredeemlistAction(){
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("Redeem");
		$this->view->activeTab = 'Redeem';
		$this->_helper->layout->setLayout("layout_admin");
		
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('product_order', array('state', 'amount', 'create_date', 'id','handle_date'))
		->join('reward_point_transaction_record', 'reward_point_transaction_record.id = product_order.reward_point_transaction_record_id', 'point_amount as point')
		->join('consumer', 'consumer.id = product_order.consumer_id', array('email', 'phone', 'address1', 'recipients_name', 'city', 'province'))
		->join('product', 'product.id = product_order.product_id', array('name', 'url', 'category'))
		->order('product_order.create_date desc');
		$this->view->redeemList = $db->fetchAll($select);
		$this->view->total = count($this->view->redeemList); 
		//2011-06-09 redeem analysis 
		$formData = $this->_request->getPost();
		$filter_state = '';
		$redeems = array();
		$filter_status = array ('NEW' => 0,'START' => 0,'FINISHED' => 0,'UNAPPROVED' => 0);
		if( $this->_request->getParam('state') ){
			$filter_status[$this->_request->getParam('state')] = 1;
			$filter_state = $this->_request->getParam('state');
		}
		if( $this->_request->getPost() ){
			$filter_status[$formData['filter_status']] = 1;
			$filter_state = $formData['filter_status'];
		}
		if ( !strlen($filter_state) ) {
			$filter_state = 'ALL';
		}
		
		$this->view->status = $filter_status;
		$this->view->filter_state  = $filter_state;
		
		$newItems      = 0;
		$finishedItems = 0;
		$startItems    = 0;
		$rejectItems   = 0;
		foreach ( $this->view->redeemList as $redeem ){
			if($redeem['state'] == $filter_state){
				$redeems[] = $redeem;
			}elseif ($filter_state == 'ALL'){
				$redeems[] = $redeem;
			}
			if ( $redeem['state'] == 'NEW' ) {
				$newItems++ ;
			}
		    if ( $redeem['state'] == 'START' ) {
				$startItems++ ;
			}
			if ( $redeem['state'] == 'FINISHED' ) {
				$finishedItems++ ;
			}
			if ( $redeem['state'] == 'UNAPPROVED' ) {
				$rejectItems++ ;
			}
		}
		$this->view->redeemList = $redeems;
		$this->view->newItems = $newItems;
		$this->view->finishedItems = $finishedItems;
		$this->view->startItems = $startItems;
		$this->view->rejectItems = $rejectItems;

		// get current page(default page = 1)
		if($this->_request->getParam('page')){
			$this->_curPage = $this->_request->getParam('page');
		}
		//paging
		$this->view->controller = $this->_request->getControllerName();
		$this->view->action = $this->_request->getActionName();
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($this->view->redeemList));
			$paginator->setCurrentPageNumber($this->_curPage)
			->setItemCountPerPage($this->_rowsPerPage); 
			$this->view->paginator = $paginator; 
			
	//		Zend_Debug::dump($this->view->paginator);
		//set the No. inital value in view page
		$this->view->NoInitValue = ($this->_curPage-1)*$this->_rowsPerPage+1;
	}
	function adminsetselectedorderAction()
	{	
		$type = $this->_request->getParam('type');
		if($type == 'orderState'){
			$idStr = $this->_request->getParam('orderids');
			$idStrArray = explode(',',$idStr);
			$productOrderModel = new ProductOrder();
			foreach($idStrArray as $idAndState){
				if(null == $idAndState || '' == $idAndState) {
					continue;
				}
				$idAndStateArray = explode('@',$idAndState);
				$productOrder = $productOrderModel->fetchRow('id = '.$idAndStateArray[0]);
				// consumer cancel order
				if($productOrder->state == 'CANCEL'){
					continue;
				}
				// confirm used point in table 'reward_point_transaction_record'
				$rewardPointTransactionRecordModel = new RewardPointTransactionRecord();
				$rewardPointTransactionRecord = $rewardPointTransactionRecordModel->fetchRow('id = '.$productOrder->reward_point_transaction_record_id);
				if($rewardPointTransactionRecord == null){
					continue;
				}else{
					$productModel = new Product();
					$product = $productModel->fetchRow('id = '.$productOrder->product_id);
					if($product == null){
						continue;
					}else{
						if($idAndStateArray[1] == 'UNAPPROVED'){
							$rewardPointTransactionRecord->point_amount = 0;	
						}
					}
					$rewardPointTransactionRecord->save();
				}
				// change order state
				$currenttime = date("Y-m-d H:i:s");
				$productOrder->state = $idAndStateArray[1];
				$productOrder->handle_date= $currenttime;
				$productOrder->save();
			}
		}
		$this->_helper->json('Success');
	}
	
	
	function adminrankAction(){
        $rankModel     = new Rank();
		$rankModel->changeAllConsumersRank();
		$this->_helper->layout->setLayout("layout_admin");
		
		//$this->_helper->layout->setLayout("layout_admin");	
	}
	
	/**
	 * export the current data to csv
	 */
	function adminredeemexportAction(){
		$state = $this->_request->getParam('state');
		$this->_helper->layout->disableLayout ();
		
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('product_order', array('state', 'amount', 'create_date', 'id'))
		->join('reward_point_transaction_record', 'reward_point_transaction_record.id = product_order.reward_point_transaction_record_id', 'point_amount as point')
		->join('consumer', 'consumer.id = product_order.consumer_id', array('email', 'phone', 'address1', 'recipients_name', 'city', 'province'))
		->join('product', 'product.id = product_order.product_id', array('name', 'url', 'category'))
		->order('product_order.create_date desc');
		if ($state != 'ALL') {
			$select->where ('product_order.state = "'.$state.'"') ;
		}
		$this->view->redeemList = $db->fetchAll($select);
		
		$file = date ( 'Y-m-d_H_i_s' ) . "redeem.csv";
		//die($file);
		
		if ($this->view->redeemList) {
			$i = 1;
			foreach ( $this->view->redeemList as $val ) {
				$campaignUsers [] = array ($i, $val ['name'], $val ['url'] ,$val ['email']  ,  $val ['phone'], $val ['recipients_name'], $val ['province'], $val ['city'], $val ['address1'], $val ['phone'], $val ['id'], $val ['create_date'] ,$val ['point'] );
				$i++;
			}
			$header = array ('No.', 'Product Name',' Url', 'Email' ,'Telephone', 'Recipients_name', 'Province', 'City', 'Address', 'Phone', 'UserID', 'create_date','Point' );
			
			$handle = fopen ( dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/public/csv/' . $file, "w" );
			fwrite($handle, "\xEF\xBB\xBF");
			fputcsv ( $handle, $header );
			foreach ( $campaignUsers as $line ) {
				
				fputcsv ( $handle, $line );
			}
			fclose ( $handle );
		}
		
		$this->view->file = file_exists ( dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/public/csv/' . $file ) ? dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/public/csv/'.$file : false;
		$this->view->filename = $file;
	}

}