<?php
/*
CREATE TABLE `client_message` (
  `id` int(11) NOT NULL auto_increment,
  `type` varchar(64),
  `subject` varchar(64) character set utf8,
  `content` text character set utf8,
  `from` int,
  `from_type` varchar(64),
  `to` int,
  `to_type` varchar(64),
  `create_user` int,
  `create_date` datetime,
  `state` varchar(64),
  `star` varchar(64),
  `parent_id` int,
  `deleted` tinyint,
  `remark` varchar(128),
  PRIMARY KEY  (`id`)
);

 */
class ClientMessage extends Zend_Db_Table
{
	protected $_name = "client_message";

	function find_by_condition($where, $order) {
        return $this->fetchAll($where, $order, null, null);
    }
    
    function find_all_message($order) {
        return $this->fetchAll("parent_id is null", $order, null, null);
    }
    
    function find_all_reply($order) {
        return $this->fetchAll("parent_id is not null", $order, null, null);
    }
    
    function createRecord($from_type, $from, $to_type, $to,
            $subject, $type, $content, $current_user, $date, $remark = null) {
        $row = $this->createRow();
        $row->from_type = $from_type;
        $row->from = $from;
        $row->to_type = $to_type;
        $row->to = $to;
        $row->subject = $subject;
        $row->type = $type;
        $row->state = "NEW";
        $row->content = $content;
        $row->remark = $remark;
        $row->create_user = $current_user;
        $row->create_date = $date;
        return $row->save();
    }
    
    function saveReply($from_type, $from, $to_type, $to,
            $subject, $content, $current_user, $date, $parent) {
        $row = $this->createRow();
        $row->from_type = $from_type;
        $row->from = $from;
        $row->to_type = $to_type;
        $row->to = $to;
        $row->subject = $subject;
        $row->content = $content;
        $row->create_user = $current_user;
        $row->create_date = $date;
        $row->parent_id = $parent;
        $row->state = "NEW";
        return $row->save();
    }
    
    function updateState($id) {
        $row = $this->fetchRow('id = '.$id);
        if ($row->state == "NEW") {
            $row->state = "VIEWED";
        } else {
            $row->state = "NEW";
        }
        $row->save();
        return $row->state;
    }
    
    function updateStar($id) {
        $row = $this->fetchRow('id = '.$id);
        if ($row->star == null) {
        	$row->star = '1';
        } else {
        	$row->star = null;
        }
        $row->save();
        return $row->star;
    }
}
