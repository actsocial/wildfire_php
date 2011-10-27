<?php
/*
CREATE TABLE `missions` (
    `id` int(11) NOT NULL auto_increment,
    `title` varchar(128) NOT NULL,
    `intro` text,
    `type` varchar(64) NOT NULL,  -- mission type determine logo
    `state` varchar(64) NOT NULL,
    `start_date` datetime NOT NULL,
    `end_date` datetime NOT NULL,
    `background` BLOB,
    `bg_type` varchar(64),
    `create_date` datetime NOT NULL,
    `brand_id` int(11) NOT NULL,
    PRIMARY KEY  (`id`)
);
 */
class Missions extends Zend_Db_Table
{
    protected $_name = "missions";

    protected $_referenceMap = array(
        'BelongTo' => array(
            'columns'        => 'brand_id',
            'refTableClass'  => 'Brands',
            'refColumns'     => 'id'
        )
    );

    function createRecord($title, $intro, $type, $startDate, $endDate,
            $bg, $bgType, $brandId)
    {
        $row = $this->createRow();
        $row->title = $title;
        $row->intro = $intro;
        $row->type = $type;
        $row->state = "NEW";
        $row->start_date = $startDate;
        $row->end_date = $endDate;
        $row->background = $bg;
        $row->bg_type = $bgType;
        $row->create_date = date("Y-m-d H:i:s");
        $row->brand_id = $brandId;
        return $row->save();
    }
}
