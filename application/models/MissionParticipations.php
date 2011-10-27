<?php
/*
CREATE TABLE `mission_participations` (
    `id` int(11) NOT NULL auto_increment,
    `mission_id` int(11) NOT NULL,
    `consumer_id` int(11) NOT NULL,
    `state` varchar(45) NOT NULL,
    `create_date` datetime NOT NULL,
    PRIMARY KEY  (`id`)
);
 */
class MissionParticipations extends Zend_Db_Table
{
    protected $_name = "mission_participations";
}
