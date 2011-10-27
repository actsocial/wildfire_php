<?php
class TelephoneLog extends Zend_Db_Table
{
	protected $_name = "telephone_log";
	
    function deleteByPlan($plan_id) {
    	$where = "plan_id=".$plan_id;
        return $this->delete($where);
    }
}
