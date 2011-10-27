<?php
/*
 * CREATE TABLE `url_report` (                                   
              `id` int(11) NOT NULL auto_increment,                       
              `url` varchar(255) NOT NULL,                                
              `campaign_id` int(11) NOT NULL,                             
              `consumer_id` int(11) NOT NULL,                             
              `reward_point_transaction_record_id` int(11) default NULL,  
              `state` varchar(45) NOT NULL,                               
              `create_date` datetime NOT NULL,                            
              PRIMARY KEY  (`id`)                                         
            )
 */
class UrlReport extends Zend_Db_Table
{
	protected $_name = "url_report";
	
}
