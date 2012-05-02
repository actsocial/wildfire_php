<?php
class Issue extends Zend_Db_Table
{
	protected $_name = "issue";
	
	public function getConsumerAlerts($cid){
		return $this->fetchAll("(type = 'public' and (max_count>accept_count or max_count=0) and id not in (select issue from alert ca where ca.consumer ='".$cid."' and ca.status = 'finish')) or id in (select issue from alert ca where ca.consumer ='".$cid."' and ca.status = 'new')");
	}
	
	public function updateIssueParticipant($iid){
		$data = array('accept_count'=>new Zend_Db_Expr('accept_count + 1'));
		return $this->update($data,'accept_count<max_count');
	}
}