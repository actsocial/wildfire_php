<?php 
include_once('saetv2.ex.class.php');
include_once('weibo.uri.utility.php');
require_once 'site.php';
require_once 'couch.php';
require_once 'couchClient.php';
require_once 'couchDocument.php';
require_once 'HttpClient.class.php';
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
		$this->view->image_uri = $config->image->host."/".(int)$this->_currentUser->id.".ifmg";
		$this->_helper->layout->setLayout("layout_isotope");
		
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
		define("PAGESIZE",50);
		$config = Zend_Registry::get('config');
		$topicsClient = new couchClient ($config->couchdb->uri.":".$config->couchdb->port,$config->couchdb->topics);
		try {
			$key = $this->_request->getParam('key');
			$page = $this->_request->getParam('page')|0;
			$totalCount = $this->_request->getParam('totalCount')|0;
			$this->view->page = $page;
			if($key){
				$endKey = array($key,array(0,0,0,0,0,0));
				$startKey = $this->_request->getParam('start_key');
				if(empty($startKey)){
					$startKey = array($key,array('{}','{}','{}','{}','{}','{}'));
				}else{
					$startKey = array($key,array((int)$startKey[1][0],(int)$startKey[1][1],(int)$startKey[1][2],(int)$startKey[1][3],(int)$startKey[1][4],(int)$startKey[1][5]));
				}
				$startkey_docid = $this->_request->getParam('startkey_docid');
				
				$view = $topicsClient->limit(PAGESIZE)->startkey_docid($startkey_docid)->reduce(FALSE)->startkey($startKey)->endkey($endKey)->stale("ok")->asArray()->descending(TRUE)->getView('bayers','topics-by-folder');
				
				$this->view->key = $key;
				if($totalCount>0){
					$this->view->totalPage = ceil($totalCount/PAGESIZE);
				}else{
					$this->view->totalPage = "-";
				}
				$this->view->totalCount = $totalCount;
				$result = array();
				$topics = array();
				
				$topicIds = array();
				foreach($view['rows'] as $topic):
					array_push($topicIds,$topic['id']);
				endforeach;
				
				$user = $this->_currentUser->id;
				$irrModel = new InboxReadRecord();
				$readResult = $irrModel->findReadTopicByUserAndTopicIds($user,$topicIds);
				foreach($view['rows'] as $topic):
//					if(!isset($topic['value']['title'])){
//						$topic['value']['title'] = "-";
//					}
// 					if(isset($topic['value']['date_posted'])){
// 						list($pyear,$pmonth,$pday,$phour,$pminute,$psecond) = $topic['value']['date_posted'];
// 						$pmonth+=1;
// 						$pdate = date("Y-m-d H:i:s",mktime($phour,$pminute,$psecond,$pmonth,$pday,$pyear));
						
// 						$diff = time() - strtotime($pdate);
// 						if($diff > 3600 * 24 *7){
// 							continue;
// 						}
// 					}
					
					if(isset($topic['key'][1])){
						list($year,$month,$day,$hour,$minute,$second) = $topic['key'][1];
						$month+=1;
						$date = date("Y-m-d H:i:s",mktime($hour,$minute,$second,$month,$day,$year));
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
					if(in_array($topic['id'],$readResult)){
						$topic['value']['read'] = true;
					}else{
						$topic['value']['read'] = false;
					}
					$url = $topic['value']['site'];
					$site = getInfoBySiteUrl($url);
					$site['url'] = $url;
					$topic['value']['site'] = $site;
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
				if(isset($post['value']['date'])){
					list($year,$month,$day,$hour,$minute,$second) = $post['value']['date'];
					$month+=1;
					$date = date("Y-m-d g:i:s a",mktime($hour,$minute,$second,$month,$day,$year));
					$post['value']['date'] = $date;
				}else{
					$date = "-";
					$post['value']['date'] = $date;
				}
				array_push($return_posts,$post);

			endforeach;
			//save read record
			$irrModel = new InboxReadRecord();
			$row = $irrModel->createRow();
			$row->topic = $topicId;
			$row->consumer = $this->_currentUser->id;
			$row->save();
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
			if(isset($result['error_code'])){
				print_r($result);
			}else{
				$mid = $result['idstr'];
				$userId = $this->_currentUser->id;
				$irrModel = new InboxReplyRecord();
				$row = $irrModel->createRow();
				$row->topic = $topicId;
				$row->consumer = $userId;
				$row->platform_type = "weibo";
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
	
	function ajaxreplyAction(){
		$platform = $this->_request->getParam('platform');
		$config = Zend_Registry::get('config');
		$host = $config->writer->host;
		$port = $config->writer->port;
		$client = new HttpClient($host,$port);
// 		$client->post("path","data");
// 		$content = $client->getContent();
		$snsUserModel = new SnsUser();
		$snsUser = $snsUserModel->loadByConsumerAndPlatform($this->_currentUser->id,$platform);
		if(empty($snsUser)){
			$this->_redirect("/sns/index");
		}else{
			$this->_helper->layout->disableLayout();
			$param = $snsUser->toArray();
			$param['text'] = $this->_request->getParam('text');
			$param['text'] = urldecode($this->_request->getParam('topicId'));
			$client->post("/sender/commets",$param);
		}
	}
	
	function ajaxpublicAction(){
		$platform = $this->_request->getParam('platform');
		$config = Zend_Registry::get('config');
		$host = $config->writer->host;
		$port = $config->writer->port;
		$client = new HttpClient($host,$port);
		// 		$client->post("path","data");
		// 		$content = $client->getContent();
		$snsUserModel = new SnsUser();
		$snsUser = $snsUserModel->loadByConsumerAndPlatform($this->_currentUser->id,$platform);
		if(empty($snsUser)){
			$this->_redirect("/sns/index");
		}else{
			$this->_helper->layout->disableLayout();
			$param = $snsUser->toArray();
			$param['text'] = $this->_request->getParam('text');
			$client->post("/sender/public_tweet",$param);
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
				$row->platform_type = "weibo";
				$row->sns_reply_id = $result['idstr'];
				$row->save();
				$replyCount = $irrModel->findReplyCount(array('consumer' => $userId,'platform_type'=>'weibo','topic'=>$topicId));
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
	
	function testAction(){
		$this->_helper->layout->disableLayout();
	}
	
	
	function completeAction(){
		$this->_helper->layout->disableLayout();
		$topic_uri = urldecode($this->_request->getParam('topic_uri'));
		$config = Zend_Registry::get('config');
		$host = $config->ws->host;
		$port = $config->ws->port;
		$uri = $config->ws->uri;
								
	  $client = new HttpClient($host,$port);
		$client->post($uri, array(
		  'topic_uri' => $topic_uri
		));
		
	}
	
}