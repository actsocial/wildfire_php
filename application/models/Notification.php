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
	
	function createRecord($template_name,$consumer_id,$param = null)
    {
        if($template_name == null || $template_name == "") {
        	return null;
        } elseif($templet_name == "REPORT_REPLY") {
        	$template = $WOM_REPORT_REPLY_TEMPLET;
        	$replaced_text = str_replace("#point",$param,$template['text']);
        } elseif($templet_name == "REDEEM_POINT") {
        	$template = $WOM_REDEEM_POINT_TEMPLET;
        	$replaced_text = str_replace("#point",$param,$template['text']);
        } elseif($templet_name == "RANK_UPGRADE") {
        	$template = $WOM_RANK_UPGRADE_TEMPLET;
        } elseif($templet_name == "CAMPAIGN_INVITATION") {
        	$template = $WOM_CAMPAIGN_INVITATION_TEMPLET;
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
        $row->redirection_url = $template['redirectionURL'];
        
        return $row->save();
    }
}