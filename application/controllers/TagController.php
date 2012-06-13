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
	
	function ajaxtopicsAction(){
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
				$this->view->key = $key;
				if($totalCount>0){
					$this->view->totalPage = ceil($totalCount/PAGESIZE);
				}else{
					$this->view->totalPage = "-";
				}
				$this->view->totalCount = $totalCount;
				$result = array();
				$topics = array();
				foreach($view['rows'] as $topic):
					if(!isset($topic['value']['title'])){
						$topic['value']['title'] = "-";
					}
					if(isset($topic['key'][1])){
						list($year,$month,$day,$hour,$minute,$second) = $topic['key'][1];
						$month+=1;
						$date = date("Y-m-d g:i:s a",mktime($hour,$minute,$second,$month,$day,$year));
					}else{
						$date = "-";
					}
					$topic['value']['date'] = $date;
					if(isset($topic['value']['body'])){
						$body = $topic['value']['body'];
					}else{
						$body = "-";
					}
					$topic['value']['body'] = $body;
					if(isset($topic['value']['tracker'])){
						$lastestKey = false;
						foreach($topic['value']['tracker'] as $k => $v):
						if(!$lastestKey){
							$lastestKey = $k;
						}else if($lastestKey < $k){
							$lastestKey = $k;
						}
						endforeach;
						$tracker = $topic['value']['tracker'][$lastestKey];
						if(isset($tracker['views'])){
							$views = $tracker['views'];
						}else{
							$views = "-";
						}
						if(isset($tracker['comments'])){
							$comments = $tracker['comments'];
						}else{
							$comments = "-";
						}
					}else{
						$comments = "-";
						$views = "-";
					}
					$topic['value']['comments'] = $comments;
					$topic['value']['views'] = $views;
					array_push($topics,$topic);
				endforeach;
				$this->view->topics = $topics;
				$result['topics'] = $this->view->topics;
				$result['key'] = $this->view->key;
				$result['totalCount'] = $this->view->totalCount;
				$result['page'] = $this->view->page;
				$this->_helper->json($result);
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
			$return_posts = array();
			foreach($posts['rows'] as $post):
				$author = explode("@",$post['value']['author']);
				$post['value']['author'] = $author[0];
// 				$dateArr = $post['value']['date'];
// 				$post['value']['date'] = date(DATE_RFC822,mktime($dateArr[3],$dateArr[4],$dateArr[5],$dateArr[1],$dateArr[2],$dateArr[0]));
				array_push($return_posts,$post);
			endforeach;
			$this->_helper->layout->disableLayout();
			$this->_helper->json($return_posts);
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