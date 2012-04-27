<?php
include_once 'sms.inc.php';
include_once 'Indicate2Connect.php';
class SmsController extends MyController {
	protected $_rowsPerPage = 30;
	protected $_curPage = 1;
	
	function admincomposeAction() {
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		if ($this->_request
			->isPost ()) {
			$formData = $this->_request
				->getPost ();
			$sendNum = ( int ) $formData ['send_number'];
			$planId = $formData ['plan_id'];
			//1.get phone numbers
			$db = Zend_Registry::get ( 'db' );
			$where = " where plan_id=" . $planId . " and state='New' order by id limit " . $sendNum;
			$rs = $db->fetchAll ( "select phone from short_message " . $where );
			$phoneStr = '';
			if (count ( $rs ) > 0) {
				foreach ( $rs as $row ) {
					$phoneStr .= $row ['phone'];
					$phoneStr .= ',';
				}
			}
			$this->view->phones = rtrim ( $phoneStr, "," );
			//2.get sms content
			$this->view->content = $db->fetchOne ( "SELECT content FROM communicate_plan WHERE id=:t1", array ('t1' => $planId ) );
			//3.update state
			// update sms log state and date
			$updateSql = "update short_message set state='Sending' " . $where;
			$db->query ( $updateSql );
			$this->view->plan_id = $planId;
		} else {
			// get phone (default '')
			if ($this->_request
				->getParam ( 'phones' )) {
				$this->view->phones = $this->_request
					->getParam ( 'phones' );
			}
			if ($this->_request
				->getParam ( 'content' )) {
				$this->view->content = $this->_request
					->getParam ( 'content' );
			}
			
			$smsMessage = new SmsMessage();
			$this->view->smsmessages = $smsMessage->fetchAll('sms_message.delete =0','crdate desc');
		}
	}
	
	//
	

	function admindynamiccomposeAction() {
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		if ($this->_request
			->isPost ()) {
			$formData = $this->_request
				->getPost ();
			$sendNum = ( int ) $formData ['send_number'];
			$planId = $formData ['plan_id'];
			//1.get phone numbers
			$db = Zend_Registry::get ( 'db' );
			$where = " where plan_id=" . $planId . " and state='New' order by id limit " . $sendNum;
			$rs = $db->fetchAll ( "select phone from short_message " . $where );
			$phoneStr = '';
			if (count ( $rs ) > 0) {
				foreach ( $rs as $row ) {
					$phoneStr .= $row ['phone'];
					$phoneStr .= ',';
				}
			}
			$this->view->phones = rtrim ( $phoneStr, "," );
			//2.get sms content
			$this->view->content = $db->fetchOne ( "SELECT content FROM communicate_plan WHERE id=:t1", array ('t1' => $planId ) );
			//3.update state
			// update sms log state and date
			$updateSql = "update short_message set state='Sending' " . $where;
			$db->query ( $updateSql );
			$this->view->plan_id = $planId;
		} else {
			// get phone (default '')
			if ($this->_request
				->getParam ( 'phones' )) {
				$this->view->phones = $this->_request
					->getParam ( 'phones' );
			}
			if ($this->_request
				->getParam ( 'content' )) {
				$this->view->content = $this->_request
					->getParam ( 'content' );
			}
			$smsMessage = new SmsMessage();
			$this->view->smsmessages = $smsMessage->fetchAll('sms_message.delete =0','crdate desc');
		}
	}
	//
	function adminsendAction() {
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		$currentTime = date ( "Y-m-d H:i:s" );
		
		if ($this->_request
			->isPost ()) {
			$formData = $this->_request
				->getPost ();
			
			$newclient = new SMS ();
			if (
				$newclient->ConfNull == "1") {
				$mobile = $formData ['PhoneNum'];
				if ($formData['Msg']!=null && $formData['Msg']!="") {
					$message = $formData['Msg'];
				} else {
					$smsMessage = new SmsMessage();
					$message    = $smsMessage->fetchRow('id ='.$formData['message']);
					$message = $message->content;
					//$message = $formData ['Msg'];
				}
				
				$time = $currentTime;
				$apitype = $formData ['apitype']; // $apitype 通道选择 0：默认通道； 2：通道2； 3：即时通道；
				$msg = iconv ( "UTF-8", "GB2312", $message );
				//				Zend_Debug::dump($msg);
				$respxml = $newclient->sendSMS ( $mobile, $msg, $time, $apitype );
				// update sms log state and date
				if (isset ( $formData ['plan_id'] ) && $formData ['plan_id'] > 0) {
					$planId = $formData ['plan_id'];
					$sendTime = date ( "Y-m-d H:i:s" );
					$sms = new ShortMessage ();
					$set = array ('state' => 'Sent', 'send_date' => $sendTime );
					$where = "plan_id=" . $planId . " and state='Sending' and phone in (" . $mobile . ")";
					$sms->update ( $set, $where );
				}
				
				//print_r($newclient->sendXML);die();
				$smsSpace = new Zend_Session_Namespace ( 'SMS' );
				$smsSpace->xml = $newclient->sendXML;
				$smsSpace->respxml = $respxml;
				$this->view->code = $newclient->getCode ();
				$respArr = $newclient->toArray ();
				$this->view->mess = $respArr ["msg"];
				$smsid = $respArr ["idmessage"] [0];
				$this->view->succnum = $respArr ["successnum"] [0];
				$this->view->succphone = $respArr ["successphone"] [0];
				$this->view->failephone = $respArr ["failephone"] [0];
				//var_dump($respArr);
				//record the history of the sms sent
				$phones = explode ( ',', $respArr ["successphone"] [0] );
				$db = Zend_Registry::get ( 'db' );
				$sentSmsModel = new SentSms();
				if (count ( $phones )) {
					foreach ( $phones as $phone ) {
						$sms = $sentSmsModel->createRow();
						$sms->text = $message;
						$sms->to=$phone;
						$sms->time=$currentTime;
						$sms->state = 'SENT';
						$sms->save();
					}
				}

/*				$phone_sent_insert = '';
				$fphone_sent_insert = '';
				$phone = explode ( ',', $respArr ["successphone"] [0] );
				
				if (count ( $phone )) {
					foreach ( $phone as $val ) {
						$phone_sent_insert .= (strlen ( $phone_sent_insert ) == 0) ? " values('$message','$val','SENT','" . date ( "Y-m-d h:i:s" ) . "')" : ",('$message','$val','SENT','" . date ( "Y-m-d h:i:s" ) . "')";
					}
					$db = Zend_Registry::get ( 'db' );
					$history_sms = $db->prepare ( 'insert into sent_sms(text,sent_sms.to,state,time) ' . $phone_sent_insert . ';' );
					//die('insert into sent_sms(text,sent_sms.to,state,time) '.$phone_sent_insert);
					$history_sms->execute ();
				}
				
				$fphone = explode ( ',', $respArr ["failephone"] [0] );
				if (strlen ( $fphone [0] )) {
					foreach ( $fphone as $val ) {
						$fphone_sent_insert .= (strlen ( $fphone_sent_insert ) == 0) ? " values('$message','$val','FAIL','" . date ( "Y-m-d h:i:s" ) . "')" : ",('$message','$val','FAIL','" . date ( "Y-m-d h:i:s" ) . "')";
					}
					$db = Zend_Registry::get ( 'db' );
					$fhistory_sms = $db->prepare ( 'insert into sent_sms(text,sent_sms.to,state,time) ' . $fphone_sent_insert . ';' );
					//die('insert into sent_sms(text,sent_sms.to,state,time) '.$fphone_sent_insert.';');
					$fhistory_sms->execute ();
				}*/
			
			} else {
				$this->view->code = "<font color='red'>失败</font>";
				$this->view->ermess = "<font color='red'>你还没有配置文件</font>";
				$this->view->error = "<font color='red'>失败</font>";
			}
		}
	}
	
	function adminsenddynamicmsgAction() {
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		$currentTime = date ( "Y-m-d H:i:s" );
		
		if ($this->_request
			->isPost ()) {
			$formData = $this->_request
				->getPost ();
			$smsMessage = new SmsMessage();
			$message    = $smsMessage->fetchRow('id ='.$formData['message']);
			$message = $message->content;
			//$message = $formData ['Msg'];
			$time = $currentTime;
			$apitype = $formData ['apitype']; // $apitype 通道选择 0：默认通道； 2：通道2； 3：即时通道；
			$msg = iconv ( "UTF-8", "GB2312", $message );
			

//				$respxml = $newclient->sendSMS ( $dorrlar[], $msg, $time, $apitype );
			$dollar = array ();
			$newclient = new SMS ();
			if ($newclient->ConfNull == "1") {
				$data = $formData ['data'];
				
				$rows = explode ( "\n", $data );
				$i = 0;
				foreach ( $rows as $row ) {
					$cells = explode ( "\t", $row );
					$j = 0;
					foreach ( $cells as $cell ) {			
						$dollar [$j] [$i] = $cell;	
						$j ++;
					}
					$i ++;
				}
				$i=0;
				$successphone = array();
				$faildphone= array();
				$msgHash = array();
				foreach ($dollar[0] as $mobile){
//					$j=1;a
//					$tempmsg = str_replace('$1', $dollar[1][0], $msg);
					if (strlen($mobile)==0){
						break;
					}
					$tempmsg = $msg;
					$tempmessage = $message;
					for ($j=1;$j<=9;$j++){
						if(isset($dollar[$j])){
							$insideof =iconv ( "UTF-8", "GB2312", $dollar[$j][$i] );
							$tempmessage = str_replace('$'.$j, $dollar[$j][$i], $tempmessage);
							$tempmsg = str_replace('$'.$j, $insideof, $tempmsg);
						}
					}
					$respxml = $newclient->sendSMS ( $mobile, $tempmsg, $time, $apitype );
					$res =  $newclient->toArray ();
					if(isset($res["successnum"][0])&&$res["successnum"][0]==1)
					{
						$successphone[]=$mobile;
					}
					else{
						$faildphone[]=$mobile;
					} 
					$msgHash[$mobile] = $tempmessage;
					$i++;
				}
				$this->view->apitype= $apitype;
				$this->view->succphone = $successphone;
				$this->view->failephone = $faildphone;

				//record the history of the sms sent
				$phones = $successphone;
				$db = Zend_Registry::get ( 'db' );
				$sentSmsModel = new SentSms();
				if (count ( $phones )) {
					foreach ( $phones as $phone ) {
						$sms = $sentSmsModel->createRow();
						$sms->text = $msgHash[$phone];
//						Zend_Debug::dump($sms->text);
						$sms->to=$phone;
						$sms->time=$currentTime;
						$sms->state = 'SENT';
						$sms->save();
					}
				}
			
			} else {
				$this->view->code = "<font color='red'>失败</font>";
				$this->view->ermess = "<font color='red'>你还没有配置文件</font>";
				$this->view->error = "<font color='red'>失败</font>";
			}
		}
	}
	
	function adminxmldisplyAction() {
		$smsSpace = new Zend_Session_Namespace ( 'SMS' );
		if ($this->_request
			->getParam ( 'flag' ) == 0) {
			//			header("Content-Type: application/xml");
			if ($this->_request
				->getParam ( 'create' ) == null) {
				$this->view->text = stripslashes ( $this->_request ["xml"] );
			} else {
				$this->view->text = stripslashes ( $smsSpace->xml );
				$smsSpace->xml = "";
				$smsSpace->__unset ( 'xml' );
			}
		} else {
			//			if (strstr($smsSpace["respxml"],"</scp>")) header("Content-Type: application/xml");
			$this->view->text = $smsSpace->respxml;
			$smsSpace->respxml = "";
			$smsSpace->__unset ( 'respxml' );
		}
		$this->_helper->layout
			->disableLayout ();
	}
	
	function adminaccountAction() {
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		
		$smsSpace = new Zend_Session_Namespace ( 'SMS' );
		
		$newclient = new SMS ();
		
		$respxml = $newclient->infoSMSAccount ();
		
		$smsSpace->xml = $newclient->sendXML;
		$smsSpace->respxml = $respxml;
		$this->view->code = $newclient->getCode ();
		$this->view->respArr = $newclient->toArray ();
		$this->view->mess = $this->view->respArr ["msg"];
		$this->view->account = $this->view->respArr ["smsaccount"] [0];
	}
	
	function adminreceiveAction() {
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		// get current page(default page = 1)
		if ($this->_request
			->getParam ( 'page' )) {
			$this->_curPage = $this->_request
				->getParam ( 'page' );
		}
		// get all sms desc
		$sparkSmsModel = new SparkSms ();
		$this->view->messages = $sparkSmsModel->findAllSMS ();
		//Zend_Debug::dump($this->view->messages);
		//paging
		$this->view->controller = $this->_request
			->getControllerName ();
		$this->view->action = $this->_request
			->getActionName ();
		$paginator = new Zend_Paginator ( new Zend_Paginator_Adapter_Array ( $this->view->messages ) );
		$paginator->setCurrentPageNumber ( $this->_curPage )
			->setItemCountPerPage ( $this->_rowsPerPage );
		$this->view->paginator = $paginator;
		
		$this->view->testEnv = Zend_Registry::get ( 'testEnv' );
		// get active campaign
		$campaign = new Campaign ();
		$time = date ( "Y-m-d H:i:s" );
		$where = "create_date<'" . $time . "' and expire_date>'" . $time . "'";
		$order = "expire_date desc";
		$this->view->campaigns = $campaign->fetchAll ( $where, $order, null, null );
	}
	
	function adminajaxAction() {
		$testEnv = Zend_Registry::get ( 'testEnv' );
		
		if ($testEnv == 0) {
			// fetch msg from gateway
			$smsSpace = new Zend_Session_Namespace ( 'SMS' );
			
			$newclient = new SMS ();
			
			$respxml = $newclient->readSMS ();
			
			$smsSpace->xml = $newclient->sendXML;
			$smsSpace->respxml = $respxml;
			
			$code = $newclient->getCode ();
			$respArr = $newclient->toArray ();
			if ($respArr ["id"] [0] > 0) {
				$sparkSmsModel = new SparkSms ();
				$message = $sparkSmsModel->createRow ();
				
				$message->msg = $respArr ["msg"];
				$message->sys_id = $respArr ["id"] [0];
				$message->source = $respArr ["src"] [0];
				$message->time = $respArr ["time"] [0];
				$message->text = iconv ( "GB2312", "UTF-8", base64_decode ( $respArr ["message"] [0] ) );
				$message->err = $respArr ["err"] [0];
				
				$db = Zend_Registry::get ( 'db' );
				$select = $db->select ();
				$select->from ( 'consumer', '*' );
				$select->where ( 'consumer.phone = ?', $message->source );
				$consumer = $db->fetchRow ( $select );
				$message->consumer_id = $consumer ['id'];
				
				$message->save ();
				$jsonMsg ['name'] = $consumer ['name'];
				$jsonMsg ["sys_id"] = $message->id;
				$jsonMsg ["source"] = $message->source;
				$jsonMsg ["time"] = $message->time;
				$jsonMsg ["text"] = $message->text;
				$jsonMsg ["err"] = $message->err;
				$this->_helper
					->json ( $jsonMsg );
			
			} else {
				$jsonMsg ["sys_id"] = - 1;
				$this->_helper
					->json ( $jsonMsg );
			}
			$this->_helper->layout
				->disableLayout ();
		
		// store msg into db
		}
	}
	
	function adminlistAction() {
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		$request = $this->getRequest ();
		
		if (! $request->isPost ()) {
			$plan_id = $request->getParam ( 'plan_id' );
		
		} else { //create new plan and execute sql
			$formData = $request->getPost ();
			$sql = $formData ['sql'];
			if ($sql == '' || $sql == null) {
				return;
			} else {
				$sql = 'select consumer.* ' . $sql;
			}
			$type = $formData ['type'];
			$content = $formData ['content'];
			
			//1.get all consumers
			$db = Zend_Registry::get ( 'db' );
			$result = $db->query ( $sql );
			$consumers = $result->fetchAll ();
			//2. new a communicate plan
			$telephonePlanModel = new TelephonePlan ();
			$row = $telephonePlanModel->createRow ();
			$row->sql = $sql;
			$row->type = $type;
			$row->content = $content;
			//$row->total_consumers = count($consumers);
			//2011-04-08 ham.bao separate the sessions with admin
			$row->admin_id = $this->_currentAdmin->id;
			$row->edit_time = date ( "Y-m-d H:i:s" );
			$plan_id = $row->save ();
			
			//3. create sms
			$num = 0;
			foreach ( $consumers as $consumer ) {
				if ($consumer ['phone'] == null || $consumer ['phone'] == '') {
					continue;
				}
				if (preg_match ( '/1[35]\d{9}/', $consumer ['phone'], $rs ) && count ( $rs ) > 0) {
					$phone = $rs [0];
				} else {
					continue;
				}
				$sms = new ShortMessage ();
				$row = $sms->createRow ();
				$row->consumer_id = $consumer ['id'];
				$row->phone = $phone;
				$row->state = 'New';
				$row->plan_id = $plan_id;
				$row->send_date = date ( "Y-m-d H:i:s" );
				$row->save ();
				$num ++;
			}
			//4.update plan total number
			$telephonePlanModel = new TelephonePlan ();
			$set = array ('total_consumers' => $num );
			$where = "id=" . $plan_id;
			$telephonePlanModel->update ( $set, $where );
		
		}
		//5.show consumers
		$db = Zend_Registry::get ( 'db' );
		$select = $db->select ();
		$select->from ( 'short_message', array ('phone', 'state', 'send_date', 'plan_id' ) )
			->join ( 'consumer', 'consumer.id = short_message.consumer_id', 'name' )
			->where ( "short_message.plan_id = ?", $plan_id )
			->order ( 'short_message.state asc' )
			->order ( 'short_message.id' );
		$this->view->sms = $db->fetchAll ( $select );
		$this->view->plan_id = $plan_id;
	}
	
	function adminreportAction() {
		
		$this->_helper->layout
			->setLayout ( "layout_admin" );
		
		$sms_id = ( int ) $this->_request
			->getParam ( 'sms_id' );
		$smsSql = "select b.* from spark_sms as a, spark_sms as b where a.id=" . $sms_id . " and b.consumer_id=a.consumer_id and b.id-a.id>-5 and b.id-a.id<5 order by b.id";
		$db = Zend_Registry::get ( 'db' );
		$rs = $db->fetchAll ( $smsSql );
		$sms = '';
		$consumer_id = 0;
		$smsIds = array ();
		foreach ( $rs as $row ) {
			array_push ( $smsIds, $row ['id'] );
			$sms .= $row ['text'];
			$consumer_id = $row ['consumer_id'];
		}
		
		//update sms state
		$ids = implode ( ",", $smsIds );
		//Zend_Debug::dump($ids);
		$updateSql = $db->prepare ( "update spark_sms set state='Reported' where id in (" . $ids . ")" );
		$updateSql->execute ();
		
		$campaign_id = ( int ) $this->_request
			->getParam ( 'cam_id' );
		//whether participate in the campaign
		$campaigninvitationModel = new CampaignInvitation ();
		$campaigninvitation = $campaigninvitationModel->fetchRow ( 'campaign_id = ' . $campaign_id . ' and consumer_id' . ' =' . $consumer_id );
		if ($campaigninvitation == null) {
			$this->_helper
				->redirector ( 'index', 'home' );
		}
		//get lang
		$consumerModel = new Consumer ();
		$consumer = $consumerModel->fetchRow ( "id=" . $consumer_id );
		//get i2_survey_id
		$campaignModel = new Campaign ();
		$campaign = $campaignModel->fetchRow ( "id=" . $campaign_id );
		
		$langNamespace = new Zend_Session_Namespace ( 'Lang' );
		$lang = $langNamespace->lang;
		
		if ($consumer->language_pref == 'en') {
			$surveyId = $campaign->i2_survey_id_en;
		} else {
			$surveyId = $campaign->i2_survey_id;
		}
		$this->view->campaing_name = $campaign->name;
		$indicate2Connect = new Indicate2_Connect ();
		$accesscode = $indicate2Connect->createParticipation ( $consumer->email, $surveyId );
		//save list in session
		$reportNamespace = new Zend_Session_Namespace ( 'AgentReports' );
		$reportNamespace->$accesscode = $consumer_id;
		$source = $accesscode . "_source";
		$reportNamespace->$source = "sms";
		
		$config = Zend_Registry::get ( 'config' );
		$this->view->filloutPage = $config->indicate2->home . "/c/" . $accesscode . "/theme/wildfire";
		$this->view->id = $consumer->id;
		$this->view->name = $consumer->name;
		$this->view->sms = $sms;
		$this->view->includeCrystalCss = true;
	}
	
	function adminreportagainAction() {
		$this->_helper->layout
			->disableLayout ();
		$sms_id = ( int ) $this->_request
			->getParam ( 'sms_id' );
		$db = Zend_Registry::get ( 'db' );
		$updateSql = $db->prepare ( "update spark_sms set state=null where id=" . $sms_id );
		$updateSql->execute ();
		$this->_redirect ( 'sms/adminreceive' );
	}
	
	function adminreplydirectlyAction() {
		$this->_helper->layout
			->disableLayout ();
		$sms_id = ( int ) $this->_request
			->getParam ( 'sms_id' );
		//update sms state
		$db = Zend_Registry::get ( 'db' );
		$updateSql = $db->prepare ( "update spark_sms set state='Replied' where id=" . $sms_id );
		$updateSql->execute ();
		//prepare consumer info
		$select = $db->select ();
		$select->from ( 'spark_sms', array ('source as phone' ) )
			->joinleft ( 'consumer', 'consumer.id = spark_sms.consumer_id', 'recipients_name as name' )
			->where ( "spark_sms.id = ?", $sms_id );
		$rs = $db->fetchRow ( $select );
		$this->_redirect ( 'sms/admincompose/phones/' . $rs ['phone'] . '/content/' . $rs ['name'] );
	}
	
	function sendcouponAction() {
		$this->_helper->layout
			->setLayout ( "layout_coupon" );
		$currentTime = date ( "Y-m-d H:i:s" );
		$endTime = date ( "Y年m月d日", strtotime ( "+7 day" ) );
		$couponId = ( int ) $this->_request
			->getParam ( 'uid' );
		//$message = $this->getCouponById($couponId);
		//var_dump($_SERVER['HTTP_REFERER']);die;
		$lou = 0;
		if (isset ( $_SERVER ['HTTP_REFERER'] ) && preg_match ( '/19lou/', $_SERVER ['HTTP_REFERER'] )) {
			$lou = 1;
			$_SESSION ['19lou'] = 1;
		}
		
		if (isset ( $_SESSION ['19lou'] )) {
			$lou = 1;
		}
		
		//var_dump($_SESSION);die;
		$this->view->message = array ();
		$this->view->coupon = $couponId;
		if ($this->_request
			->isPost ()) {
			$formData = $this->_request
				->getPost ();
			if (trim ( $formData ['username'] ) == '') {
				$this->view->message ['username'] = '姓名不能为空。';
			}
			if (trim ( $formData ['telephone'] ) == '') {
				$this->view->message ['telephone'] = '手机号码不能为空。';
			}
			if (trim ( $couponId ) == '') {
				$this->view->message ['coupon'] = '没有对应的优惠券。';
			}
			$this->view->postData = $formData;
			if ((trim ( $formData ['telephone'] ) != '') && $this->validateSend ( $formData ['telephone'], $couponId, $lou )) {
				$this->view->message ['got'] = '已经领取优惠券或同一天固定IP只能领取一份。';
			}
			//print_r($formData);die;
			if (! count ( $this->view->message )) {
				$newclient = new SMS ();
				if ($newclient->ConfNull == "1") {
					$mobile = $formData ['telephone'];
					$message = $this->getCouponById ( $couponId );
					$time = $currentTime;
					//echo str_replace('{date}',$endTime,$message['content']);die;
					$apitype = 3; // $apitype 通道选择 0：默认通道； 2：通道2； 3：即时通道；
					$msg = iconv ( "UTF-8", "GB2312", str_replace ( '{date}', $endTime, $message ['content'] ) );
					//				Zend_Debug::dump($msg);
					$respxml = $newclient->sendSMS ( $mobile, $msg, $time, $apitype );
					
					//print_r($newclient->sendXML);die();
					$smsSpace = new Zend_Session_Namespace ( 'SMS' );
					$smsSpace->xml = $newclient->sendXML;
					$smsSpace->respxml = $respxml;
					$this->view->code = $newclient->getCode ();
					if ($this->view->code == 2000) {
						$this->view->message ['susess'] = "成功领取优惠券.";
						//add the coupon history
						$db = Zend_Registry::get ( 'db' );
						$insetSql = $db->prepare ( "insert into coupon_history(telephone,ip,crdate,cuid) values ('$formData[telephone]','$_SERVER[REMOTE_ADDR]','$currentTime',$couponId)" );
						$insetSql->execute ();
					} else {
						$this->view->message ['telephone'] = '手机号码可能不正确。';
					}
				} else {
					$this->view->message ['fail'] = "失败";
					$this->view->message ['noconfig'] = "你还没有配置文件";
				}
			}
		}
	}
	
	function getCouponById($id) {
		$db = Zend_Registry::get ( 'db' );
		
		$select = $db->select ();
		$select->from ( 'coupon', array ('content' ) )
			->where ( "uid = ?", $id );
		
		$rs = $db->fetchRow ( $select );
		//print_r($rs);die;
		return $rs;
	}
	/**
	 * validate whether the user has got the coupon
	 * @param string $telephone
	 * @param string $cuid
	 * @param bool $lou
	 * @return bool|bool
	 */
	function validateSend($telephone, $cuid, $lou) {
		$db = Zend_Registry::get ( 'db' );
		$formData = $this->_request
			->getPost ();
		if (preg_match ( '/13564709693/', $formData ['telephone'] )) {
			return false;
		}
		$select = $db->select ();
		if (! $lou) {
			$select->from ( 'coupon_history', array ('uid' ) )
				->where ( "(telephone = '$telephone' and cuid= $cuid) or (ip='$_SERVER[REMOTE_ADDR]' and cuid = $cuid and DATE_FORMAT(crdate,'%Y-%m-%d')=DATE_FORMAT(now(),'%Y-%m-%d'))" );
		} else {
			$select->from ( 'coupon_history', array ('uid' ) )
				->where ( "(telephone = '$telephone' and cuid= $cuid)" );
		}
		$rs = $db->fetchRow ( $select );
		//var_dump($rs);die; 
		return $rs ? true : false;
	}
	
	function adminsmsmessageAction(){
		ini_set('iconv.internal_encoding','utf-8');
		$this->_helper->layout->setLayout ( "layout_admin" );
		$form = new SmsMessageForm();
		$smsMessage = new SmsMessage();
		$id = $this->_request->getParam('id');
		if($id){
			$smsData = $smsMessage->fetchRow('id='.$id);
			$form->setDefault('subject', $smsData->subject);
			$form->setDefault('message', $smsData->content);
		}
		
		$this->view->saved = false;
		if($this->_request->isPost()){
			$formData =  $this->_request->getPost();
			if ($form->isValid ( $formData )){
				
				if($id){
					$smsMessage->update(array("content"=>$formData['message'],'subject'=>$formData['subject']),'id = '.$id);
					$this->view->saved = true;
				}else{
					$message = $this->_request->getPost('message');	
					$subject = $formData['subject'];				
					if(trim($message)!=''){
						$row = $smsMessage->createRow();
						$row->content = $message;
						$row->subject = $subject;
						$row->crdate = date("Y-m-d H:i:s");
						$row->save();
						$this->view->saved = true;
					}
				}
				
				
			}
		}

		$this->view->id   = $id;
		$this->view->form = $form;	
		
	}
	
    function adminsmsmessagelistAction(){
		$this->_helper->layout->setLayout ( "layout_admin" );
		$smsMessage = new SmsMessage();
		$id = $this->_request->getParam('id');
    	if($id){
			$smsMessage->update(array("delete"=>1),'id = '.$id);
			$this->_helper->redirector("adminsmsmessagelist");
		}
		$db = Zend_Registry::get ( 'db' );
		$select = $db->select ();
		$select->from ( 'sms_message', array('sms_message.*'));
		$select->where ('sms_message.delete =0 ');
		$select->order ('crdate desc');
		$this->view->sms = $db->fetchAll( $select );
	}
	
}


