<?php
/*
CREATE TABLE `spark_sms` (
  `id` int(11) NOT NULL auto_increment,
  `text` text,
  `msg` varchar(45) default NULL,
  `sys_id` varchar(45) default NULL,
  `source` varchar(45) default NULL,
  `time` datetime default NULL,
  `err` varchar(45) default NULL,
  `consumer_id` int(11) default NULL,
  PRIMARY KEY  (`id`)
);

ALTER TABLE `wildfire`.`spark_sms` ADD COLUMN `state` VARCHAR(16)  DEFAULT NULL AFTER `consumer_id`;
*/

class SparkSms extends Zend_Db_Table
{
	protected $_name = "spark_sms";
	
    function findAllSMS() {
        $db = Zend_Registry::get('db');
        $rs = $db->fetchAll("SELECT spark_sms.id as sms_id, spark_sms.sys_id, spark_sms.source, spark_sms.text, spark_sms.err, spark_sms.time, spark_sms.state,".
                            "consumer.id as con_id, consumer.name,consumer.id, consumer.email, consumer.recipients_name ".
                            "FROM spark_sms left join consumer on consumer.id = spark_sms.consumer_id ".
                            "ORDER BY spark_sms.id DESC");
        return $rs;
    }
	
}
