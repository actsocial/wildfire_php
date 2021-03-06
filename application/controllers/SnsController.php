<?php
require_once 'HttpClient.class.php';

class SnsController extends MyController
{
	public function indexAction() 
	{
		//$this->_helper->layout->disableLayout();
		$db = Zend_Registry::get ( 'db' );
		$select = $db->select()
		             ->from('sns_user')
								 ->where('consumer = ?', (int)$this->_currentUser->id);
       
	  $sns_users = $db->fetchAll($select);
		$this->view->sns_users = $sns_users;
		
		$this->view->sns_enabled_source = Sns::$_enabled_source;

		$config = Zend_Registry::get('config');
		$this->view->writer_host = $config->writer->host;
    $this->view->current_user_id = (int)$this->_currentUser->id;
	}
	
		
	public function ajaxcheckandsaveAction() {
    $this->_helper->layout->disableLayout();
    $code = urldecode($this->_request->getParam('code'));
    
    $config = Zend_Registry::get('config');
    $writer_host = $config->writer->host;
    $uri = "/sns/find_sns.json";
    //TODO: use config.ini
    $client = new HttpClient("localhost", "4000");
    
    $access_token = NULL;
    $access_token_secret = NULL;
    $refresh_access_token = NULL;
    $expires_at = NULL;
    $expires_in = NULL;
    $username = NULL;
    $user = NULL;
    $nick = NULL;
    $profile_img_path = NULL;
    $big_profile_img_path = NULL;
    $small_profile_img_path = NULL;
       
    $client->get($uri, array(
      'code' => $code
    ));

    if ($client->getStatus() == "200") {
      $rs = json_decode($client->getContent());

      if (isset($rs->access_token_secret)) $access_token_secret = $rs->access_token_secret;
      if (isset($rs->refresh_access_token)) $refresh_access_token = $rs->refresh_access_token;
      if (isset($rs->expires_at)) $expires_at = $rs->expires_at;
      if (isset($rs->expires_in)) $expires_in = $rs->expires_in;
      if (isset($rs->username)) $username = $rs->username;
      if (isset($rs->user)) $user = $rs->user;
      if (isset($rs->nick)) $nick = $rs->nick;
      if (isset($rs->profile_img_path)) $profile_img_path = $rs->profile_img_path;
      if (isset($rs->big_profile_img_path)) $big_profile_img_path= $rs->big_profile_img_path;        
      if (isset($rs->small_profile_img_path)) $small_profile_img_path= $rs->small_profile_img_path;
      
      $sns = new Sns();
      $row = $sns->fetchRow(
        $sns->select()
            ->where('access_token = ?', $rs->access_token)
      );
      
      if(isset($row)){
        $data = array(
          'access_token' => $rs->access_token
        );
        $where = $sns->getAdapter()->quoteInto('id = ?', $row->id);
        $sns->update($data, $where);
      } else {       
        try {         
          $data = array(
            'code' => $code,
            'access_token' => $rs->access_token,
            'access_token_secret' => $access_token_secret,
            'refresh_access_token' => $refresh_access_token,
            'expires_at' => $expires_at,
            'expires_in' => $expires_in,
            'consumer' => (int)$this->_currentUser->id,
            'source' => $rs->source,
            'timestamp' => date("Y-m-d H:i:s"),
            'username' => $username,
            'user' => $user,
            'nick' => $nick,
            'profile_img_path' => $profile_img_path,
            'big_profile_img_path' => $big_profile_img_path,
            'small_profile_img_path' => $small_profile_img_path            
          );           
          $sns->insert($data);
          $this->_helper->json(1);
        } catch(Exception $e) {
          print_r($e);
        }
      }
      $response = array(
        "status" => 1
      );
    } else {
      $response = array(
        "status" => 0
      );     
    }
   
    $this->getHelper('json')->sendJson($response);
    
	}
  
	public function commentsAction() {
    $config = Zend_Registry::get('config');
    $host = $config->writer->host;
    $client = new HttpClient("localhost", "4000");
    
    $snsModel = new Sns();
    $source = urldecode($this->_request->getParam('source'));
    $sns = $snsModel->loadByConsumerAndSource((int)$this->_currentUser->id,$source);
    
    if(empty($sns)){ 
      $response = array(
        "status" => 0
      );
    } else {
      $this->_helper->layout->disableLayout();
      $param['source'] = $this->request->getParam('source');
      $param['source_id'] = $this->request->getParam('source_id');
      $param['user_id'] = $this->request->getParam('user_id');
      $param['source_type'] = $this->_request->getParam('source_type');
      $param['content'] = urldecode($this->_request->getParam('content'));
      
      $param['access_token'] = $sns->access_token;
      $param['access_token_secret'] = $sns->access_token_scret;
      $param['refresh_access_token'] = $sns->refresh_access_token;
      $param['expires_at'] = $sns->expires_in;
      $param['expires_in'] = $sns->expires_at;
      $client->post("/sender/comments", $param);
      $response = array(
        "status" => 1
      );
    }
	}
	 
}




