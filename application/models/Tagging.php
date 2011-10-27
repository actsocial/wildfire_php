<?php


/*
CREATE TABLE `taggings` (
  `id` int(11) NOT NULL auto_increment,
  `tag_id` int,
  `report_tab_id` int,
  PRIMARY KEY  (`id`)
);
 */
class Tagging extends Zend_Db_Table {
	protected $_name = "taggings";
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

//	public function findBy($report_tab_id) {
//		return $this->fetchAll($this->select()->where('report_tab_id = ? ', $report_tab_id));
//	}

	public function findBy(array $params) {
		$select = $this->select();
		foreach ($params as $key => $value) {
			$select->where($key . ' = ? ', $value);
		}
		return $this->fetchAll($select);
	}
	
	public function getTagIds(array $params) {
	    $taggings = $this->findBy($params);
        $taggingIds = array();
        foreach ($taggings as $tagging) {
        	array_push($taggingIds, $tagging->tag_id);
        }
        return $taggingIds;
	}
}