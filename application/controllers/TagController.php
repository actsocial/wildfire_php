<?php 
include_once('saetv2.ex.class.php');
include_once('weibo.uri.utility.php');
require_once 'couch.php';
require_once 'couchClient.php';
require_once 'couchDocument.php';
class TagController extends MyController {
	
	function indexAction(){
		$this->view->issues = array();
		$this->view->topicsUntaggedSumByFolder = array();
		$config = Zend_Registry::get('config');
		$topicsClient = new couchClient ($config->couchdb->uri.":".$config->couchdb->port,$config->couchdb->topics);
		try {
			$view = $topicsClient->group(TRUE)->asArray()->stale("ok")->getView('bayers','topics-untagged-sum-by-folder');
			$this->view->topicsUntaggedSumByFolder = $view['rows'];
			$issueModel = new Issue();
			$issues = $issueModel->getConsumerAlerts($this->_currentUser['id']);
			if(sizeof($issues)>0){
				$this->view->issues = $issues;
			}
		} catch (Exception $e) {
		}
	}
	
	function ajaxissuedetailAction(){
		$this->_helper->layout->disableLayout();
		$idString = $this->_request->getParam('key');
		if($idString){
			$config = Zend_Registry::get('config');
			$topicsClient = new couchClient ($config->couchdb->uri.":".$config->couchdb->port,$config->couchdb->topics);
			$doc= $topicsClient->asArray()->getDoc(urldecode($idString));
			if(!isset($doc['error'])){
				$this->view->issue = $doc;
			}
		}
	}
	
	function ajaxgetissueAction(){
		$this->_helper->layout->disableLayout();
		$issue = $this->_request->getParam('issue');
		if($issue){
			$issueModel = new Issue();
			$uid = $this->_currentUser->id;
			$r = $issueModel->updateIssueParticipant($issue,$uid);
			if($r){
				$alertModel = new Alert();
				$alertModel->createAlert($uid,$issue);
				echo "ok";
			}else{
				echo "exceed";
			}
		}
	}
	
	function ajaxdetailAction(){
		$this->_helper->layout->disableLayout();
		define("PAGESIZE",10);
		$config = Zend_Registry::get('config');
		$topicsClient = new couchClient ($config->couchdb->uri.":".$config->couchdb->port,$config->couchdb->topics);
		try {
			$key = $this->_request->getParam('key');
			$page = $this->_request->getParam('page')|0;
			$totalCount = $this->_request->getParam('totalCount')|0;
			$this->view->page = $page;
			if($key){
				$startKey = array($key,array(0,0,0,0,0,0));
				$endKey = array($key,array('{}','{}','{}','{}','{}','{}'));
				$view = $topicsClient->skip($page*PAGESIZE)->limit(PAGESIZE)->reduce(FALSE)->startkey($startKey)->endkey($endKey)->stale("ok")->asArray()->getView('bayers','topics-untagged-by-folder');
				$this->view->topics = $view['rows'];
				$this->view->key = $key;
				if($totalCount>0){
					$this->view->totalPage = ceil($totalCount/PAGESIZE);
				}else{
					$this->view->totalPage = "-";
				}
				$this->view->totalCount = $totalCount;
				$this->view->currentPage = $page+1;
			}
		} catch (Exception $e) {
				
		}
	}
	
	function ajaxgetpostsAction(){
		$topicId = $this->_request->getParam('topic');
		if($topicId){
			$topicId = urldecode($topicId);
			$startKey = array($topicId,0,0);
			$endKey = array($topicId,'{}','{}');
			$config = Zend_Registry::get('config');
			$postsClient = new couchClient ($config->couchdb->uri.":".$config->couchdb->port,$config->couchdb->posts_users);
			$posts = $postsClient->reduce(FALSE)->startkey($startKey)->endkey($endKey)->stale("ok")->asArray()->getView('socialmediathread','posts-by-topic');
			$this->view->posts = $posts['rows'];
			$this->_helper->layout->disableLayout();
		}
	}
	
	function ajaxsaveweiboalertreplyAction(){
		$this->_helper->layout->disableLayout();
		$topicId = urldecode($this->_request->getParam('topicId'));
		$annotations = array('wildfire_community',$topicId);
		$tokenNamespace = new Zend_Session_Namespace('token');
		$token =$tokenNamespace->token;
		if($token){
			include_once( 'weiboconfig.php' );
			$c = new SaeTClientV2( WB_AKEY , WB_SKEY , $token['access_token'] );
			$content = $this->_request->getParam('content');
			$result = $c->update($content,NULL,NULL,$annotations);
			print_r($result);die;
			if(isset($result['error_code'])){
				print_r($result);
			}else{
				$mid = $result['idstr'];
				$userId = $this->_currentUser->id;
				$irrModel = new InboxReplyRecord();
				$row = $irrModel->createRow();
				$row->topic = $topicId;
				$row->consumer = $userId;
				$row->sns_type = "weibo";
				$row->sns_reply_id = $result['idstr'];
				$row->save();
				$rewardPointTransactionRecordModel = new RewardPointTransactionRecord();
				$rewardPointTransaction = array(
									"consumer_id" => $userId,
									"date" => date("Y-m-d H:i:s"),
									"transaction_id" => "11",
									"point_amount" => "10"
				);
				$id = $rewardPointTransactionRecordModel->insert($rewardPointTransaction);
				$row->reward_point_transaction_record_id = $id;
				$row->save();
				$rankModel = new Rank();
				$rankModel->changeConsumerRank($userId);
				$alertModel = new Alert();
				$alertModel->finishAlert($userId,$topicId);
				echo "ok";
			}
		}
	}
	
	function ajaxsaveweiboreplyAction(){
		$this->_helper->layout->disableLayout();
		$topicId = urldecode($this->_request->getParam('topicId'));
		$annotations = array('wildfire_community',$topicId);
		$tokenNamespace = new Zend_Session_Namespace('token');
		$token =$tokenNamespace->token;
		if($token){
			include_once( 'weiboconfig.php' );
			$c = new SaeTClientV2( WB_AKEY , WB_SKEY , $token['access_token'] );
			$content = $this->_request->getParam('content');
			$result = $c->update($content,NULL,NULL,$annotations);
			if(isset($result['error_code'])){
				print_r($result);
			}else{
				$mid = $result['idstr'];
				$userId = $this->_currentUser->id;
				$irrModel = new InboxReplyRecord();
				$row = $irrModel->createRow();
				$row->topic = $topicId;
				$row->consumer = $userId;
				$row->sns_type = "weibo";
				$row->sns_reply_id = $result['idstr'];
				$row->save();
				$replyCount = $irrModel->findReplyCount(array('consumer' => $userId,'sns_type'=>'weibo','topic'=>$topicId));
				if($replyCount<2){
					$rewardPointTransactionRecordModel = new RewardPointTransactionRecord();
					$rewardPointTransaction = array(
										"consumer_id" => $userId,
										"date" => date("Y-m-d H:i:s"),
										"transaction_id" => "11",
										"point_amount" => "10"
					);
					$id = $rewardPointTransactionRecordModel->insert($rewardPointTransaction);
					$row->reward_point_transaction_record_id = $id;
					$row->save();
					$rankModel = new Rank();
					$rankModel->changeConsumerRank($userId);
					
				}
				echo "ok";
			}
		}
	}
	
	function adminindexAction(){
		$this->_helper->layout->setLayout("layout_admin");
		$irrModel = new InboxReplyRecord();
		$this->view->irrs = $irrModel->findReplyRecord();
	}
	
}