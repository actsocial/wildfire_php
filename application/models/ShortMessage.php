<?php
/*
CREATE TABLE `short_message` (
  `id` int(11) NOT NULL auto_increment,
  `consumer_id` int(11) NOT NULL,
  `phone` varchar(25),
  `state` varchar(25),
  `send_date` datetime,
  `plan_id` int NOT NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`consumer_id`) REFERENCES `consumer` (`id`),
  FOREIGN KEY (`plan_id`) REFERENCES `communicate_plan` (`id`)
);
 */
class ShortMessage extends Zend_Db_Table
{
	protected $_name = "short_message";
	
    function deleteByPlan($plan_id) {
        $where = "plan_id=".$plan_id;
        return $this->delete($where);
    }
}
