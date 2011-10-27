<?php

/* 
CREATE TABLE `user_emails` (
  `id` int(11) NOT NULL auto_increment,
  `accessCode` varchar(255),
  `email` varchar(255),
  `create_date` datetime,
  PRIMARY KEY  (`id`)
);
 */
 
 
class UserEmail extends Zend_Db_Table
{
	protected $_name = "user_emails";
	
}
