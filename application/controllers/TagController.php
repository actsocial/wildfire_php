<?php 
include_once( 'weiboconfig.php' );
include_once( 'saetv2.ex.class.php' );
require_once 'couch.php';
require_once 'couchClient.php';
require_once 'couchDocument.php';
class TagController extends MyController {
	
	function indexAction(){
		$config = Zend_Registry::get('config');
		$topicsClient = new couchClient ($config->couchdb->uri.":".$config->couchdb->port,$config->couchdb->topics);
		try {
			$view = $topicsClient->group(TRUE)->asArray()->getView('bayers','topics-untagged-sum-by-folder');
			$this->view->topicsUntaggedSumByFolder = $view['rows'];
			$this->view->issues = array();
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
			$r = $issueModel->updateIssueParticipant($issue);
			if($r){
				$alertModel = new Alert();
				$alertModel->createAlert($this->_currentUser->id,$issue);
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
				$view = $topicsClient->skip($page*PAGESIZE)->limit(PAGESIZE)->reduce(FALSE)->key($key)->asArray()->getView('bayers','topics-untagged-sum-by-folder');
				$this->view->topicIds = $view['rows'];
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
	
	function ajaxgettopicAction(){
		$config = Zend_Registry::get('config');
		$topicsClient = new couchClient ($config->couchdb->uri.":".$config->couchdb->port,$config->couchdb->topics);
		$topicId = $this->_request->getParam('topic');
		$doc = $topicsClient->asArray()->getDoc($topicId);
		$this->view->topic = $doc;
		$this->_helper->layout->disableLayout();
	}
	
	function ajaxgetpostsAction(){
		$topicId = $this->_request->getParam('topic');
		if($topicId){
			$topicId = urldecode($topicId);
			$startKey = array($topicId,0,0);
			$endKey = array($topicId,'{}','{}');
			$config = Zend_Registry::get('config');
			$postsClient = new couchClient ($config->couchdb->uri.":".$config->couchdb->port,$config->couchdb->posts_users);
			$posts = $postsClient->reduce(FALSE)->startkey($startKey)->endkey($endKey)->asArray()->getView('socialmediathread','posts-by-topic');
			$this->view->posts = $posts['rows'];
			$this->_helper->layout->disableLayout();
		}
	}
	
	function ajaxsavereplyAction(){
		$this->_helper->layout->disableLayout();
		$annotations = array('community',$this->_request->getParam('topicId'));
		$tokenNamespace = new Zend_Session_Namespace('token');
		$token =$tokenNamespace->token;
		if($token){
			$c = new SaeTClientV2( WB_AKEY , WB_SKEY , $token['access_token'] );
			$content = $this->_request->getParam('content');
			$result = $c->update($content,NULL,NULL,$annotations);
			if(isset($result['error_code'])){
				print_r($result);
			}else{
				echo "ok";
			}
		}
		
	}
}