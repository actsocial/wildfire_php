<?php


/*
 CREATE TABLE `report_tabs` (
  `id` int(11) NOT NULL auto_increment,
  `campaign_id` int,
  `name` varchar(255),
  `description` text,
  PRIMARY KEY  (`id`)
);
 */
class ReportTab extends Zend_Db_Table {
	protected $_name = "report_tabs";

	public function findBy($params) {
		$select = $this->select();
		$select->setIntegrityCheck(false);
		$select->from('report_tabs', array (
			'id',
			'campaign_id',
			'name'
		));
		if (in_array('campaign', $params)) {
			$select->join('campaign', 'report_tabs.campaign_id = campaign.id', array (
				'id as campaign_id',
				'name as campaign_name'
			));
			unset($params['campaign']); //remove 'campaign' if exist for later construct where sentence
		}
	    foreach ($params as $key => $value) {
            $select->where($key . ' = ? ', $value);
        }
		return $this->fetchAll($select);
	}

	public function save($data) {
		unset ($data['id']);
		return $this->insert($data);

	}
	
}