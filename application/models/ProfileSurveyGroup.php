<?php
/* 
CREATE TABLE `profile_survey_group` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255),
  `profile_survey_id` int NOT NULL,
  `consumer_id` int NOT NULL,
--  `campaign_id` int,
  `create_date` datetime,
  PRIMARY KEY  (`id`)
);

alter table profile_survey add column is_special boolean default false;
 */

class ProfileSurveyGroup extends Zend_Db_Table
{
	protected $_name = "profile_survey_group";
 	
    function find_by_condition($order) {
        return $this->fetchAll(null, $order, null, null);
    }
	
    function createRecord($campaign_id, $profile_survey_id, $consumer_id, $date) {
        $row = $this->createRow();
        $row->campaign_id = $campaign_id;
        $row->profile_survey_id = $profile_survey_id;
        $row->consumer_id = $consumer_id;
        $row->create_date = $date;
        return $row->save();
    }
}
