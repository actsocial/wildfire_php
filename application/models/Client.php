<?php
/*
 * CREATE TABLE `client` (                                             
          `email` varchar(255) character set utf8 NOT NULL default '',     
          `password` varchar(255) character set utf8 NOT NULL default '',  
          `name` varchar(255) character set utf8 NOT NULL default '',      
          `id` int(11) unsigned NOT NULL auto_increment,                   
          `language_pref` varchar(32) character set utf8 default NULL,     
          PRIMARY KEY  (`id`)                                              
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8  
 */
class Client extends Zend_Db_Table
{
	protected $_name = "client";
	
}
