<?php
class SnsUser extends Zend_Db_Table
{
	protected $_name = "sns_user";
	
	function loadByConsumerAndPlatform($consumer,$platform){
		$select = $this->select();
		$select->where( 'consumer = ? ', $consumer);
		$select->where( 'platform_type = ? ', $platform);
		return $this->fetchRow($select);
	}
}