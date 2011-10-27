<?php
/* 
CREATE TABLE `image_report` (
  `id` int(11) NOT NULL auto_increment,
  `file_name` varchar(255),
  `type` varchar(255),
  `state` varchar(64),
  `image` LONGBLOB,
  `reward_point_transaction_record_id` int,
  `thumb_width` int,
  `thumb_height` int,
  `campaign_id` int NOT NULL,
  `consumer_id` int NOT NULL,
  `create_date` datetime,
  PRIMARY KEY  (`id`)
);

 */

class ImageReport extends Zend_Db_Table
{
	protected $_name = "image_report";
	
}
