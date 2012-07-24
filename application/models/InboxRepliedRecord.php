<?php 
class InboxRepliedRecord extends Zend_Db_Table {
	
	protected $_name = "inbox_reply_record";
	
	function findRepliedTopicByUserAndTopicIds($user,$topics){
		$select = $this->select();
		$select->where( 'consumer = ? ', $user);
		$select->where( 'topic IN (?) ', $topics);
		$repliedTopic =  $this->fetchAll($select);
		$repliedTopicIds = array();
		foreach($repliedTopic as $topic):
		array_push($repliedTopicIds,$topic['topic']);
		endforeach;
		return $repliedTopicIds;
	}
	
	function findRepliedTopicByUser($user){
		$select = $this->select();
		$select->where( 'consumer = ? ', $user);
		$select->order('timestamp DESC');
		$repliedTopic =  $this->fetchAll($select);
		return $repliedTopic;
	}
	
	function findRepliedTopicOrderByUser(){
		$select = $this->select();
		$select->from('inbox_reply_record');
		$select->order('consumer','timestamp DESC');
		$select->setIntegrityCheck(false);
		$select->joinLeft('consumer', 'inbox_reply_record.consumer = consumer.id', array (
								'consumer.name as consumer_name','email'
		));
		$select->joinLeft('reward_point_transaction_record', 'inbox_reply_record.reward_point_transaction_record_id = reward_point_transaction_record.id', array (
										'point_amount'
		));
		$repliedTopic =  $this->fetchAll($select);
		return $repliedTopic;
	}
	
	function updatePointTransaction($irrId,$rptrId){
		$select = $this->select();
		$select->where( 'id = ? ', $irrId);
		$row = $this->fetchRow($select);
		$row->reward_point_transaction_record_id = $rptrId;
		return $row->save();
	}
	
}