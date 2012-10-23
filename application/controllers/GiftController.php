<?php
require_once 'Pagination/Pagination.php';
include_once 'fckeditor_php5.php';
class GiftController extends MyController
{
	protected $_rowsPerPage = 9;
	protected $_curPage = 1;
	/**
	 * listAction方法是列出全部的礼品
	 * Enter description here ...
	 */
	function listAction(){
		
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("GIFT_LIST");
		
		$this->view->consumer = $this->_currentUser;

		$productModel = new Product();
		
		$today = date("Y-m-d" , time());
		
		if($this->_request->getParam('t') != null && $this->_request->getParam('t') == 'mine'){

			$db = Zend_Registry::get('db');
//			$selectAmountPoint = $db->select();
//	    	$selectAmountPoint->from('reward_point_transaction_record', 'SUM(point_amount)')
//			->where("consumer_id = ?", $this->_currentUser->id);
//			$amountPoints = (int)$db->fetchOne($selectAmountPoint);
			$amountPoints=$db->fetchOne(
    		"SELECT sum(point_amount) FROM reward_point_transaction_record WHERE (consumer_id = :temp and date <:temp2) or (consumer_id = :temp and date >=:temp2 and transaction_id=4) ",
			array('temp' =>$this->_currentUser->id,'temp2'=>date("Y-m-d",strtotime("$today   -30   day"))));
			$products = $productModel->fetchAll('point <= '.$amountPoints.' and state ="STOCK"', 'point desc')->toArray(); 
		}else{
			if($this->_request->getParam('p2') != null && (int)$this->_request->getParam('p2') > 0){
				$products = $productModel->fetchAll('point >= '.$this->_request->getParam('p1').' and point <= '.$this->_request->getParam('p2').' and state ="STOCK"', 'point')->toArray(); 			

			}else{
				if($this->_request->getParam('t') != null && $this->_request->getParam('t') != 'none'){
					if($this->_request->getParam('t')=="NEW"){
						//添加最新上架 Bruce.Liu
						$products = $productModel->fetchAll(' state ="STOCK" ','id desc',18,'point')->toArray();
//						Zend_Debug::dump($products);die();
					}else{
						$products = $productModel->fetchAll("category = '".$this->_request->getParam('t')."'".' and state ="STOCK"', 'point')->toArray();
					}
				}else{
					$products = $productModel->fetchAll('state ="STOCK"', 'point')->toArray(); 
				}
			}
			
		}
		//2011-04-14 ham.bao page links modification
		if($this->_request->getParams('t')!=NULL){
			$this->view->t = $this->_request->getParam('t');
		}
		if($this->_request->getParam('p2') != null){	    
			$this->view->p1 = $this->_request->getParam('p1');
			$this->view->p2 = $this->_request->getParam('p2');
		}
		// get total points
		$db = Zend_Registry::get('db');
		if ($this->_currentUser!=null){
//			$selectAmountPoint = $db->select();
//	    	$selectAmountPoint->from('reward_point_transaction_record', 'SUM(point_amount)')
//			->where("consumer_id = ?", $this->_currentUser->id);
			$this->view->amountPoints = $db->fetchOne(
    		"SELECT sum(point_amount) FROM reward_point_transaction_record WHERE (consumer_id = :temp and date <:temp2) or (consumer_id = :temp and date >=:temp2 and transaction_id=4) ",
			array('temp' =>$this->_currentUser->id,'temp2'=>date("Y-m-d",strtotime("$today   -30   day"))));;	
		}
		
		if($this->_request->getParam('page'))
        {
        	$this->_curPage = $this->_request->getParam('page');
        }
        //paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
//Zend_Debug::dump($products);die;
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($products));
		$paginator->setCurrentPageNumber($this->_curPage)
		->setItemCountPerPage($this->_rowsPerPage); 
		$this->view->products = $paginator; 
        //set the No. inital value in view page
        $this->view->NoInitValue = ($this->_curPage-1)*$this->_rowsPerPage+1;
	}
	
	function addtocartAction(){
		
		if ($this->_request->isPost()) {
			$postData = $this->_request->getPost();
//			Zend_Debug::dump($postData);
			$id = $postData['product_id'];
			$name = $postData['product_name'];
			$point = $postData['product_point'];
		
		$cartNamespace = new Zend_Session_Namespace('Cart');
		$existed = false;
		$list = $cartNamespace->list;
		if ($list == null){
			$list = array();
		}else{
			foreach ($list as $gift){
				if ($gift['id']==$id){
					$pos = array_search($gift,$list);
					$existed = true;
				}
			}
		}
		
		if (!$existed){
			$list[count($list)+1] = array("id"=>$id, "amount"=>1,"name"=>$name,"point"=>$point);
		}
		$cartNamespace->list = $list;
//		Zend_Debug::dump($list);
		$this->_redirect('gift/cart');
		}
	}
	
	function cartAction(){
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("GIFT_CART");
		
		$cartNamespace = new Zend_Session_Namespace('Cart');
		if ($cartNamespace->list == null){
			$cartNamespace->list = array();
		}
		$sum = 0;
		foreach ($cartNamespace->list as $gift){
			$sum += $gift['point'] *$gift['amount']; 
		}
			
		$this->view->gifts = $cartNamespace->list;
		$this->view->sum = $sum;
		// check the point
		$db = Zend_Registry::get('db');
//		$selectAmountPoint = $db->select();
//    	$selectAmountPoint->from('reward_point_transaction_record', 'SUM(point_amount)')
//		->where("consumer_id = ?", $this->_currentUser->id);
		$today = date("Y-m-d" , time());
		$amountPoints = $db->fetchOne(
    		"SELECT sum(point_amount) FROM reward_point_transaction_record WHERE (consumer_id = :temp and date <:temp2) or (consumer_id = :temp and date >=:temp2 and transaction_id=4) ",
			array('temp' =>$this->_currentUser->id,'temp2'=>date("Y-m-d",strtotime("$today   -30   day"))));
		
		$amountSelectedProductPoint = 0;
		foreach($cartNamespace->list as $product){
			$selectSelectedProductPoint = $db->select();
    		$selectSelectedProductPoint->from('product', 'point')
			->where("id = ". $product['id']);
			$selectedProductPoint = (int)$db->fetchOne($selectSelectedProductPoint);	
			$amountSelectedProductPoint += $product['amount']*$selectedProductPoint;
		}
		if($amountSelectedProductPoint > $amountPoints){
			$this->view->notQualified = true;
			$this->view->notHaveEnoughPoint = true;
		}
		// check redeem condition
		$selectTotalCompletedCampaign = $db->select();
		$selectTotalCompletedCampaign->from('campaign_participation', 'count(*)')
		->join('campaign_invitation', 'campaign_participation.campaign_invitation_id = campaign_invitation.id', null)
		//2011-03-07 ham.bao redeem enable
		//->where('campaign_participation.state = "COMPLETED"')
		->where('campaign_invitation.consumer_id = ?', $this->_currentUser->id);
		$this->view->completedCampaignAmount = $db->fetchOne($selectTotalCompletedCampaign);
	
		if($this->view->completedCampaignAmount < 1 || ($this->_currentUser->pest != null && $this->_currentUser->pest == 1)){
			$this->view->notQualified = true;
			$consumer = $this->_currentUser;
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
				->where('campaign_invitation.consumer_id = ?', $consumer->id)
				->where('campaign_participation.state != "FINISHING"')
				->where('campaign_participation.state != "COMPLETED"');
				$this->view->runningCampaign = $db->fetchAll($selectRunningCampaign);		
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
//			if($this->view->completedCampaignAmount < 1){
//				$selectNotFinishedCampaignId = $db->select();
//				$selectNotFinishedCampaignId->from('campaign_invitation', 'campaign_id')
//				->join('campaign_participation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id', null)
//				->where('campaign_invitation.consumer_id = ?', $this->_currentUser->id)
//				->where("campaign_participation.state != 'COMPLETED'");
//				$this->view->notFinishedCampaignId = $db->fetchOne($selectNotFinishedCampaignId);		
//				$this->view->notFinishedCampaignSurvey = true;
//			}
			if($this->_currentUser->pest != null && $this->_currentUser->pest == 1){
				$this->view->isPest = true;
			}
		}
	}
	
	function listorderAction(){
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("GIFT_ORDER");
		
		$db = Zend_Registry::get('db');
		$select =  $db->select();
		$select->from('product', array('product_id', 'name'))
		->join('product_order', 'product_order.product_id = product.id', array('id', 'create_date', 'state', 'amount'))
		->join('reward_point_transaction_record', 'product_order.reward_point_transaction_record_id = reward_point_transaction_record.id', 'point_amount as point')
		->where('product_order.consumer_id = ?', $this->_currentUser->id)
		->order('product_order.create_date desc');
		$this->view->orders = $db->fetchAll($select);
//		Zend_Debug::dump($this->view->orders);
		
	}
	
	function deleteorderAction(){
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("GIFT_DELETE_ORDER");
		
		$orderid = $this->_request->getParam('orderid');
		$productorderModel = new ProductOrder();
		$order = $productorderModel->fetchRow('id = '.$orderid);
		if($order == null || $order->state != 'NEW' || $order->consumer_id != $this->_currentUser->id){
			$this->view->message = $this->view->translate("Gift_delete_order_fail");
			return;
		}else{
			// reward_point_transaction_record table
			$rewardPointTransactionRecordModel = new RewardPointTransactionRecord();
			$rewardPointTransactionRecord = $rewardPointTransactionRecordModel->fetchRow('id = '.$order->reward_point_transaction_record_id);
			$rewardPointTransactionRecord->point_amount = 0;
			$rewardPointTransactionRecord->save();
			// product_order table
			$order->state = 'CANCEL';
			$order->save();
			// roll back...
		}
		$this->view->message = $this->view->translate("Gift_delete_order_successfully");
	}
	
	function deletegiftAction(){
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("GIFT_DELETE_CART");
		
		$id = $this->_request->getParam('id');
		if ($id>0){
			$cartNamespace = new Zend_Session_Namespace('Cart');
			$list = $cartNamespace->list;
//			Zend_Debug::dump($list);
			foreach ($list as $gift){
				if ($gift['id']==$id){
					$pos = array_search($gift,$list);
					unset($list[$pos]);
				}
			}
			$cartNamespace->list = $list;
//			Zend_Debug::dump($list);
		}
		
		$this->_redirect('gift/cart');
	}
	
	
	function clearcartAction(){

		$cartNamespace = new Zend_Session_Namespace('Cart');
		unset($cartNamespace->list);
		$this->_redirect('gift/cart');
	}
	
	function confirmcartAction(){
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("GIFT_CONFIRM_CART");
		
		$cartNamespace = new Zend_Session_Namespace('Cart');
		if($cartNamespace->list == null){
			$this->_redirect('gift/list');
		}
		$this->view->email = $this->_currentUser->email;
		if ($this->_request->isPost()) {
			
			$postData = $this->_request->getPost();
			$ids = $postData['id'];		
			
			$list = array();
			$productModel = new Product();			
			
			foreach ($ids as $id){
				$product = $productModel->find($id)->current();
				if ($postData['amount_'.$id]>=1){
					$list[count($list)+1] = array("id"=>$id, "amount"=>floor($postData['amount_'.$id]),"name"=>$product->name,"point"=>$product->point);
				}else{
					if ($postData['amount_'.$id]==0){
						unset($list[count($list)+1]);
					}else{
						$list[count($list)+1] = array("id"=>$id, "amount"=>1, "name"=>$product->name,"point"=>$product->point);
					}					
				}				
			}
			$cartNamespace->list = $list;
		
		
			if ($postData['type']=='modify'){
				$this->_redirect('gift/cart');
			}
		
		}
		//edit ConsumerContactForm();
		$consumerModel = new Consumer();
		$form = new ConsumerContactForm();
		$this->view->gifts = $cartNamespace->list; 
		$consumer = $consumerModel->find($this->_currentUser->id)->current();
		$form->populate($consumer->toArray());
		$langNamespace = new Zend_Session_Namespace('Lang');
		$this->view->language = $langNamespace->lang;
		// zh city
		if($consumer["city"]!= NULL && $consumer["province"]!= NULL ){
			$this->view->city = $consumer["city"];
			$this->view->province = $consumer["province"];
		}
		// en city
		if($consumer["city"]!= NULL && $consumer["province"]== NULL ){
			$this->view->encity = $consumer["city"];
		}
		$this->view->form = $form;
		
		// total point
		$sum = 0;
		foreach ($cartNamespace->list as $gift){
			$sum += $gift['point'] *$gift['amount']; 
		}		
		$this->view->sum = $sum;	
		$db = Zend_Registry::get('db');
		$selectUsablePoints = $db->select();
    	$selectUsablePoints->from('reward_point_transaction_record', 'SUM(point_amount)')
		->where("consumer_id = ?", $this->_currentUser->id);
		$usablePoints = (int)$db->fetchOne($selectUsablePoints);
		if ($sum>$usablePoints){
				$this->_redirect('gift/cart');
		}
		
		// err message
		$messageArray = $this->_flashMessenger->getMessages();
		if($messageArray != null){
			$this->view->showMessage = $messageArray[0];
		}	
	}

	function redeemAction(){
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("GIFT_REDEEM");
		
		if ($this->_request->isPost()) {
			$formData = $this->_request->getPost();	
			$cartNamespace = new Zend_Session_Namespace('Cart');	
			if($cartNamespace->list == null){
				$this->_redirect('gift/list');
				return;
			}
			// validate consumer info
			$consumerModel = new Consumer();
			$consumer = $consumerModel->fetchRow("email = '".$this->_currentUser->email."' and password = MD5('".$formData['password']."')");
			if($consumer == null){
				$this->_flashMessenger->addMessage($this->view->translate("Gift_consumer_info_incorrect"));
				$this->_flashMessenger->addMessage(true);
				$this->_redirect('gift/confirmcart');
				return;
			}
			// check redeem condition
			$db = Zend_Registry::get('db');
			$selectTotalCompletedCampaign = $db->select();
			$selectTotalCompletedCampaign->from('campaign_participation', 'count(*)')
			->join('campaign_invitation', 'campaign_participation.campaign_invitation_id = campaign_invitation.id', null)
			//2011-03-07 ham.bao redeem enable
			//->where('campaign_participation.state = "COMPLETED"')
			->where('campaign_invitation.consumer_id = ?', $this->_currentUser->id);
			$this->view->completedCampaignAmount = $db->fetchOne($selectTotalCompletedCampaign);
	
//			$selectTotalSubmittedReport = $db->select();
//			$selectTotalSubmittedReport->from('report', 'count(*)')
//			->where('state = "APPROVED"')
//			->where('consumer_id = ?', $this->_currentUser->id);
//			$this->view->submittedReportAmount = $db->fetchOne($selectTotalSubmittedReport);		
			if($this->view->completedCampaignAmount < 1 || ($this->_currentUser->pest != null && $this->_currentUser->pest == 1)){
				$this->_flashMessenger->addMessage($this->view->translate("Gift_can_not_redeem_gift"));
				$this->_flashMessenger->addMessage(true);
				$this->_redirect('gift/confirmcart');
				return;
			}
			// check the point
			$selectUsablePoints = $db->select();
    		$selectUsablePoints->from('reward_point_transaction_record', 'SUM(point_amount)')
			->where("consumer_id = ?", $this->_currentUser->id);
			$usablePoints = (int)$db->fetchOne($selectUsablePoints);
			
			$amountSelectedProductPoint = 0;
			foreach($cartNamespace->list as $product){
				$selectSelectedProductPoint = $db->select();
	    		$selectSelectedProductPoint->from('product', 'point')
				->where("id = ". $product['id']);
				$selectedProductPoint = (int)$db->fetchOne($selectSelectedProductPoint);	
				$amountSelectedProductPoint += $product['amount']*$selectedProductPoint;
			}
			if($amountSelectedProductPoint > $usablePoints){
				$this->_flashMessenger->addMessage($this->view->translate("Gift_have_no_enough_point"));
				$this->_flashMessenger->addMessage(true);
				$this->_redirect('gift/confirmcart');
				return;
			}
			// save shipping info
			$consumerModel = new Consumer();
			$id = $this->_currentUser->id;
			$consumer = $consumerModel->find($id)->current();
			$consumer->recipients_name = $formData['recipients_name'];
			$consumer->phone = $formData['phone'];
			$consumer->address1 = $formData['address1'];
			$consumer->postalcode = $formData['postalcode'];
			if($formData['city'] !=null && $formData['province'] != null){
				$consumer->city = $formData['city'];
				$consumer->province = $formData['province'];
			}
			if($formData['englishcity'] !=null){
				$consumer->city = $formData['englishcity'];
				$consumer->province = null;
			}
			if($formData['province'] == '' && $formData['englishcity'] == null){
				$consumer->city = null;
				$consumer->province = null;
			}
			$consumer->save();
			// save exchange records
			$currentTime = date("Y-m-d H:i:s");
			$rewardPointTransactionRecordModel = new RewardPointTransactionRecord();
			$prodcutOrderModel = new ProductOrder();
			$notificationModel = new Notification();
			$total_redeem_point = 0;
			foreach($cartNamespace->list as $product){
				// add records to reward_point_transaction_record table			
				$rewardPointTransactionRecord = array("consumer_id" => $this->_currentUser->id,
													  "DATE" => $currentTime,
													  "transaction_id" => '4',
													  "point_amount" => -$product['amount']*$product['point']);  
				$transactionRecordId = $rewardPointTransactionRecordModel->insert($rewardPointTransactionRecord);	
				// add records to product_order table
				$prodcutOrder = array('consumer_id' => $this->_currentUser->id,
									  'product_id' => $product['id'],
									  'create_date' => $currentTime,
									  'state' => 'NEW',
									  'reward_point_transaction_record_id' => $transactionRecordId,
									  'amount' => $product['amount']);
				$prodcutOrderId = $prodcutOrderModel->insert($prodcutOrder);
				// roll back if an exception occurred
				// ...
				$total_redeem_point += $product['amount']*$product['point'];
			}
			// add notification
			$notificationModel->createRecord("REDEEM_POINT",$this->_currentUser->id,$total_redeem_point);
			$this->paidGifts = $cartNamespace->list;
			$cartNamespace->list = null;
			// show redeem.phtml with "... Successfully"
			$this->_flashMessenger->addMessage("Gift_submit_orders_successfully");	
			$this->_flashMessenger->addMessage(false);
			$this->_flashMessenger->addMessage($this->paidGifts);
			$this->_redirect('gift/thankyou');	
		}else{
			$this->_redirect('gift/list');
		}	
	}
	function thankyouAction(){
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("GIFT_REDEEM");
		$messageArray = $this->_flashMessenger->getMessages();
		if($messageArray == null){
			$this->_redirect('gift/list');
		}else{
			$this->view->showMessage = $this->view->translate($messageArray[0]);
			$this->view->isErr = $messageArray[1];
			if(count($messageArray) > 2){
				$this->view->gifts = $messageArray[2];
			}
			foreach($messageArray as $message){
				$this->_flashMessenger->addMessage($message);
			}
		}
	}
	function descriptionAction() {
		$this->view->title = $this->view->translate("Wildfire")." - ".$this->view->translate("GIFT_REDEEM");
		
		$id = $this->_request->getParam('id');
		if ($id>0){
			$productModel = new Product();
			$this->view->product = $productModel->find($id)->current();
		}
	}
	function adminlistgiftsAction(){
		$this->_helper->layout->setLayout("layout_admin");
		$this->view->activeTab = 'Redeem';
		//2011-09-20 ham.bao filter the gift by status
		if( $this->_request->getParam('status')){
			$this->view->status = $this->_request->getParam('status');
			if ($this->view->status != 'ALL'){
				$status = '  state = "'. $this->view->status .'"';
			}else{
				$status = '';
			}
		}else{
			$this->view->status = "ALL";
			$status = '';
		}
		if( $this->_request->getParam('t')){
			$this->view->t = $this->_request->getParam('t');
			if ($this->view->t != 'ALL'){
				$t = "category = '".$this->_request->getParam('t')."'";
			}else{
				$t = '';
			}
		}else{
			$this->view->t = 'ALL';
			$t = '';
		}
		
		if( $this->_request->getParam('p1')){
			$this->view->p1 = $this->_request->getParam('p1');
		}else{
			$this->view->p1  = 0;
			$p1 = '';
		}
		
		if( $this->_request->getParam('p2')){
			$this->view->p2 = $this->_request->getParam('p2');
			if ($this->view->p2 != 0){
				$p2 = 'point >= '.$this->_request->getParam('p1').' and point <= '.$this->_request->getParam('p2');
			}else{
				$p2 = '';
			}
		}else{
			$this->view->p2  = 0;
			$p2 = '';
		}
		$where = '';
		if( $status != '' ){
			$where = ' and '.$status;
		}
		if($t != ''){
			$where .= ' and '.$t;
		}
		if($p2 != ''){
			$where .= ' and '.$p2;
		}
		$productModel = new Product();
		$this->view->products = $productModel->fetchAll('state != "EXPIRED" ' . $where, 'id')->toArray(); 
		//2011-09-20 ham.bao filter the gift by status
//		$productModel = new Product();
//		
////		if($this->_request->getParam('t') != null && $this->_request->getParam('t') == 'mine'){
////			$db = Zend_Registry::get('db');
//////			$selectAmountPoint = $db->select();
//////	    	$selectAmountPoint->from('reward_point_transaction_record', 'SUM(point_amount)')
//////			->where("consumer_id = ?", $this->_currentUser->id);
////			$today = date("Y-m-d" , time());
////			$amountPoints = $db->fetchOne(
////    		"SELECT sum(point_amount) FROM reward_point_transaction_record WHERE (consumer_id = :temp and date <:temp2) or (consumer_id = :temp and date >=:temp2 and transaction_id=4) ",
////			array('temp' =>$this->_currentUser->id,'temp2'=>date("Y-m-d",strtotime("$today   -30   day"))));
////			$this->view->products = $productModel->fetchAll(' point <= '.$amountPoints . $status, 'point desc')->toArray(); 
////		}else{
////			if($this->_request->getParam('p2') != null && (int)$this->_request->getParam('p2') > 0){
////				$this->view->products = $productModel->fetchAll('point >= '.$this->_request->getParam('p1').' and point < '.$this->_request->getParam('p2') . $status, 'point')->toArray(); 
////			}else{
////				if($this->_request->getParam('t') != null && $this->_request->getParam('t') != 'none'){
////					$this->view->products = $productModel->fetchAll("category = '".$this->_request->getParam('t')."'" . $status, 'point')->toArray();
////				}else{
////					$this->view->products = $productModel->fetchAll('state != "EXPIRED" ' . $status, 'id')->toArray(); 
////				}
////			}
////		}
//		
		
		$this->view->fc = Zend_Controller_Front::getInstance();
//		Zend_Debug::dump($this->view->products);
	}
	function admindelAction(){
		if($this->_request->getParam('product_id')!=null&&$this->_request->getParam('product_id')!=''){
			$product_up=new Product();
			$result=$product_up->update(array('state'=>'OOS'),"id=".$this->_request->getParam('product_id'));
			if($result){
				/**
				 * render _forward _redirect 三者之间的区别
				 * 不指定render结果： {当前Module}/{当前Controller}/{当前Action}.phtml
				 * $this->render('bar') ;结果： {当前Module}/{当前Controller}/bar.phtml
				 */
				//$this->_redirect('gift/adminlistgifts');
				$this->render("adminlistgifts");
//				$this->_forward('');
//				$this->_redirect($url, $options);
			}else{
				
			}
		}
	}
	function admineditgiftAction(){
		$this->_helper->layout->setLayout("layout_admin");
		
		if ($this->_request->isPost()) {
			$formData = $this->_request->getPost();
//			Zend_Debug::dump($formData);
			$productModel = new Product();
			$product = $productModel->fetchRow("id = ".$formData['id']);
			$product->product_id = $formData['product_id'];
			$product->name = $formData['name'];
			$product->point = $formData['point'];
			$product->state = $formData['state'];
			$product->source = $formData['source'];
			$product->category = $formData['category'];
			$product->subcategory = $formData['subcategory'];
			$product->description = $formData['description'];
			$product->url = $formData['url'];
			$product->long_desc = $formData['long_desc'];
			$product->save();
		}else{
			$product_id = $this->_request->getParam('product_id');
			$productModel = new Product();
			$this->view->product = $productModel->fetchRow("id = ".$product_id);
//			Zend_Debug::dump($this->view->product['long_desc']);
			
			$fc = Zend_Controller_Front::getInstance();
			$this->view->oFCKeditor = new FCKeditor("long_desc");
			$this->view->oFCKeditor->BasePath = $fc->getBaseUrl()."/js/fckeditor/";
			$this->view->oFCKeditor->Height = "500px";
			if($this->view->product['long_desc'] != null && $this->view->product['long_desc'] != ''){
				$this->view->oFCKeditor->Value= $this->view->product['long_desc'];
			}
			
		}
	}
	
	
	function adminaddgiftAction(){
			$this->_helper->layout->setLayout ( "layout_admin" );
			if ($this->_request->isPost()) {
				$formData = $this->_request->getPost();
				//Zend_Debug::dump($formData);
				$productModel = new Product();
				$product = $productModel->createRow();
				$product->name = $formData['name'];
				$product->point = $formData['point'];
				$product->state = $formData['state'];
				$product->source = $formData['source'];
				$product->category = $formData['category'];
				$product->subcategory = $formData['subcategory'];
				$product->description = $formData['description'];
				$product->url = $formData['url'];
				$product->long_desc = $formData['long_desc'];
				$product->save();
				
				$this->view->saved = true;
			}else{
				
				$fc = Zend_Controller_Front::getInstance();
				$this->view->oFCKeditor = new FCKeditor("long_desc");
				$this->view->oFCKeditor->BasePath = $fc->getBaseUrl()."/js/fckeditor/";
				$this->view->oFCKeditor->Height = "500px";
				if($this->view->product['long_desc'] != null && $this->view->product['long_desc'] != ''){
					$this->view->oFCKeditor->Value= $this->view->product['long_desc'];
				}
			
		} 
		
	}
	function admintestAction(){
		$formData = $this->_request->getPost();
//			Zend_Debug::dump($formData);
			$productModel = new Product();
			$product = $productModel->fetchRow("id = ".$formData['id']);
			$product->product_id = $formData['product_id'];
			$product->name = $formData['name'];
			$product->point = $formData['point'];
			$product->state = $formData['state'];
			$product->source = $formData['source'];
			$product->category = $formData['category'];
			$product->subcategory = $formData['subcategory'];
			$product->description = $formData['description'];
			$product->url = $formData['url'];
			$product->long_desc = $formData['long_desc'];
			$product->save();
			$this->_redirect('gift/description/id/'.$formData['id']);
//		Zend_Debug::dump($formData['long_desc']);
	}
	 
	function deletegiftlogicAction(){
		$pid = $this->_request->getParam('id');	
		$db = Zend_Registry::get('db');	
		$db->query("update product set state = 'EXPIRED' where id = " . $pid);	
		$this->_helper->json('Success');
	}
}

