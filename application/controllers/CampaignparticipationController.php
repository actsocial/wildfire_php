<?php

class CampaignparticipationController extends MyController {
	function adminindexAction() {
		$this->view->title = "All Participations";
		$this->view->activeTab = "List Participations";
		
		$this->campaign_id = $this->_request->getParam ( 'id' );
		$this->tag         = $this->_request->getParam ( 'tag' );
		
		$db = Zend_Registry::get ( 'db' );
		$select = $db->select ();
		
		$select->from ( 'campaign', null );
		$select->join ( 'campaign_invitation', 'campaign.id = campaign_invitation.campaign_id', 'state' );
		if ( $this->tag ) {
			$select->join ( 'consumer', "consumer.id = campaign_invitation.consumer_id and tags like '%$this->tag,%'" );
		}else{
			$select->join ( 'consumer', "consumer.id = campaign_invitation.consumer_id " );
		}
		
		$select->join ( 'campaign_participation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id', array ('accept_date', 'state' ) );
		$select->where ( 'campaign_invitation.campaign_id = ?', $this->campaign_id )
			->where ( 'consumer.pest is null or consumer.pest != 1' );

		
		$this->view->campaignParticipations = $db->fetchAll ( $select );
		$this->view->campaign_id = $this->campaign_id;
		$this->view->tag = $this->tag;
		//		$this->_helper->layout->disableLayout();
		
        //print_r($this->view->campaignParticipations);die;
		$this->_helper->layout
			->setLayout ( "layout_admin" );
			
		//extract the profile survey
		$profileSurvey =new ProfileSurvey();
		$this->view->profilesurvey = $profileSurvey->fetchAll('id !=12');
		
	}
	
	function adminexportdataAction() {
		$this->_helper->layout
			->disableLayout ();
		$idValue = explode ( '&', $this->_request
			->getParam ( 'id' ) );
		$this->campaign_id = $idValue [0];
		$campaignModel = new Campaign ();
		$this->view->campaign = $campaignModel->fetchRow ( 'id = ' . $this->campaign_id );
		$this->view->flag = 0;
		if ($this->_request
			->isPost ()) {
			$postData = $this->_request
				->getPost ();
			$this->view->flag = 1;
			$db = Zend_Registry::get ( 'db' );
			$select = $db->select ();
			
			$select->from ( 'campaign', null );
			$select->join ( 'campaign_invitation', 'campaign.id = campaign_invitation.campaign_id', 'state' );
			$select->join ( 'consumer', "consumer.id = campaign_invitation.consumer_id" );
			switch ($postData ['action']) {
				case 'all' :
					$select->join ( 'campaign_participation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id', array ('accept_date', 'state' ) );
					break;
				case 'sent' :
					$select->join ( 'campaign_participation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id and campaign_participation.state="KIT SENT"', array ('accept_date', 'state' ) );
					break;
				case 'notsent' :
					$select->join ( 'campaign_participation', 'campaign_invitation.id = campaign_participation.campaign_invitation_id and campaign_participation.state<>"KIT SENT"', array ('accept_date', 'state' ) );
					
					break;
			}
			
			$select->where ( 'campaign_invitation.campaign_id = ?', $this->campaign_id )
				->where ( 'consumer.pest is null or consumer.pest != 1' );
			
			$campaignParticipations = $db->fetchAll ( $select );
//			Zend_Debug::dump($campaignParticipations);die();
			$campaignUsers = array ();
			
			$file = $this->campaign_id . '_' . date ( 'Y-m-d_H_i_s' ) . "participation.csv";
			
			if ($campaignParticipations) {
				$i = 0;
				foreach ( $campaignParticipations as $val ) {
					$campaignUsers [] = array ($i, $val ['name'], $val ['email'] , $val ['login_phone'], $val ['recipients_name'], $val ['province'], $val ['city'], $val ['address1'], $val ['phone'], $val ['id'], $val ['accept_date'] );
					$i ++;
				}
				$header = array ('No.', 'Name', 'Email', 'Telephone', 'Recipients_name', 'Province', 'City', 'Address', 'Phone', 'UserID', 'Date' );
				
				$handle = fopen ( dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/public/csv/' . $file, "w" );
				Zend_Debug::dump(dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/public/csv/' . $file);die();
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

}