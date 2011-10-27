<?php
/*
CREATE TABLE `dashboard_mapping` (
  `id` int(11) NOT NULL auto_increment,
  `survey_id` int,
  `context_index` int,
  `question_index` int,
  `mark` varchar(64),
  PRIMARY KEY  (`id`)
);
 */
class DashboardMapping extends Zend_Db_Table
{
	protected $_name = "dashboard_mapping";
	
	public function findBy(array $params) {
		$select = $this->select();
		foreach ($params as $key => $value) {
			$select->where($key . ' = ? ', $value);
		}
		return $this->fetchAll($select);
	}
}
