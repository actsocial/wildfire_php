<?php
/*
 * CREATE TABLE `url_report_reply` (                 
                    `id` int(10) unsigned NOT NULL auto_increment,  
                    `date` datetime default NULL,                   
                    `subject` varchar(250) default NULL,            
                    `content` text,                                 
                    `from` varchar(250) default NULL,               
                    `to` varchar(250) default NULL,                 
                    `url_report_id` int(10) unsigned default NULL,  
                    `admin_id` int(10) unsigned default NULL,       
                    PRIMARY KEY  (`id`)                             
                  ) 
 */
class UrlReportReply extends Zend_Db_Table
{
	protected $_name = "url_report_reply";
	
}
