<?php

include "Consumer.php";
include "CampaignInvitation.php";
class Rank extends Zend_Db_Table{
	
	protected $_name = "rank";
	
	
	
	function changeConsumerRank($uid){
		//consumer information
		if(!isset($this->consumer)){
			$this->consumer = new Consumer();
		}
		$this->consumerData = $this->consumer->fetchRow('id = '.$uid);
		//total points 
	    $this->rewardPointTransactionRecord = new RewardPointTransactionRecord();
	    
	    $rewardData = $this->rewardPointTransactionRecord->fetchAll('consumer_id='.$uid.' and transaction_id !=4');
	    $rewardDataArray = $rewardData->toArray();
	    $total = 0;
	    if(count($rewardDataArray)){
		    foreach ($rewardDataArray as $val){
		    	$total += $val['point_amount'];
		    }
	    }	
        //participated campaigns
	    $campaign = new CampaignInvitation();
	    $campaignData = $campaign->fetchAll('state = "ACCEPTED" and consumer_id ='.$uid);
	    $campaginNum = count($campaignData);
	    //rank information
		$rank = $this->fetchRow('campaign_number<='.$campaginNum .' and point_total<='.$total,'point_total desc');
		
		if(count($rank)){
			if($this->consumerData->rank == $rank->id){
				return;
			}else{
				$this->consumerData->rank = $rank->id;
				$this->consumerData->save();
				// add notification
				$notificationModel = new Notification();
				$notificationModel->createRecord("RANK_UPGRADE",$uid);
				$row  = $this->rewardPointTransactionRecord->createRow();
				$row->consumer_id = $uid;
				$row->date = date("Y-m-d H:i:s");
				$row->transaction_id = 7;
				$row->point_amount = $rank->point_bonus;
				$row->save();
				$this->changeConsumerRank($uid);
			}
		}else{
			return ;
		}		
	}
	
	function changeAllConsumersRank(){
		$this->consumer = new Consumer();
		$consumerData = $this->consumer->fetchAll();
		foreach($consumerData as $consumer){
			$this->changeConsumerRank($consumer->id);
		}
		
	}
	
}
