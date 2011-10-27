<?php
/*
CREATE TABLE `mission_report` (
    `id` int(11) NOT NULL auto_increment,
    `state` varchar(64),
    `accesscode` varchar(64),
    `mission_participation_id` int(11) NOT NULL,
    `consumer_id` int(11) NOT NULL,
    `mission_id` int(11) NOT NULL,
    `reward_point_transaction_record_id` int(11),
    `create_date` datetime NOT NULL,
    `text` varchar(1024),
    PRIMARY KEY  (`id`)
);
 */
class MissionReport extends Zend_Db_Table
{
    protected $_name = "mission_proofs";
}
