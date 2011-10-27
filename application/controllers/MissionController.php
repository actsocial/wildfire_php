<?php

class MissionController extends MyController
{
    function indexAction()
    {
//        $this->_helper->layout->disableLayout();
        //get top 3 missions
        $missionTable = new Missions();
        $order = "create_date desc";
        $this->view->missions = $missionTable->fetchAll(null, $order, 3, null);
        //get top 3 brands
        $brandTable = new Brands();
        $this->view->brands = $brandTable->fetchAll(null, $order, 2, null);

    }

    function descriptionAction()
    {
//      $this->_helper->layout->disableLayout();
        $db = Zend_Registry::get('db'); 
        $campaign_id = $this->_request->getParam('id');
        
        $currentTime = date("Y-m-d H:i:s");
        
        $campaignModel = new Campaign();
        $campaign      = $campaignModel->fetchRow('id ='.$campaign_id);
        
        $campaignInvitation = new CampaignInvitation();
        $invitation         = $campaignInvitation->fetchRow("campaign_id = $campaign_id and campaign_invitation.consumer_id = " . $this->_currentUser->id);
        
        if(($campaign->public == 1)&&(!count($invitation))){
        	$campaignInvitation = new CampaignInvitation();
        	$row                = $campaignInvitation->createRow();
        	$row->consumer_id   = $this->_currentUser->id;
        	$row->campaign_id   = $campaign_id;
        	$row->state         = 'NEW';
        	$row->create_date   = $currentTime;
        	$row->save();        	
        }
        
         if(($campaign->public == 0)&&(!count($invitation))){
         	$this->_helper->redirector('index', 'home');
         }
        //
        $campaignInvitation = new CampaignInvitation();
        $invitation         = $campaignInvitation->fetchRow("campaign_id = $campaign_id and campaign_invitation.consumer_id = " . $this->_currentUser->id);
        $this->view->state  = $invitation->state;
  
        //participate        
		$select = $db->select();
		$select->from('consumer', 'consumer.*');
		$select->join('campaign_invitation', 'consumer.id = campaign_invitation.consumer_id and campaign_invitation.campaign_id ='.$campaign_id,'campaign_id');
		$select->join('campaign_participation','campaign_invitation.id = campaign_participation.campaign_invitation_id','accept_date');
		$this->view->co = $db->fetchAll($select);
		
		$this->view->campaign_id = $campaign_id;
		
		//var_dump($this->view->activeMissions);die;
    }
    
    function paticipateAction(){
    	$this->_helper->layout->disableLayout();
        $campaign_id   = $this->_request->getParam('id');    
        $currentTime   = date("Y-m-d H:i:s");   
        $campaignModel = new Campaign();
        $campaign      = $campaignModel->fetchRow('id ='.$campaign_id);   

		$langNamespace = new Zend_Session_Namespace('Lang');
		$lang = $langNamespace->lang;
		if ($lang=='en'){
			$surveyId =	$campaign->i2_survey_id_en;
		}else{
			$surveyId =	$campaign->i2_survey_id;
		}
		$this->view->campaing_name = $campaign->name;
		$this->view->id = $campaign_id;
		$this->view->surveyId = $surveyId;
		
        $file          = $surveyId.'.phtml';        
        $this->view->file = $file;
        
		$this->view->includeCrystalCss = true;
		$this->_helper->layout->setLayout("layout_questionnaire");   	
   
    }

    function adminaddAction()
    {
        $this->_helper->layout->setLayout("layout_admin");
        $brandTable = new Brands();
        $order = "create_date desc";
        $this->view->brands = $brandTable->fetchAll(null, $order, null, null);
        if ($this->_request->isPost()) { //post method
            $formData = $this->_request->getPost();
            //get background image
            $imgfile = $_FILES['background'];
            $imgdata = null;
            if (is_array($imgfile)) {
                $name = $imgfile['name'];
                $type = $imgfile['type'];
                $size = $imgfile['size'];
                if(!preg_match('/^image\//i', $type) ? true : false) {
                    $this->view->error = "请上传正确的图片";
                } else if($size > 2000000) {
                    $this->view->error = "图片不得超过2M";
                } else {
                    $tmpfile = $imgfile['tmp_name'];
                    $file = fopen($tmpfile, "rb");
                    $imgdata = base64_encode(fread($file,$size));
                    fclose($file);

                    $this->view->error = "上传成功";
                }
            }
            //save mission
            $missionTable = new Missions();
            $newMission = $missionTable->createRecord($formData['title'],
                    $formData['intro'],$formData['type'],$formData['startdate'],
                    $formData['enddate'],$imgdata,$type,$formData['brand']);
            if ($newMission > 0) {
                $result = "Success";
                $this->_helper->redirector('adminlist','mission',null,
                        array('id' => $formData['brand']));
            }
        } else { //get method
        	$fc = Zend_Controller_Front::getInstance();
			$this->view->oFCKeditor = new FCKeditor("long_desc");
			$this->view->oFCKeditor->BasePath = $fc->getBaseUrl()."/js/fckeditor/";
			$this->view->oFCKeditor->Height = "500px";
			$this->view->oFCKeditor->Width = "700px";
			if($this->view->product['long_desc'] != null && $this->view->product['long_desc'] != ''){
			$this->view->oFCKeditor->Value= $this->view->product['long_desc'];
			}
        }
    }

    function adminlistAction()
    {
        $this->_helper->layout->setLayout("layout_admin");
        $this->view->title = "All Missions";
        $missionTable = new Missions();
        $order = "create_date desc";
        $this->view->missions = $missionTable->fetchAll(null, $order, null, null);
        $mission = $this->view->missions[0];
        Zend_Debug::dump($mission->findParentRow('Brands', 'BelongTo'));
        //TODO: paging
    }

    function admindetailAction()
    {
        $this->_helper->layout->setLayout("layout_admin");
        $this->view->title = "Mission Detail";
        
    }

    public function getimageAction()
    {
        $this->_helper->layout->disableLayout();
        $id = (int) $this->_request->getParam('id', 0);
        $missionTable = new Missions();
        $brand = $missionTable->find($id)->current();
        //Zend_Debug::dump($photo);
        header("Content-type:$brand->logo_type");
        $this->view->image = base64_decode($brand->logo);
    }

}
