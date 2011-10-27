<?php
class CampaignInvitation extends Zend_Db_Table
{
	protected $_name = "campaign_invitation";
	protected $_referenceMap    = array(
		'Campaign' => array(
			'columns'           => array('campaign_id'),
			'refTableClass'     => 'Campaign',
			'refColumns'        => 'id',
		),
		'Consumer' => array(
			'columns'           => array('consumer_id'),
			'refTableClass'     => 'Consumer',
			'refColumns'        => 'id',
		),
	);
	
}
