<?php
require_once 'Pagination/Pagination.php';
class ConsumerController extends MyController {
	protected $_rowsPerPage = 2000;
	protected $_curPage = 1;

	function adminimportAction() {
		$request = $this->getRequest ();
		$campaign = new Campaign ();
		$order = "expire_date desc";
		$this->view->campaigns = $campaign->fetchAll ( null, $order, null, null );
		if ($request->isPost ()) {
			$formData = $request->getPost ();
			$campaign_id = $formData ['campaign_id'];
			$file = $_FILES ['file'];
			$objReader = PHPExcel_IOFactory::createReader ( 'Excel2007' );
			$objReader->setReadDataOnly ( true );
			$objPHPExcel = $objReader->load ( $file ['tmp_name'] );
			$objWorksheet = $objPHPExcel->getActiveSheet ();
			foreach ( $objWorksheet->getRowIterator () as $row ) {
				$row_index = $row->getRowIndex ();
				if ($row_index == 1) {
					continue;
				}
				$consumerModel = new Consumer ();
				$row = $consumerModel->createRow ();
				$row->name = $objWorksheet->getCell ( 'A' . $row_index )
				->getValue ();
				$row->recipients_name = $objWorksheet->getCell ( 'A' . $row_index )
				->getValue ();
				$row->login_phone = strval ( $objWorksheet->getCell ( 'C' . $row_index )
				->getValue () );
				$row->phone = strval ( $objWorksheet->getCell ( 'C' . $row_index )
				->getValue () );
				$row->password = md5 ( "111111" );
				$row->address1 = $objWorksheet->getCell ( 'B' . $row_index )
				->getValue ();
				$row->province = $objWorksheet->getCell ( 'D' . $row_index )
				->getValue ();
				$row->city = $objWorksheet->getCell ( 'E' . $row_index )
				->getValue ();
				$consumer_id = $row->save ();

				$currentTime = date ( "Y-m-d H:i:s" );
				$campaignInvitationModel = new CampaignInvitation ();
				$row = $campaignInvitationModel->createRow ();
				$row->consumer_id = $consumer_id;
				$row->campaign_id = $campaign_id;
				$row->state = 'ACCEPTED';
				$row->create_date = $currentTime;
				$campaign_invitation_id = $row->save ();

				$campaignParticipationModel = new CampaignParticipation ();
				$row = $campaignParticipationModel->createRow ();
				$row->campaign_invitation_id = $campaign_invitation_id;
				$row->state = 'NEW';
				$row->accept_date = $currentTime;
				$row->save ();
			}

			//$this->_helper->redirector('adminindex', 'consumer');
		}
	}

	function adminindexAction() {
		$this->view->title = 'Consumers';
		$this->view->activeTab = "List Consumers";
		if ($this->_request
		->getParam ( 'page' )) {
			$this->_curPage = $this->_request
			->getParam ( 'page' );
		}
		$db = Zend_Registry::get ( 'db' );
		$selectConsumer = $db->select ();
		$selectConsumer->from ( 'consumer', '*' );
		if ($this->_request
		->getParam ( 'pest' ) == '0') {
			$selectConsumer->where ( 'consumer.pest is null or consumer.pest != 1' );
		} else {
			$selectConsumer->where ( 'consumer.pest = 1' );
		}
		$selectConsumer->order ( 'id desc' );
		$this->view->consumers = $db->fetchAll ( $selectConsumer );
		//paging
		$this->view->controller = $this->_request
		->getControllerName ();
		$this->view->action = $this->_request
		->getActionName ();
		$paginator = new Zend_Paginator ( new Zend_Paginator_Adapter_Array ( $this->view->consumers ) );
		$paginator->setCurrentPageNumber ( $this->_curPage )
		->setItemCountPerPage ( $this->_rowsPerPage );
		$this->view->paginator = $paginator;
		//set the No. inital value in view page
		$this->view->NoInitValue = ($this->_curPage - 1) * $this->_rowsPerPage + 1;

		//get sender
		$select = $db->select ();
		$select->from ( 'signup_auth_code', array ('receiver', 'use_date' ) );
		$select->where ( 'signup_auth_code.receiver is not null' );
		$select->where ( 'signup_auth_code.sender is not null' );
		$select->join ( 'consumer', 'signup_auth_code.sender = consumer.id', 'consumer.name' );

		$this->view->sendAndReceiverArray = array ();
		$sendAndReceivers = $db->fetchAll ( $select );

		foreach ( $sendAndReceivers as $sendAndReceiver ) :
		$i = $sendAndReceiver ['receiver'];
		$this->view->sendAndReceiverArray [$i] = $sendAndReceiver ['name'];
		endforeach
		;
		//get use date
		$select2 = $db->select ();
		$select2->from ( 'signup_auth_code', array ('receiver', 'use_date' ) );
		$select2->join ( 'consumer', 'signup_auth_code.receiver = consumer.id' );

		$this->view->codeUseDateArray = array ();
		$codeUseDates = $db->fetchAll ( $select2 );
		foreach ( $codeUseDates as $codeUseDate ) :
		$i = $codeUseDate ['receiver'];
		$this->view->codeUseDateArray [$i] = $codeUseDate ['use_date'];
		endforeach
		;

		$this->_helper->layout
		->setLayout ( "layout_admin" );

		//		Zend_Debug::dump($sendAndReceivers);


	}
	function adminshowinvitedfriendsAction() {
		$this->_helper->layout
		->setLayout ( "layout_admin" );
		$this->view->title = 'Invite Friends List';
		$this->view->activeTab = "List Consumers";

		$db = Zend_Registry::get ( 'db' );
		$selectInvitations = $db->select ();
		$selectInvitations->from ( 'consumer', array ('count(*)', 'id', 'email', 'name', 'recipients_name' ) )
		->join ( 'invitation_email', 'consumer.id = invitation_email.consumer_id ', null )
		->where ( 'consumer.pest is null or consumer.pest != 1' )
		->// 173 is test@163.com and used to send admin invitation mails
		where ( 'consumer.id != 173' )
		->group ( 'invitation_email.consumer_id' )
		->order ( 'count(*) desc' );
		$this->view->invitations = $db->fetchAll ( $selectInvitations );

		$selectTotalInvitations = $db->select ();
		$selectTotalInvitations->from ( 'invitation_email', 'count(*)' )
		->join ( 'consumer', 'consumer.id = invitation_email.consumer_id ', null )
		->where ( 'consumer.pest is null or consumer.pest != 1' )
		->where ( 'invitation_email.consumer_id != 173' );
		$this->view->totalInvitations = $db->fetchOne ( $selectTotalInvitations );

		$selectJoined = $db->select ();
		$selectJoined->from ( 'signup_auth_code', array ('count(*)', 'sender' ) )
		->join ( 'consumer', 'consumer.id = signup_auth_code.sender ', null )
		->where ( 'consumer.pest is null or consumer.pest != 1' )
		->where ( 'receiver is not null' )
		->where ( 'sender is not null' )
		->where ( 'use_date is not null' )
		->where ( 'source is not null' )
		->where ( 'source != "PUBLIC_LINK"' )
		->group ( 'sender' );
		$joins = $db->fetchAll ( $selectJoined );
		$this->view->joinedArray = array ();
		$this->view->totalJoin = 0;
		foreach ( $joins as $join ) {
			$this->view->joinedArray [$join ['sender']] = $join ['count(*)'];
			$this->view->totalJoin += $join ['count(*)'];
		}

		//		Zend_Debug::dump($joins);
	}

	function editAction() {

		$this->view->activeTab = 'Profile';
		$this->view->title = $this->view
		->translate ( "Wildfire" ) . " - " . $this->view
		->translate ( "Edit_Account_Settings" );
		$form = new ConsumerForm ();
		$consumerModel = new Consumer ();
		$consumerextraModel = new ConsumerExtraInfo ();
		$consumerextra = $consumerextraModel->fetchRow ( 'consumer_id = ' . $this->_currentUser->id );
		$consumer = $this->_currentUser;

		if ($this->_request
		->isPost ()) { //POST
			$formData = $this->_request
			->getPost ();
			if ($form->isValid ( $formData )) {
				$id = $this->_currentUser->id;
				//consumer table
				$consumer = $consumerModel->find ( $id )
				->current ();
				$consumer->name = $form->getValue ( 'name' );
				$consumer->login_phone = $form->getValue('login_phone');
				$consumer->phone = $form->getValue ( 'phone' );
				$consumer->address1 = $form->getValue ( 'address1' );
				$consumer->postalcode = $form->getValue ( 'postalcode' );
				$consumer->recipients_name = $form->getValue ( 'recipients_name' );
				$consumer->birthdate = $form->getValue("birthdate");
				$consumer->gender=$form->getValue("gender");
				$consumer->qq = $form->getValue ( 'qq' );
				$consumer->city = $formData['city'] ;
				$consumer->country = $formData['country'];

				/*if (isset ( $formData ['city'] ) && $formData ['city'] != null && $formData ['province'] != null) {
					$consumer->city = $formData ['city'];
					$consumer->province = $formData ['province'];
				}*/
				// no need province 2012-11-07
				/*if ($formData ['englishcity'] != null) {
					$consumer->city = $formData ['englishcity'];
					$consumer->province = null;
				}
				if ($formData ['province'] == '' && $formData ['englishcity'] == null) {
					$consumer->city = null;
					$consumer->province = null;
				}
				}*/
				$consumer->birthdate = $formData ['birthdate'] != null ? $formData ['birthdate'] : null;
				$consumer->save ();
				// consumer_extra_info table
				if ($consumerextra == null) {
					$consumerextra = $consumerextraModel->createRow ();
					$consumerextra->consumer_id = $this->_currentUser->id;
				}
				$consumerextra->gender = isset ( $formData ['gender'] ) ? $formData ['gender'] : null;
				$consumerextra->birthdate = $formData ['birthdate'] != null ? $formData ['birthdate'] : null;
				$consumerextra->profession = $formData ['profession'];
				$consumerextra->education = $formData ['education'];
				$consumerextra->have_children = isset ( $formData ['have_children'] ) ? $formData ['have_children'] : null;
				$consumerextra->children_birth_year = $formData ['children_birth_year'];
				$consumerextra->income = $formData ['income'];
				$consumerextra->status = $formData['status'];
				$consumerextra->online_shopping = isset ( $formData ['online_shopping'] ) ? $formData ['online_shopping'] : null;
				if (isset ( $formData ['use_extra_bonus_for'] )) {
					$use_extra_bonus_forstr = '';
					foreach ( $formData ['use_extra_bonus_for'] as $target ) {
						$use_extra_bonus_forstr .= $target . ';';
					}
				}
				$consumerextra->use_extra_bonus_for = isset ( $formData ['use_extra_bonus_for'] ) ? $use_extra_bonus_forstr : null;
				$consumerextra->save ();

				//session
				$authNamespace = new Zend_Session_Namespace ( 'Zend_Auth' );
				$authNamespace->user = $consumer;
				$form->populate ( $consumer->toArray () );
				// update bar data in session
				// The username and email are 20 points
				$count = 20;
				$consumerinfoArray = $consumer->toArray ();
				for($i = 0; $i < count ( $consumerinfoArray ); $i ++) {
					$temp = each ( $consumerinfoArray );
					// Address1, phone, city and recipients_name is 5 points each
					if ($temp ['key'] == 'address1' || $temp ['key'] == 'phone' || $temp ['key'] == 'city' || $temp ['key'] == 'recipients_name') {
						if ($temp ['value'] != null && $temp ['value'] != '') {
							$count += 5;
						}
					} else {
						continue;
					}
				}
				$extrainfoArray = $consumerextra->toArray ();
				$inc = round ( 60 / (count ( $extrainfoArray ) - 3), 1 );
				for($i = 0; $i < count ( $extrainfoArray ); $i ++) {
					$temp = each ( $extrainfoArray );
					// Ignore birth year of children
					if ($temp ['key'] == 'id' || $temp ['key'] == 'consumer_id' || $temp ['key'] == 'children_birth_year') {
						continue;
					} else {
						if ($temp ['value'] != null && $temp ['value'] != '') {
							$count += $inc;
						}
					}
				}
				$consumerExtraInfo = new Zend_Session_Namespace ( 'consumerExtraInfo' );
				$count = floor ( $count );
				$consumerExtraInfo->data = ($count + ($count % 5 == 0 ? 0 : 5 - $count % 5)) > 100 ? 100 : ($count + ($count % 5 == 0 ? 0 : 5 - $count % 5));
			}
		} else {
			//GET
			$request = $this->getRequest ();
			$form->populate ( $consumer->toArray () );
			if ($consumerextra != null) {
				$form->populate ( $consumerextra->toArray () );
				$form->use_extra_bonus_for
				->setValue ( explode ( ';', substr ( $consumerextra->use_extra_bonus_for, 0, strlen ( $consumerextra->use_extra_bonus_for ) - 1 ) ) );
			}
		}
		$langNamespace = new Zend_Session_Namespace ( 'Lang' );
		$this->view->language = $langNamespace->lang;
		if ($consumer ["city"] != NULL && $consumer ["province"] != NULL) {
			$this->view->city = $consumer ["city"];
			$this->view->province = $consumer ["province"];
		}
		if ($consumer ["city"] != NULL && $consumer ["province"] == NULL) {
			$this->view->encity = $consumer ["city"];
		}

		$this->view->form = $form;
		// consumer info bar session
		$consumerExtraInfo = new Zend_Session_Namespace ( 'consumerExtraInfo' );
		if (! isset ( $consumerExtraInfo->data )) {
			// The username and email are 20 points
			$count = 20;
			$consumerinfoArray = $consumer->toArray ();
			for($i = 0; $i < count ( $consumerinfoArray ); $i ++) {
				$temp = each ( $consumerinfoArray );
				// Address1, phone, city and recipients_name is 5 points each
				if ($temp ['key'] == 'address1' || $temp ['key'] == 'phone' || $temp ['key'] == 'city' || $temp ['key'] == 'recipients_name') {
					if ($temp ['value'] != null && $temp ['value'] != '') {
						$count += 5;
					}
				} else {
					continue;
				}
			}
				
			if ($consumerextra != null) {
				$extrainfoArray = $consumerextra->toArray ();
				$inc = round ( 60 / (count ( $extrainfoArray ) - 3), 1 );
				for($i = 0; $i < count ( $extrainfoArray ); $i ++) {
					$temp = each ( $extrainfoArray );
					// Ignore birth year of children
					if ($temp ['key'] == 'id' || $temp ['key'] == 'consumer_id' || $temp ['key'] == 'children_birth_year') {
						continue;
					} else {
						if ($temp ['value'] != null && $temp ['value'] != '') {
							$count += $inc;
						}
					}
				}
				$count = floor ( $count );
				$count = ($count + ($count % 5 == 0 ? 0 : 5 - $count % 5)) > 100 ? 100 : ($count + ($count % 5 == 0 ? 0 : 5 - $count % 5));
			}
			$consumerExtraInfo->data = $count;
		}
		$this->view->consumerextrainfo = $consumerExtraInfo->data;

	}

	function editcontactAction() {
		$campaign = $this->_request->getParam('cid');
		$campaignModel = new Campaign();	
		$cam = $campaignModel->find($campaign)->current();
		$form = new ConsumerContactForm (array('relative' =>$cam->relative));
		
		$consumerModel = new Consumer ();
		if ($this->_request->isPost ()) { //POST
			$formData = $this->_request->getPost ();
			//Zend_Debug::dump($form->isValid ( $formData ));die;
			if ($form->isValid ( $formData )) {
				$id = $this->_currentUser->id;
				$consumer = $consumerModel->find ( $id )
				->current ();
				$consumer->recipients_name = $form->getValue ( 'recipients_name' );
				$consumer->phone = $form->getValue ( 'phone' );
				$consumer->company_phone = $form->getValue ( 'telephone' );
				$consumer->address1 = $form->getValue ( 'address1' );
				$consumer->postalcode = $form->getValue ( 'postalcode' );
				if ($formData ['qq'] != null) {
					$consumer->qq = $formData ['qq'];
				}
				if ($formData ['city'] != null && $formData ['province'] != null) {
					$consumer->city = $formData ['city'];
					$consumer->province = $formData ['province'];
				}
				if ($formData ['englishcity'] != null) {
					$consumer->city = $formData ['englishcity'];
					$consumer->province = null;
				}
				if ($formData ['birthdate'] != null) {
					$consumer->birthdate = $formData ['birthdate'];
				}
				if ($formData ['gender'] != null) {
					$consumer->gender = $formData ['gender'];
				}
				if ($formData ['qq'] != null) {
					$consumer->qq = $formData ['qq'];
				}
				if ($formData ['province'] == '' && $formData ['englishcity'] == null) {
					$consumer->city = null;
					$consumer->province = null;
				}
				$consumer->save ();
				
				$authNamespace = new Zend_Session_Namespace ( 'Zend_Auth' );
				$authNamespace->user = $consumer;
				$form->populate ( $consumer->toArray () );
				
				$consumerextraModel = new ConsumerExtraInfo ();
				$consumerextra = $consumerextraModel->fetchRow ( 'consumer_id = ' . $this->_currentUser->id );
				if ($consumerextra == null) {
					$consumerextra = $consumerextraModel->createRow ();
					$consumerextra->consumer_id = $this->_currentUser->id;
				}
				$consumerextra->gender = isset ( $formData ['gender'] ) ? $formData ['gender'] : null;
				$consumerextra->birthdate = $formData ['birthdate'] != null ? $formData ['birthdate'] : null;
				$consumerextra->education = $formData ['education'];
				$consumerextra->income = $formData ['income'];
				$consumerextra->status = $formData ['status'];
				$consumerextra->save();
				
				
				//2011-05-03 ham.bao add the related friends 
				$consumerFriend = new ConsumerFriend();
				$consumerFriend->delete('consumer ='.$consumer->id.' and campaign='.$campaign);
				
				//delete
				/*foreach ( $formData as $key =>$val ){
					$consumerFriend = new ConsumerFriend();
					if((substr($key, 0,6) == 'friend')&& ($val != '')){
						$friend = $consumerFriend->createRow();
						$friend->consumer = $id;
						$friend->campaign = $campaign;
						$friend->friend = $val;
						$friend->date = date('m-d-Y H:i:s');
						$friend->save();
					}
				}*/
				//2011-05-03 ham.bao add the related friends 
				$campaign_model = new Campaign();
				$campaign_campaign=$campaign_model->find($campaign)->current();
//				Zend_Debug::dump($campaign_campaign->relative);die;
				//new
				for ($i=1;$i<=$campaign_campaign->relative;$i++){
					if ($formData['friend_name_'.$i] && $formData['friend_name_'.$i]!='' ){
						$consumerFriend = new ConsumerFriend();
						$friend = $consumerFriend->createRow();
					    $friend->consumer = $id;
					    $friend->campaign = $campaign;
						$friend->name = $formData['friend_name_'.$i]; //change column name in db
						$friend->email = $formData['friend_email_'.$i]; //add column in db
						$friend->message = $formData['friend_message_'.$i];//add column in db
						$friend->date = date('Y-m-d H:i:s');
						$friend->save();
					}
				} 
				
				$this->_forward('description','campaign',null,array('id'=>$campaign));
			}else{
				$this->view->errMessage = "Please fill out all mandatory fields and make sure your emails are correct!";
				$this->_forward('precampaignfinished',
                                       'campaign',
                                       null,
                                       array('survey' => '643'));		
			}
		} else {
			
		}
	}

	function admindeleteAction() {
		$this->view->title = "Delete Consumer";

		if ($this->_request	->isPost ()) {
			$id = ( int ) $this->_request->getPost ( 'id' );
			$del = $this->_request->getPost ( 'del' );
			$consumerModel = new Consumer ();
			if ($del == 'Yes' && $id > 0) {
				$where = 'id = ' . $id;
				$consumerModel->delete ( $where );
			}
				
			$this->view->consumers = $consumerModel->fetchAll ();
			$this->_redirect ( 'consumer/index' );
		} else {
			$id = ( int ) $this->_request->getParam ( 'id' );
			if ($id > 0) {
				$consumerModel = new Consumer ();
				$this->view->consumer = $consumerModel->fetchRow ( 'id=' . $id );
			}
		}
	}

	function changepasswordAction() {
		$this->view->activeTab = 'Profile';
		$this->view->title = $this->view->translate ( "Wildfire" ) . " - " . $this->view->translate ( "Change_Password" );
		$form = new PasswordForm ();
		$consumerModel = new Consumer ();

		if ($this->_request->isPost ()) { //POST
			$formData = $this->_request->getPost ();
			if ($form->isValid ( $formData )) {
				if ($formData ['newpassword'] == $formData ['repeat']) {
					$id = $this->_currentUser->id;
					$consumer = $consumerModel->find ( $id )->current ();
					if ($consumer->password == md5 ( $formData ['oldpassword'] )) {
						$consumer->password = md5 ( $form->getValue ( 'newpassword' ) );
						$consumer->save ();
						$form->populate ( $consumer->toArray () );
						$this->view->showMessage = $this->view->translate ( 'Save_Successfully' );
					} else {
						$this->view->showMessage = $this->view->translate ( 'Password_is_wrong' );
					}
				} else {
					$this->view->showMessage = $this->view->translate ( 'New_password_and_repeat_must_be_consistent' );
				}
			}

			//			Zend_Debug::dump($this->veiw->showMessage);
		} else { //GET
			$email = $this->_currentUser->email;
			$form->setDefault ( 'email', $email );
		}
		$this->view->form = $form;
	}
	
	function showAction() {
		$this->view->activeTab = 'Profile';
		$this->view->title = $this->view
		->translate ( "Wildfire" ) . " - " . $this->view
		->translate ( "PROFILE" );

		$this->view->consumer = $this->_currentUser;

	}
	
	function adminconfirmaddressAction() {
		// get
		$rowsPerPage = 50;
		$curPage = 1;
		$this->_helper->layout
		->setLayout ( "layout_admin" );
		if ($this->_request
		->getParam ( 'page' )) {
			$curPage = $this->_request
			->getParam ( 'page' );
		}

		$consumerModel = new Consumer ();
		$consumers = $consumerModel->fetchAll ( "address1 is not null and address1 != '' and (province is null or city is null) and pest is null and language_pref != 'en'", 'id desc' )
		->toArray ();
		// parse
		$this->view->addressArray = array ();
		$this->view->confirmedaddressArray = array ();
		$this->view->addressesStr = '';
		$municipalityArray = array ('北京', '上海', '天津', '重庆' );
		$provinceArray = array ('广东', '江苏', '浙江', '四川', '海南', '福建', '山东', '江西', '广西', '安徽', '河北', '河南', '湖北', '湖南', '陕西', '山西', '黑龙江', '辽宁', '吉林', '云南', '贵州', '甘肃', '内蒙', '宁夏', '西藏', '新疆', '青海', '香港', '澳门', '台湾', '国外' );
		$arraylen = count ( $consumers );
		for($i = 0; $i < $rowsPerPage && $i < $arraylen; $i ++) {
			$consumer = $consumers [($curPage - 1) * $rowsPerPage + $i];
			$isParsed = false;
			if ($consumer ['city'] == null || $consumer ['city'] == '' || $consumer ['province'] == null || $consumer ['province'] == '') {
				$addressStr = 'T' . $consumer ['address1'];
				foreach ( $municipalityArray as $municipality ) {
					if (strpos ( $addressStr, $municipality )) {
						$this->view->addressArray [$consumer ['id']] ['province'] = $this->view->addressArray [$consumer ['id']] ['city'] = $municipality;
						$isParsed = true;
					}
				}
				if (! $isParsed) {
					$this->view->addressArray [$consumer ['id']] ['province'] = null;
					foreach ( $provinceArray as $province ) {
						if (strpos ( $addressStr, $province )) {
							$this->view->addressArray [$consumer ['id']] ['province'] = $province;
						}
					}
					if ($this->view->addressArray [$consumer ['id']] ['province'] != null) {
						preg_match ( '/[^\x00-\x7F]+市/', $addressStr, $city );
						if ($city != null) {
							$this->view->addressArray [$consumer ['id']] ['city'] = substr ( $city [0], strlen ( $city [0] ) - 9, 9 );
						}
						$isParsed = true;
					}
				}
			} else {
				$this->view->confirmedaddressArray [$consumer ['id']] = 'yes';
				$this->view->addressArray [$consumer ['id']] ['province'] = $consumer ['province'];
				$this->view->addressArray [$consumer ['id']] ['city'] = $consumer ['city'];
				$this->view->addressesStr .= $consumer ['id'] . "," . $this->view->addressArray [$consumer ['id']] ['province'] . "," . $this->view->addressArray [$consumer ['id']] ['city'] . ";";
				$isParsed = true;
			}
			if ($isParsed) {
				$this->view->addressesStr .= $consumer ['id'] . "," . $this->view->addressArray [$consumer ['id']] ['province'] . "," . $this->view->addressArray [$consumer ['id']] ['city'] . ";";
			}
		}
		//		Zend_Debug::dump($this->view->addressArray);
		//paging
		$this->view->controller = $this->_request
		->getControllerName ();
		$this->view->action = $this->_request
		->getActionName ();
		$paginator = new Zend_Paginator ( new Zend_Paginator_Adapter_Array ( $consumers ) );
		$paginator->setCurrentPageNumber ( $curPage )
		->setItemCountPerPage ( $rowsPerPage );
		$this->view->paginator = $paginator;
		//set the No. inital value in view page
		$this->view->NoInitValue = ($curPage - 1) * $rowsPerPage + 1;

	}

	function adminajaxAction() {
		// post
		if ($this->_request
		->isPost ()) {
			$formData = $this->_request
			->getPost ();
				
			$keysArray = array_keys ( $formData );
			//			$this->_helper->json($formData);
			$table = new Consumer ();
			for($i = 0; $i < count ( $keysArray ); $i ++) {
				$provinceArray = explode ( '_', $keysArray [$i] );
				if ($provinceArray [0] == 'province' && $formData [$keysArray [$i]] != '') {
					$id = $provinceArray [1];
					$province = $formData [$keysArray [$i]];
					$i ++;
					$cityArray = explode ( '_', $keysArray [$i] );
					if ($cityArray [0] == 'city' && $formData [$keysArray [$i]] != '') {
						$city = $formData [$keysArray [$i]];
					} else {
						continue;
					}
					$row = $table->fetchRow ( "id = '" . $id . "'" );
					$row->province = $province;
					$row->city = $city;
					$row->save ();
				} else {
					continue;
				}
			}
		}

		// get
	}
	
	function profileAction() {
		$this->_helper->layout->setLayout ( "layout_admin" );
		$uid 		= $this->_request->getParam ( 'uid' );
		$campainId  = $this->_request->getParam ( 'campaign');
		if($campainId != ''){
			$campaignInvitationModel = new CampaignInvitation();
			$data = $campaignInvitationModel->fetchAll('consumer_id = '.$uid .' and campaign_id ='.$campainId);
			if(count($data)==0){
				$row = $campaignInvitationModel->createRow();
				$row->campaign_id = $campainId;
				$row->consumer_id = $uid;
				$row->create_date = date("Y-m-d H:i:s");
				$row->state 	  = "NEW";
				$row->save();				
			}
			$this->_helper->redirector('profile','consumer','',array('uid'=>$uid));
		}
		$search = $this->_request->getParam('search');
		$this->view->search =$search;
		$campaignInfo = array ();
		$reportsInfo = array ();
		$userModel = new Consumer ();
		if ($this->_request->getParam ( 'resetpassword' )) {
			$userModel->update ( array ('password' => md5 ( '111111' ) ), 'id =' . $uid );
			$this->_helper->redirector ( 'profile', 'consumer', null, array ('uid' => $uid ) );
		}
		$profile = $userModel->fetchRow ( 'id =' . $uid );
		$userExtraInfo = new ConsumerExtraInfo();
		$extra_profile = $userExtraInfo->fetchRow (' consumer_id = ' . $uid);
		$this->view->profile = $profile;
		$this->view->extra_profile = $extra_profile;
//		var_dump($extra_profile);die;

		$db = Zend_Registry::get ( 'db' );
		$select = $db->select ();
		$select->from ( "campaign", array ('campaign.*', 'campaign_participation.state','campaign_invitation.state as cstate','campaign_invitation.id as ciid'  ));
		$select->joinLeft ( "campaign_invitation", "campaign.id=campaign_invitation.campaign_id and campaign_invitation.consumer_id = ".$uid, null );
		$select->joinLeft ( "campaign_participation", "campaign_invitation.id=campaign_participation.campaign_invitation_id", "campaign_participation.accept_date" );
		//$select->where ( "campaign_invitation.state = 'ACCEPTED'" );
		//$select->where ( " campaign_invitation.consumer_id = ?", $uid );
		$select->order ( 'campaign_participation.accept_date desc' );
		$campaignsAll = $db->fetchAll ( $select );
		$this->view->campaigns = $campaignsAll;
		
		//survey
		$db = Zend_Registry::get('db');
		$select_survey = $db->select();	
		$select_survey->from('profile_survey', array('name', 'english_name','state'));
		$select_survey->join('poll_participation', 'poll_participation.poll_id = profile_survey.id', 'poll_participation.date');
		$select_survey->join('reward_point_transaction_record', 'reward_point_transaction_record.date = poll_participation.date and reward_point_transaction_record.consumer_id = poll_participation.consumer_id', 'point_amount');
		$select_survey->where('reward_point_transaction_record.transaction_id =3');
		$select_survey->where('poll_participation.consumer_id = ?', $uid);
		$select_survey->order('poll_participation.date desc');
		$surveysAll = $db->fetchAll($select_survey);
		$this->view->surveysall=$surveysAll;
		
		// point detail
		/*$db = Zend_Registry::get('db');
		$select_pointdetail = $db->select();
		$select_pointdetail->from();
		$select_pointdetail->join();
		$select_pointdetail->join();
		$select_pointdetail->where();
		$select_pointdetail->where();
		$select_pointdetail->order();
		$surveysAll = $db->fetchAll($select_pointdetail);*/
		
		//var_dump($campaignsAll);die;
		$selectTotal = $db->select ();
		$selectTotal->from ( "report", array ('count(id) as num', 'campaign_id' ) );
		$selectTotal->where ( "consumer_id =" . $uid );
		$selectTotal->group ( 'campaign_id' );
		$totalData = $db->fetchAll ( $selectTotal );
		if (count ( $totalData )) {
			foreach ( $totalData as $val ) {
				$reportsInfo [$val ['campaign_id']] = $val ['num'];
			}
		}	
		$this->view->totalreports = $reportsInfo;
		// total points
		$this->view->totalPoints = $db->fetchOne ( "SELECT sum(point_amount) FROM reward_point_transaction_record WHERE transaction_id!=4 and consumer_id = :temp", array ('temp' => $uid ) );
		if (empty ( $this->view->totalPoints )) {
			$this->view->totalPoints = 0;
		}
		//redeem points
		$this->view->redeemPoints = $db->fetchOne ( "SELECT sum(point_amount) FROM reward_point_transaction_record WHERE consumer_id = :temp", array ('temp' => $uid ) );
		if (empty ( $this->view->redeemPoints )) {
			$this->view->redeemPoints = 0;
		}
		//usable points
		$today = date("Y-m-d" , time());
		$this->view->usablePoints =  $db->fetchOne(
    		"SELECT sum(point_amount) FROM reward_point_transaction_record WHERE (consumer_id = :temp and date <:temp2) or (consumer_id = :temp and date >=:temp2 and transaction_id=4) ",
			array('temp' =>$uid,'temp2'=>date("Y-m-d",strtotime("$today   -30   day")))
		);
		if (empty($this->view->usablePoints)){ 
			$this->view->usablePoints=0;
		}
		$redeemSelect = $db->select ();
		$redeemSelect->from ( 'product_order', array ('product_order.amount as amount', 'product_order.create_date', 'product_order.state as pstate', 'product_order.id as pid', 'product.name', 'reward_point_transaction_record.point_amount' ) );
		$redeemSelect->join ( 'product', 'product_order.product_id=product.id' );
		$redeemSelect->join ( 'reward_point_transaction_record', 'product_order.reward_point_transaction_record_id=reward_point_transaction_record.id' );
		$redeemSelect->where ( 'product_order.consumer_id=?', $uid );

		$this->view->redeem = $db->fetchAll ( $redeemSelect );

		$translate = new Zend_Translate ('array',Array( "Value is required and can't be empty"=>$this->view->translate('validation_null')));
		$form = new ConsumerSearchForm ();
		$form->setTranslator ( $translate );
		if ($this->_request
		->getParam ( 'search' ) != null) {
			$search = $this->_request->getParam ( 'search' );
			$form->search->setValue($search);
		}
		$this->view->form = $form;
	}

	function smshistoryAction(){
		$this->_helper->layout->disableLayout ();
		$idValue = explode('&',$this->_request->getParam('uid'));
		$uid = $idValue[0];
		$incomingSmsDataArray = array();
		$sentSmsDataArray     =array();
		$sparkSmsModel = new SparkSms ();
		$incomingSmsData = $sparkSmsModel->fetchAll('consumer_id='.$uid);
		$db = Zend_Registry::get ( 'db' );
		$sentSmsSelect = $db->select ();
		$sentSmsSelect->from('sent_sms',array('sent_sms.*'));
		$sentSmsSelect->join('consumer','consumer.phone = sent_sms.to and consumer.id='.$uid);
		$sentSmsSelect->order('sent_sms.time DESC');
		$sentSmsData = $db->fetchAll ( $sentSmsSelect );

		if(count($incomingSmsData)){
			foreach($incomingSmsData as $data){
				$incomingSmsDataArray[] = $data->toArray();
			}
		}
//		if(count($sentSmsData)){
//			foreach($sentSmsData as $data){
//				$sentSmsDataArray[] = $data->toArray();
//			}
//		}
		//按照时间排序
		$newSmsData=array();
		$count=0;
		for($i=count($incomingSmsDataArray)-1,$j=0;($i>=0||$j<count($sentSmsData));){
			if($i<count($incomingSmsDataArray)&&$j<count($sentSmsData)){
				$income_time=$incomingSmsDataArray[$i]['time'];
				$sent_time=$sentSmsData[$j]['time'];
				if($incomingSmsDataArray[$i]['time']>=$sentSmsData[$j]['time']){
					$newSmsData[$count]=$incomingSmsDataArray[$i];
					$i--;
				}else{
					$newSmsData[$count]=$sentSmsData[$j];
					$j++;
				}
			}elseif ($i>=count($incomingSmsDataArray)&&$j<count($sentSmsData)){
				$newSmsData[$count]=$sentSmsData[$j];
				$j++;
			}elseif ($i<count($incomingSmsDataArray)&&$j>=count($sentSmsData)){
				$newSmsData[$count]=$incomingSmsDataArray[$i];
				$i--;
			}
			$count++;
		}
		$this->view->sms = $newSmsData;
	}
	
	function urlreportAction(){
		$this->_helper->layout->disableLayout ();
		$idValue = explode('&',$this->_request->getParam('uid'));
		$uid = $idValue[0];
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('url_report',array('id', 'url', 'state', 'create_date'))
		->join('campaign', 'campaign.id = url_report.campaign_id', array( 'name'))
		->joinLeft('url_report_reply', 'url_report_reply.url_report_id = url_report.id', 'content')
		->where('url_report.consumer_id = ?', $uid)
		->order('create_date desc');
		$this->view->urlReports = $db->fetchAll($select);
		
		$selectDuplicatedUrlReport = $db->select();
		$selectDuplicatedUrlReport->from('url_report', 'url')
		->group('url')
		->having('count(*) > 1');
		$duplicatedUrlReport = $db->fetchAll($selectDuplicatedUrlReport);
		$this->view->duplicatedUrlArray = array();
		foreach($duplicatedUrlReport as $urlReport){
			$this->view->duplicatedUrlArray[$urlReport['url']] = '0';
		}
		//var_dump($this->view->urlReports);
	}

	function ajaxeditAction() {
		$this->_helper->layout->disableLayout ();
		$uid = $this->_request->getParam ( 'uid' );
		$postData = $this->_request->getPost ();
		$consumerModel = new Consumer ();
		$consumerExtraModel = new ConsumerExtraInfo();
		if ($postData ['field']== "birthdate" || $postData ['field']== "education" || $postData ['field']== "have_children"|| $postData ['field']== "children_birth_year"|| $postData ['field']== "income" || $postData['field']=="status" ){
			$consumerExtraModel->update (array($postData ['field'] => $postData ['value'] ), 'consumer_id = ' . $uid );
		}else {
			$consumerModel->update ( array ($postData ['field'] => $postData ['value'] ), 'id = ' . $uid );
		}
		$this->_helper->json("");
	}

	function ajaxchangeorderstateAction(){
        $this->_helper->layout->disableLayout ();
		$uid = $this->_request->getParam ( 'uid' );
		$postData = $this->_request
		->getPost ();
		$productOrderModel = new ProductOrder();
		$productOrderModel->update( array('state' => $postData['state']),'id = ' . $uid);
		$this->_helper->json("Success");

	}

	function ajaxaddrewardAction() {
		$this->_helper->layout->disableLayout ();
		$uid = $this->_request->getParam ( 'uid' );
		$postData = $this->_request->getPost ();
		if (is_numeric ( $postData ['score'] )) {
			$rewardPointTransactionRecordModel = new RewardPointTransactionRecord ();
			$rewardPointTransactionRecordModel->insert ( array ('consumer_id' => $uid, 'transaction_id' => 7, 'point_amount' => $postData ['score'], 'date' => date ( "Y-m-d H:i:s" ) ) );
			$rankModel = new Rank();
			$rankModel->changeConsumerRank($uid);
			$this->_helper->json("Success");
		} else {
			$this->_helper->json("Wrong");

		}
	}
	
	function adminsearchAction() {
		$this->_helper->layout
		->setLayout ( "layout_admin" );
		$translate = new Zend_Translate ('array',Array( "Value is required and can't be empty"=>$this->view->translate('validation_null')));
		$form = new ConsumerSearchForm ();
		$form->setTranslator ( $translate );
		$this->view->form = $form;
		
//        $campaignsModel = new Campaign();
//        $campaigns = $campaignsModel->fetchAll();
        
		if ($this->_request->getParam ( 'search' ) != null) {
			$search = $this->_request->getParam ( 'search' );
			$consumerModel = new Consumer ();
			$consumers = array ();
			$consumers = $consumerModel->fetchAll ( 'email like "%' . $search . '%" or recipients_name like "%' . $search . '%" or name like "%' . $search . '%" or city like "%' . $search . '%" or province like "%' . $search . '%" or phone like "%' . $search . '%" or login_phone like "%'.$search.'%"' );
			if (count ( $consumers ) == 1) {
				$consumer = $consumers [0];
				$this->_helper
				->redirector ( 'profile', 'consumer', null, array ('uid' => $consumer ['id'] ) );
			}
			if ($consumers [0] != null) {
				$this->view->tip = true;
			}
			$form->search->setValue($search);
			$this->view->search =$search;
			$this->view->consumers = $consumers;
			$this->view->isPost = true;
		}
		if ($this->_request->isPost ()) {
			$formData = $this->_request->getPost ();
			if ($form->isValid ( $formData )) {
				$search = $form->getValue ( 'search' );
				$consumerModel = new Consumer ();
				$consumers = array ();
				$consumers = $consumerModel->fetchAll ( 'email like "%' . $search . '%" or recipients_name like "%' . $search . '%" or name like "%' . $search . '%" or city like "%' . $search . '%" or province like "%' . $search . '%" or phone like "%' . $search . '%"' );
				$form->search->setValue($search);
				if (count ( $consumers ) == 1) {
					$consumer = $consumers [0];
					$this->_helper
					->redirector ( 'profile', 'consumer', null, array ('uid' => $consumer ['id'] ,'search' => $search) );
				}
				if ($consumers [0] != null) {
					$this->view->tip = true;
				}
				$this->view->consumers = $consumers;
				if(count($consumers) == 0){
                     $inviteEmailModel = new InvitationEmail();
                     $inviteEmailData =  $inviteEmailModel->fetchAll(' invitation_email.to like "%'.$search.'%"');
                     $this->view->inviteEmail = $inviteEmailData;
				}
				$this->view->search =$search;
				$this->view->isPost = true;
			}
		}
	}
	
	function ajaxemailcontentAction(){
		$this->_helper->layout->disableLayout ();
		$idValue = explode('&',$this->_request->getParam('uid'));
		$uid = $idValue[0];
        $inviteEmailModel = new InvitationEmail();
        $inviteEmailData =  $inviteEmailModel->fetchAll(' id  ='.$uid);
        $this->view->inviteEmail = $inviteEmailData[0];
        //print_r($this->view->inviteEmail);die;
	}

	function ajaxqqconversationAction() {
		$this->_helper->layout->disableLayout ();
		if ($this->_request->getParam ( 'uid' )) {
			$idValue = explode('&',$this->_request->getParam('uid'));
			$uid = $idValue[0];
			$qqconversationModel = new QqConversation ();
			$qqconversation = $qqconversationModel->fetchAll ( 'consumer_id="' . $uid . '"' );
			if ($qqconversation)
			$this->view->qqconversation = $qqconversation;
		}
	}

	function ajaxphoneconversationAction() {
		$this->_helper->layout->disableLayout ();
		if ($this->_request->getParam ( 'uid' )) {
			$idValue = explode('&',$this->_request->getParam('uid'));
			$uid = $idValue[0];
			$phoneconversationModel = new PhoneConversation ();
			$phoneconversation = $phoneconversationModel->fetchAll ( 'consumer_id="' . $uid . '"' );
			if ($phoneconversation)
			$this->view->phoneconversation = $phoneconversation;
			$telephonelogModel = new TelephoneLog ();
			$telephonelog = $telephonelogModel->fetchAll ( 'consumer_id="' . $uid . '"' );
			if ($telephonelog)
			$this->view->telephonelog = $telephonelog;
		}
	}
	
	function ajaxreportAction(){
		$this->_helper->layout->disableLayout ();
		$config = Zend_Registry::get('config');
		$db = Zend_Registry::get('db');
		$select = $db->select();
		
		//tag for report
        $selectTags = $db->select();
        $selectTags->from('tags', array('name','id','sort'))
        ->where("module = 'REPORT'")
        //->where("module = 'REPORT'")       
        ->order('sort');
        $this->view->tags = $db->fetchAll($selectTags);
        
        //organize tag list
		$tagHash = array();
		foreach ($this->view->tags as $tag){
			$tagHash[$tag['id']] = $tag['name'];
		}
		
    	// get reports
		$select->from('report', array('id', 'accesscode','create_date'))
		->where('consumer_id = ?',$this->_request->getParam ( 'uid' ))
		->order('create_date desc');
		$oldreportArray = $db->fetchAll($select);
		
		$replyModel = new Reply();
		$this->view->oldreports = array();
		$i = 1;
		foreach($oldreportArray as $oldreport){
			$oldTags = '';

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
	
	function adminadvancedsearchAction(){
		$this->_helper->layout->setLayout ( "layout_admin" );
		$campaigns = new Campaign();
		$this->view->campaigns = $campaigns->fetchAll();	
	}
	
	
	function rankAction(){
		$rankModel = new Rank();
		$rankData  = $rankModel->fetchAll();
		$this->view->ranks = $rankData;
	}
	
	function adminaddtagsAction(){
		$type = $this->_request->getParam('type');
		$tags = $this->_request->getParam('tags');
		$consumerId = $this->_request->getParam('id');
		//die($tags);
		$db = Zend_Registry::get('db');
		if($type == 'add'){
			$db->query("update consumer set tags = concat(consumer.tags,'$tags,') where id in ( " . $consumerId . ")");
		}elseif ($type == 'delete'){
			$db->query("update consumer set tags = replace(consumer.tags,'$tags,','') where id in ( " . $consumerId . ")");
		}
		$this->_helper->json('Success');
	}
}