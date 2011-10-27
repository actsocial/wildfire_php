<?php
class PointService {    
	/**
     * decrypt 
     * @param string $site
     * @param string $userName
     * @param string $password
     * @param string $realname
     * @param string $auth
     * @return string $data
     */
//    public function decrypt($site, $userName, $password, $realname, $auth){
//    	if($auth == 'xingxinghuodecrypt'){
//    		//decrypt
//	    	$config = Zend_Registry::get('config');
//	    	$key = $config->mcrypt->tripledes->key; 
//	    	try{
//	    		$td = mcrypt_module_open('tripledes', '', 'ecb', ''); 
//				$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND); 
//				mcrypt_generic_init($td, $key, $iv); 
//		        $site = mdecrypt_generic($td, base64_decode(str_replace(" ","+",$site))); 
//				$userName = mdecrypt_generic($td, base64_decode(str_replace(" ","+",$userName)));
//				$password = mdecrypt_generic($td, base64_decode(str_replace(" ","+",$password))); 
//				$realname = mdecrypt_generic($td, base64_decode(str_replace(" ","+",$realname)));        
//		        mcrypt_generic_deinit($td); 
//		        mcrypt_module_close($td);
//	    	}catch(Exception $e){
//	    		return -1;
//	    	}
//			if($site == null || $site == ''|| $userName == null || $userName == ''
//			|| $password == null || $password == ''|| $realname == null || $realname == ''){
//				return -1;
//			}
//			$returnString = trim($site)."|".trim($userName)."|".trim($password)."|".trim($realname);
//			mb_convert_encoding($returnString, "UTF-8");
//	        return $returnString; 
//    	}else{
//    		return -1;
//    	}
//    	
//    }
    /**
     * verify account by user name and password
     * @param string $userName
     * @param string $password
     * @return integer current point, return -1 if the account is not verified
     */
//    public function verifyAccount($userName, $password){
//    	$config = Zend_Registry::get('config');
//    	$rate = (int)$config->exchange->rate;
//		
//        //verify
//    	$consumerMode = new Consumer();
//    	$consumer = $consumerMode->fetchRow("email = '".trim($userName)."' and password = '".trim($password)."' and (consumer.pest != 1 or consumer.pest is null)");	
//    	if($consumer != null){
//    		$db = Zend_Registry::get('db');
//    		$selectPoint = $db->select();
//			$selectPoint->from('reward_point_transaction_record', 'SUM(point_amount)')
//			->where("consumer_id = ?", $consumer->id);
//			$amountPoints = $db->fetchOne($selectPoint);
//			$returnString = round($amountPoints*$rate).'|'.$userName;
//    		mb_convert_encoding($returnString, "UTF-8");
//    		//record info in exchange_record when the member make a Jifentong account
//    		$exchangeRecordModel = new ExchangeRecord();
//			$exchangeRecord = array("consumer_id" => $consumer->id,
//									"create_date" => date("Y-m-d H:i:s"),
//									"exchange_point_amount" => 0,
//									"point_amount" => 0,
//									"exchange_rate" => 0,
//									"identify_code" => '0'); 
//			$exchangeRecord_id = $exchangeRecordModel->insert($exchangeRecord);
//	        return $returnString;
//    	}else{
//    		return -1;
//    	}
//    }
    
//    /**
//     * 
//     * @param string $userName
//     * @param string $password
//     * @param integer $point
//     * @return string deposit, return -1 if transcation is failed
//     */
//    public function exchange($userName,$password,$point){
//
//    	$config = Zend_Registry::get('config');
//    	$rate = (int)$config->exchange->rate;
//    	
//    	$consumerMode = new Consumer();
//    	$consumer = $consumerMode->fetchRow("email = '".$userName."' and password = '".$password."' and (consumer.pest != 1 or consumer.pest is null)");
//    	
//    	if($consumer != null){
//    		$db = Zend_Registry::get('db');
//	    	//check exchange condition first
//			$selectTotalCompletedCampaign = $db->select();
//			$selectTotalCompletedCampaign->from('campaign_participation', 'count(*)')
//			->join('campaign_invitation', 'campaign_participation.campaign_invitation_id = campaign_invitation.id', null)
//			->where('campaign_participation.state = "COMPLETED"')
//			->where('campaign_invitation.consumer_id = ?', $consumer->id);
//			$this->view->completedCampaignAmount = $db->fetchOne($selectTotalCompletedCampaign);
//
//			$selectTotalSubmittedReport = $db->select();
//			$selectTotalSubmittedReport->from('report', 'count(*)')
//			->where('state = "APPROVED"')
//			->where('consumer_id = ?', $consumer->id);
//			$this->view->submittedReportAmount = $db->fetchOne($selectTotalSubmittedReport);		
//			if($this->view->completedCampaignAmount < 1 || $this->view->submittedReportAmount < 3 || 
//			($consumer->pest != null && $consumer->pest == 1)){
//				return -1;
//			}else{
//				//if sparks satisfy the condition, continue...
//	    		$selectPoint = $db->select();
//	    		$selectPoint->from('reward_point_transaction_record', 'SUM(point_amount)')
//				->where("consumer_id = ?", $consumer->id);
//				$amountPoints = $db->fetchOne($selectPoint);
//				
//				$exchangePoint = round($point/$rate,4);
//				if((int)$point <= 0 || $exchangePoint > $amountPoints){
//					return -1;
//				}
//				else{
//					$currentTime = date("Y-m-d H:i:s");	
//					//save exchange into table 'reward_point_transaction_record'
//					$rewardPointTransactionRecordModel = new RewardPointTransactionRecord();	
//					$rewardPointTransactionRecord = array("consumer_id" => $consumer->id,
//													"DATE" => $currentTime,
//													"transaction_id" => '4',
//													"point_amount" => -$exchangePoint);  
//					$rewardPointTransactionRecord_id = $rewardPointTransactionRecordModel->insert($rewardPointTransactionRecord);
//					//save exchange into table 'exchange_record'
//					$codePattern = '1234567890ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
//					$identify_code = '';
//					for($codeCount=0; $codeCount<12; $codeCount++){
//						$identify_code = $identify_code.$codePattern{mt_rand(0,35)};
//					}
//					$exchangeRecordModel = new ExchangeRecord();
//					$exchangeRecord = array("consumer_id" => $consumer->id,
//											"create_date" => $currentTime,
//											"exchange_point_amount" => $point,
//											"point_amount" => $exchangePoint,
//											"exchange_rate" => $rate,
//											"identify_code" => $identify_code); 
//					$exchangeRecord_id = $exchangeRecordModel->insert($exchangeRecord);
//					
//					//return residual_points
//					$returnString = round(($amountPoints-$exchangePoint)*$rate).'|'.$identify_code;
//					mb_convert_encoding($returnString, "UTF-8");
//		       		return $returnString;
//				}
//			}
//			
//    	}else{
//    		return -1;
//    	}
//
//    }
//    
    /**
     * Get account information without access control
     * @param string $userName
     * @param string $password
     * @return integer $point 
     */
//    public function getCurrentPoint($userName, $password){
//    	$config = Zend_Registry::get('config');
//    	$rate = (int)$config->exchange->rate;
//    	
//    	$consumerMode = new Consumer();
//    	
//    	$consumer = $consumerMode->fetchRow("email = '".$userName."' and password = '".$password."' and (consumer.pest != 1 or consumer.pest is null)");
//    	if($consumer == null){
//    		return -1;
//    	}
//    	$db = Zend_Registry::get('db');
//    	$selectTotalCompletedCampaign = $db->select();
//    	$selectTotalCompletedCampaign->from('campaign_participation', 'count(*)')
//		->join('campaign_invitation', 'campaign_participation.campaign_invitation_id = campaign_invitation.id', null)
//		->where('campaign_participation.state = "COMPLETED"')
//		->where('campaign_invitation.consumer_id = ?', $consumer->id);
//		$this->view->completedCampaignAmount = $db->fetchOne($selectTotalCompletedCampaign);
//		
//    	$selectTotalSubmittedReport = $db->select();
//		$selectTotalSubmittedReport->from('report', 'count(*)')
//		->where('state = "APPROVED"')
//		->where('consumer_id = ?', $consumer->id);
//		$this->view->submittedReportAmount = $db->fetchOne($selectTotalSubmittedReport);		
//		if($this->view->completedCampaignAmount < 1 || $this->view->submittedReportAmount < 3 || 
//		($consumer->pest != null && $consumer->pest == 1)){
//			return -1;
//		}else{
//			if($consumer != null){
//	    		$selectPoint = $db->select();
//				$selectPoint->from('reward_point_transaction_record', 'SUM(point_amount)')
//				->where("consumer_id = ?", $consumer->id);
//				$amountPoints = $db->fetchOne($selectPoint);
//	    		return round($amountPoints*$rate);
//	    	}else{
//	    		return -1;
//	    	}
//		}	
//    }
	
}