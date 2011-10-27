<?php
class Report extends Zend_Db_Table
{
	protected $_name = "report";
	
    function getConsumerIdByReportId($reportId) {
        $report = $this->fetchRow("id=".$reportId);
        return $report->consumer_id;
    }
}
