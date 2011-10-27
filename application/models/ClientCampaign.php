<?php
/*
CREATE TABLE `client_campaign` (                    
                      `client_id` int(10) unsigned default NULL,       
                      `campaign_id` int(10) unsigned default NULL   
                    )
*/
class ClientCampaign extends Zend_Db_Table
{
	protected $_name = "client_campaign";
}
