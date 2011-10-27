<?php

class ImageController extends MyController
{
	function getimageAction() {
        $imageReportModel = new ImageReport();
//        $campaign_id = (int)$this->_request->getParam('id');
//        $url = $this->_request->getParam('url');
//        
//        $db = Zend_Registry::get('db');
//		$select = $db->select();
//		$select->from('image_report', '*')
//		->where('campaign_id = ?', $campaign_id)
//		->where('consumer_id = ?', $this->_currentUser->id)
//		->order('create_date DESC');
//		
//		$imageReport = $db->fetchRow($select);

        $id = (int)$this->_request->getParam('id');
        $url = $this->_request->getParam('url');
        $db = Zend_Registry::get('db');
        $select = $db -> select();
        $select ->from('image_report', 'image')
                ->where('consumer_id = ?', $this->_currentUser->id)
                ->where('id = ?', $id);
		$imageReport = $db->fetchRow($select);
        header("Content-type:image/jpeg");
        $this->view->image = $imageReport['image'];
        $this->_helper->layout->disableLayout();
	}
	
	function getimagebyimagereportidAction(){
		$imageReportModel = new ImageReport();
		$id = (int)$this->_request->getParam('id');
		$imageReport = $imageReportModel->fetchRow("id = ".$id);
        
        header("Content-type:".$imageReport->type);
//		header("Content-type:image/jpeg");
        $this->view->image = $imageReport->image;
        $this->_helper->layout->disableLayout();
	}
	function adminimagereportAction(){
		
		$this->view->title = 'Reports';
		$this->view->activeTab = 'Reports';
		$this->_helper->layout->setLayout("layout_admin");
		$this->view->campaign_id = $this->_request->getParam('id');
		
		$curPage = 1;
		$rowsPerPage = 50;
		if($this->_request->getParam('page'))
        {
        	$curPage = $this->_request->getParam('page');
        }
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('image_report',array('id', 'image', 'state', 'create_date', 'consumer_id', 'thumb_width', 'thumb_height'))
		->join('consumer', 'consumer.id = image_report.consumer_id', array('email', 'name', 'recipients_name', 'language_pref'))
		->joinLeft('image_report_reply', 'image_report_reply.image_report_id = image_report.id', 'content')
		->joinLeft('reward_point_transaction_record', 'reward_point_transaction_record.id = image_report.reward_point_transaction_record_id', 'point_amount')
		->where('campaign_id = ?', $this->view->campaign_id)
		->where('consumer.pest is null')
		->order('create_date desc');
		$this->view->imageReports = $db->fetchAll($select);
		//paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($this->view->imageReports));
		$paginator->setCurrentPageNumber($curPage)
		->setItemCountPerPage($rowsPerPage); 
		$this->view->paginator = $paginator; 
//		Zend_Debug::dump($this->view->duplicatedUrlArray);
		Zend_View_Helper_PaginationControl::setDefaultViewPartial('pagination/pagelist.phtml');
		$this->view->NoInitValue = ($curPage-1)*$rowsPerPage+1;
	}
	function adminsaveimagereportstateAction(){
		$type = $this->_request->getParam('type');
		$db = Zend_Registry::get('db');
		if($type == 'reportState'){
			$idStr = $this->_request->getParam('reportids');
			$idStrArray = explode(',',$idStr);
			$reportIdArray = array();
			$i = 0;
			foreach($idStrArray as $idAndState){
				if($idAndState == ''){
					continue;
				}
				$idAndStateArray = explode('@',$idAndState);
				if($idAndStateArray[1] == 'NEW'){
					continue;
				}
				if($idAndStateArray[1] == 'APPROVED'){
					$imagereportModel = new ImageReport();
					$row = $imagereportModel->fetchRow('id = '.$idAndStateArray[0]);
					if($row->state != 'NEW'){
						continue;
					}
					$row->state = $idAndStateArray[1];
					$row->save();
					
				}
				if($idAndStateArray[1] == 'UNAPPROVED'){
					$imagereportModel = new ImageReport();
					$row = $imagereportModel->fetchRow('id = '.$idAndStateArray[0]);
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
					$row->state = $idAndStateArray[1];
					$row->save();
				}
			}
		}
	}
	function adminsaveimagereportpointAction(){
		
		$type = $this->_request->getParam('type');
		$db = Zend_Registry::get('db');
		if($type == 'reportPoint'){
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
				if($idAndStateArray[4] == '' || $idAndStateArray[4] == '0'){
					$imagereportModel = new ImageReport();
					$row = $imagereportModel->fetchRow('id = '.$idAndStateArray[0]);
					if($row == null){
						continue;
					}
					if($row->reward_point_transaction_record_id != null && $row->reward_point_transaction_record_id != ''){
						$rewardModel = new RewardPointTransactionRecord();
						$reward = $rewardModel->fetchRow('id = '.$row->reward_point_transaction_record_id);
						if($reward != null){
							$reward->point_amount = $idAndStateArray[4];
							$reward->save();
						}
					}
					$row->state = $idAndStateArray[1];
					$row->save();
					
				}else{
					$imagereportModel = new ImageReport();
					$row = $imagereportModel->fetchRow('id = '.$idAndStateArray[0]);
					$row->state = $idAndStateArray[1];
					if($row->reward_point_transaction_record_id == null || $row->reward_point_transaction_record_id == ''){
						$rewardModel = new RewardPointTransactionRecord();
						$reward = $rewardModel->createRow();
						$reward->consumer_id = $idAndStateArray[2];
						$reward->date = date("Y-m-d H:i:s");
						$reward->transaction_id = 9;
						$reward->point_amount = $idAndStateArray[4];
						$row->reward_point_transaction_record_id = $reward->save();
					}else{
						$rewardModel = new RewardPointTransactionRecord();
						$reward = $rewardModel->fetchRow('id = '.$row->reward_point_transaction_record_id);
						if($reward != null){
							$reward->date = date("Y-m-d H:i:s");
							$reward->point_amount = $idAndStateArray[4];
							$reward->save();
						}
					}
					$row->save();
					//2011-05-13 change the rank of consumer 
					$rankModel = new Rank();
					$rankModel->changeConsumerRank($idAndStateArray[2]);
				}
				$consumerModel = new Consumer();
				$consumer = $consumerModel->fetchRow('id = '.$idAndStateArray[2]);
				
				
				//send mail...
				$mail = new Zend_Mail('utf-8');
				if($consumer->language_pref != null && $consumer->language_pref == 'en'){
					$emailSubject = $this->view->translate('Admin_Reply_WOM_IMAGEReport_Subject_en');
					$emailBody = $this->view->translate('Admin_Reply_WOM_IMAGEReport_Body_en');
				}else{
					$emailSubject = $this->view->translate('Admin_Reply_WOM_IMAGEReport_Subject_zh');
					$emailBody = $this->view->translate('Admin_Reply_WOM_IMAGEReport_Body_zh');
				}
				$campaignModel = new Campaign();
				$campaign = $campaignModel->fetchRow('id = '.$row->campaign_id);
				$stringChange = array(
					'?USERNAME?' => $consumer->name,
					'?CAMPAIGN?' => $campaign->name,
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
				$imagereportreplyModel = new ImageReportReply();
				$imagereportreply = $imagereportreplyModel->createRow();
				$imagereportreply->date = date("Y-m-d H:i:s");
				$imagereportreply->subject = $emailSubject;
				$imagereportreply->content = $idAndStateArray[3];
				$imagereportreply->from = $config->smtp->report->mail->username;
				$imagereportreply->to = $consumer->email;
				$imagereportreply->image_report_id = $row->id;
				//2011-04-08 ham.bao separate the sessions with admin
				$imagereportreply->admin_id = $this->_currentAdmin->id;
				$imagereportreply->save();
			}	
		}
	}
}

