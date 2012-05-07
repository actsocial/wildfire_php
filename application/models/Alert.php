<?php
class Alert extends Zend_Db_Table
{
	protected $_name = "alert";
	
	function createAlert($uid,$iid){
		$row = $this->fetchRow("consumer =".$uid." and issue = ".$iid);
		if(!$row){
			$row = $this->createRow();
			$row->consumer = $uid;
			$row->issue = $iid;
		}
		$row->status = 'start';
		$row->save();
	}
	
	public function finishAlert($userId,$topic){
		$set = array('status'=>'finish');
		$where = 'consumer = '.$userId.' and exists (select id from issue where topic = "'.urldecode($topic).'" and alert.issue = issue.id)';
		$this->update($set,$where);
	}
}