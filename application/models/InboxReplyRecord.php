<?php 
class InboxReplyRecord extends Zend_Db_Table {
	
	protected $_name = "inbox_reply_record";
	
	function findReplyCount($params){
		$select = $this->select();
		foreach ($params as $key => $value) {
			$select->where($key . ' = ? ', $value);
		}
		return count($this->fetchAll($select));
	}
	
	function findReplyRecord(){
		$select = $this->select();
		$select->from('inbox_reply_record', array (
					'topic',
					'platform_type',
					'sns_reply_id',
					'timestamp'
		));
		$select->setIntegrityCheck(false);
		$select->joinLeft('consumer', 'inbox_reply_record.consumer = consumer.id', array (
						'consumer.name as consumer_name',
						'weiboid'
		));
		$select->joinLeft('reward_point_transaction_record', 'inbox_reply_record.reward_point_transaction_record_id = reward_point_transaction_record.id', array (
								'point_amount'
		));
		return $this->fetchAll($select);
	}
}
