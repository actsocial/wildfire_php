<?php

/*
 * CREATE TABLE `campaign_pre_invitation` (
    `id` int(11) NOT NULL auto_increment,
    `email` varchar(255),
    `phone` varchar(255),
    `description` text,
    `state` varchar(255) default 'PENDING',
    `code`  varchar(255),
    `content` text,
    `date_used` datetime,
    `create_date` datetime,
    `area` varchar(25),
      PRIMARY KEY  (`id`)
)
 */
class CampaignPreInvitation extends Zend_Db_Table {
	protected $_name = "campaign_pre_invitation";

	function send() {
	}
}