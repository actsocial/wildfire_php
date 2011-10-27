<?php
/*
CREATE TABLE `brands` (
    `id` int(11) NOT NULL auto_increment,
    `name` varchar(128) NOT NULL,
    `company` varchar(128),
    `description` text,
    `logo` BLOB,
    `logo_type` varchar(64),
    `create_date` varchar(64),
    PRIMARY KEY  (`id`)
);
 */
class Brands extends Zend_Db_Table
{
    protected $_name = "brands";
    protected $_rowClass = 'Brand';
    protected $_dependentTables = array('Missions');

    function createRecord($name, $campany, $desc, $logo, $logoType)
    {
        $row = $this->createRow();
        $row->name = $name;
        $row->company = $campany;
        $row->description = $desc;
        $row->logo = $logo;
        $row->logo_type = $logoType;
        $row->create_date = date("Y-m-d H:i:s");
        return $row->save();
    }
}
