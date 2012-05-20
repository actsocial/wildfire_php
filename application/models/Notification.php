<?php
class Notification extends Zend_Db_Table{
	protected $_name = "notification";
	
	public $WOM_REPORT_REPLY_TEMPLATE = array(
	  "type"=>"wom_report_reply",
	  "text"=>"您的口碑报告已经审核通过，您获得了#point积分",
	  "redirectionURL"=>"campaign/description/id/#campaignId"
	);
	
	public $WOM_REDEEM_POINT_TEMPLATE = array(
	  "type"=>"wom_redeem_point",
	  "text"=>"您已兑换#point积分",
	  "redirectionURL"=>"history/stuff"
	);
	
	public $WOM_RANK_UPGRADE_TEMPLATE = array(
	  "type"=>"wom_rank_upgrade",
	  "text"=>"恭喜您！您的等级已经提升，请继续努力！"
	);
	
	public $WOM_CAMPAIGN_INVITATION_TEMPLATE = array(
	  "type"=>"wom_campaign_invitation",
	  "text"=>"恭喜您！您已被邀请参加#campaign活动！",
	  "redirectionURL"=>"campaigninvitation/index"
	);
	
	public $WOM_PROFILE_SURVEY_TEMPLATE = array(
	  "type"=>"wom_profile_survey",
	  "text"=>"您已被邀请参加问卷活动.",
	  "redirectionURL"=>"profilesurvey/participate/id/#profile_survey_id"
	);
	
	public $WOM_CONSUMER_BADGE_TEMPLATE = array(
	  "type"=>"wom_consumer_badge",
	  "text"=>"恭喜您！您获得了一枚勋章，快去看看吧",
	);
	
	public function save($data) {
		if (null === ($data['id'])) {
			unset ($data['id']);
			return $this->insert($data);
		} else {
			return $this->update($data, array (
				'id = ?' => $data['id']
			));
		}
	}
	
	public function findBy(array $params) {
		$select = $this->select();
		foreach ($params as $key => $value) {
			$select->where($key . ' = ? ', $value);
		}
		return $this->fetchAll($select);
	}
	/*
	* USAGE:
	    $notificationModel = new Notification();
	    // add notification
	    $notificationModel->createRecord("REDEEM_POINT",$this->_currentUser->id,$total_redeem_point);
	*/
	function createRecord($template_name,$consumer_id,$param = null)
    {
        $template = null;
        $replaced_text = null;
        $replaced_url  = null;
        //Zend_Debug::dump($template_name);
    	if($template_name == null || $template_name == "") {
        	return null;
        } elseif($template_name == "REPORT_REPLY") {
        	$template = $this->WOM_REPORT_REPLY_TEMPLATE;
        	$replaced_text = str_replace("#point",$param,$template['text']);
        } elseif($template_name == "REDEEM_POINT") {
        	$template = $this->WOM_REDEEM_POINT_TEMPLATE;
        	$replaced_text = str_replace("#point",$param,$template['text']);
        } elseif($template_name == "RANK_UPGRADE") {
        	$template = $this->WOM_RANK_UPGRADE_TEMPLATE;
        } elseif($template_name == "CAMPAIGN_INVITATION") {
        	$template = $this->WOM_CAMPAIGN_INVITATION_TEMPLATE;
        } elseif($template_name == "PROFILE_SURVEY") {
        	$template = $this->WOM_PROFILE_SURVEY_TEMPLATE;
        	$replaced_url = str_replace("#profile_survey_id",$param,$template['redirectionURL']);
        } elseif($template_name == "CONSUMER_BADGE") {
        	$template = $this->WOM_CONSUMER_BADGE_TEMPLATE;
        }
    	$row = $this->createRow();
        $row->consumer_id = $consumer_id;
        $row->date = date("Y-m-d H:i:s");
        $row->type = $template['type'];
        if($replaced_text) {
        	$row->text = $replaced_text;
        } else {
        	$row->text = $template['text'];
        }
        $row->status = "NEW";
        if($replaced_url) {
        	$row->redirection_url = $replaced_url;
        } else {
        	$row->redirection_url = $template['redirectionURL'];
        }
        
        return $row->save();
    }
}