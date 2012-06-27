<?php 
class InboxReadRecord extends Zend_Db_Table {
	
	protected $_name = "inbox_read_record";
	
	
	function findReadTopicByUserAndTopicIds($user,$topics){
		$select = $this->select();
		$select->where( 'consumer = ? ', $user);
		$ids = "";
		$select->where( 'topic IN (?) ', $topics);
		$readTopic =  $this->fetchAll($select);
		$readTopicIds = array();
		foreach($readTopic as $topic):
			array_push($readTopicIds,$topic['topic']);
		endforeach;
		return $readTopicIds;
	}
}