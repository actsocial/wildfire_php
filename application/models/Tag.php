<?php
/*
CREATE TABLE `tags` (
  `id` int(11) NOT NULL auto_increment,
  `location` varchar(64),
  `name` varchar(255),
  `type` varchar(64),
  `sort` int,
  PRIMARY KEY  (`id`)
);
 */
class Tag extends Zend_Db_Table
{
	protected $_name = "tags";
	
}
