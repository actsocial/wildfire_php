<?php
include_once 'Indicate2Connect.php';
class SiteController extends MyController
{
	function privacypolicyAction()
	{

	}
	function termsAction()
	{
		$this->view->title = "Terms and Conditions";
	}

	function sitemapAction()
	{

	}
	function contactusAction()
	{

	}
	
	function showtypeAction() {
		$this->_helper->layout->disableLayout();
     $a_array = array(7130 => 1, 7156 => 1, 5813 => 1, 7169 => 1, 7170 => 1, 7171 => 1, 7172 => 1, 7228 => 1, 7246 => 1,);
     $b_array = array(7128 => 1, 7140 => 1, 7141 => 1, 7151 => -2, 7152 => -1, 7153 => 0, 7154 => 1, 7155 => 2,);
     $c_array = array(7158 => 1, 5815 => 1, 5816 => 1, 5817 => 1,);
     $d_array = array(7246 => 1, 7177 => 3, 7205 => 1, 7228 => 1,);
     $e_array = array(7133 => 1, 7181 => 1, 7182 => 1, 7188 => 1, 7189 => 1, 7190 => 1, 7191 => 1, 7192 => 1, 7193 => 1, 7199 => 1, 7200 => 1, 7201 => 1, 7202 => 1, 7203 => 1, 7204 => 1,);
     $f_array = array(7209 => 2, 7210 => 1, 7215 => 2, 7216 => 1, 7233 => 1,);
     $score_array = array('A' => 0, 'B' => 0,'C' => 0,'D' => 0,'E' => 0,'F' => 0);
	 $accessCode = $this->_request->getParam('accessCode');
	 $indicate2Connect = new Indicate2_Connect();
	 $response = $indicate2Connect->getAnswerSetForAccessCode(array($accessCode));

	 if(isset($response->AnswerSetType)) {
	   if(isset($response->AnswerSetType->AnswerType)) {
	     if(is_array($response->AnswerSetType->AnswerType)) {
	     	foreach($response->AnswerSetType->AnswerType as $answer):
	     	   if(is_array($answer->AnswerText)) {
	     	   	foreach($answer->AnswerText as $answerText):
	     	   	  $key = base64_decode($answerText);
	     	   	  if(is_numeric($key)) {
	     	   	    $this->calculate_score(&$score_array, $key, $a_array, 'A');
	     	   	    $this->calculate_score(&$score_array, $key, $b_array, 'B');
	     	   	    $this->calculate_score(&$score_array, $key, $c_array, 'C');
	     	   	    $this->calculate_score(&$score_array, $key, $d_array, 'D');
	     	   	    $this->calculate_score(&$score_array, $key, $e_array, 'E');
	     	   	    $this->calculate_score(&$score_array, $key, $f_array, 'F');
	     	   	  }
	     	   	endforeach;
	     	   } else {
	     	   	  $key = base64_decode($answer->AnswerText);
                  if(is_numeric($key)) {
	     	   	    $this->calculate_score(&$score_array, $key, $a_array, 'A');
	     	   	    $this->calculate_score(&$score_array, $key, $b_array, 'B');
	     	   	    $this->calculate_score(&$score_array, $key, $c_array, 'C');
	     	   	    $this->calculate_score(&$score_array, $key, $d_array, 'D');
	     	   	    $this->calculate_score(&$score_array, $key, $e_array, 'E');
	     	   	    $this->calculate_score(&$score_array, $key, $f_array, 'F');
	     	   	  }
	     	   }
	     	endforeach;
	     }
	   }
	 } else {	  

	 }
	 $max = 0;
	 foreach ($score_array as $key => $value) {
        if($value > $max) {
        	$max = $value;
        }
     }
     if($max < 3) {
     	$this->view->type = 'C';
     } else {
     	$this->view->type = array_search($max, $score_array);
     }
     $this->view->accessCode = $accessCode;
	}
	
	function calculate_score($score_array, $key, $rule_array, $type) {
		if(array_key_exists($key, $rule_array)) {
			$score_array[$type] += $rule_array[$key];
		}	
	}
	

	function iframeAction(){
		$this->_helper->layout->disableLayout();
		$this->view->con =  $this->_request->getParam('c');
		$this->view->act =  $this->_request->getParam('a');
		$this->view->survey = (int)$this->_request->getParam('survey',0);

	}

	function statusAction(){
		$campaginId = rand(10,12);
		$this->_helper->layout->disableLayout();
		$db = Zend_Registry::get('db');
		$select1 = $db->select();
		$select1->from('consumer', 'count(*)');
		$select1->where('consumer.pest != 1 or consumer.pest is null');
		$this->view->sparks = $db->fetchOne($select1);
		//		Zend_Debug::dump($this->view->sparks);

		$select2 = $db->select();
		$select2->from('report', '*');
		$select2->join('consumer', 'report.consumer_id = consumer.id');
		$select2->join('campaign', 'campaign.id = report.campaign_id')
		->where('consumer.pest is null or consumer.pest != 1')
		->where('report.state = "APPROVED" or report.state = "NEW" or report.state="LOCKED"')
		->where('campaign.id = '.$campaginId);
		$this->view->reports = count($db->fetchAll($select2));

		$select3 = $db->select();
		$select3->from('report', '*');
		$select3->join('consumer', 'report.consumer_id = consumer.id');
		$select3->join('campaign', 'campaign.id = report.campaign_id')
		->where('consumer.pest is null or consumer.pest != 1')
		->where('campaign.id = '.$campaginId)
		->where('report.state = "APPROVED"');
		$this->view->reply = count($db->fetchAll($select3));
		
		$select3 = $db->select();
		$select3->from('report', '*');
		$select3->join('consumer', 'report.consumer_id = consumer.id');
		$select3->join('campaign', 'campaign.id = report.campaign_id')
		->where('report.state = "APPROVED"');
		$this->view->pooled = count($db->fetchAll($select3));

		$select5 = $db->select();
		$select5->from('product_order', 'count(*)')
		->where('product_order.state = "NEW"');
		if ($db->fetchOne($select5)>0){
			$this->view->redeemed = 'New Redeem';
		}else{
			$this->view->redeemed = '          ';
		}

		$select6 = $db->select();
		$select6->from('report','Timediff(now(),report.create_date) as diff');
		$select6->join('consumer','consumer.id = report.consumer_id');
		$select6->where('report.state="NEW"');
		$select6->where('report.campaign_id='.$campaginId);
		$select6->where('consumer.pest is null')
		->order('diff desc');
		$diff = $db->fetchOne($select6);
		$diffArray = explode(':',$diff);
		if ($diffArray[0]>=24){
			$this->view->oldest = floor(($diffArray[0]/24))."d".($diffArray[0]%24)."h";
		}else{
			$this->view->oldest = $diffArray[0]."h".$diffArray[1];
		}
		$select9 = $db->query('select max(c1.id) from consumer c1,consumer c2, consumer c3 where  c3.password=c2.password and c2.password = c1.password and c2.id-c1.id>0 and c2.id-c1.id<5 and c3.id-c2.id>0 and c3.id-c2.id<5 and c1.pest is null and c2.pest is null and c3.pest is null and c1.password!="96e79218965eb72c92a549dd5a330112";');
		$suspects = $select9->fetchAll();
		if (intval($suspects['0']['id'])>4854){
			$this->view->alarm = 'PEST ALARM';
		}else{
			$this->view->alarm = '          ';
		}
		
		$params = array ('host'     => '127.0.0.1',
                 'username' => 'root',
               'password' => 'j8MfYXcw',
//				 'password' => '',
                 'dbname'   => 'feedback');
		$db2 = Zend_Db::factory('PDO_MYSQL', $params);
		$select4 = $db2->query('select count(distinct(p.email)) as con from SurveyParticipation sp, Participant p where sp.participant_id = p.id and sp.dateUsed is not null;');
		$profilled1 = $select4->fetchAll();
		
		$select5 = $db2->query("select count(distinct a.stringvalue) as con from Answer a where a.stringvalue like '%@%' and a.stringvalue not like '% %' and a.stringvalue not in (select distinct(p.email) from SurveyParticipation sp, Participant p where sp.participant_id = p.id and sp.dateUsed is not null);");
		$profilled2 = $select5->fetchAll();
		$this->view->profil = $profilled1['0']['con']+$profilled2['0']['con'];
		
//		Zend_Debug::dump($profilled);


	}
	
	function remoteurlAction(){
		$url = $this->_request->getParam('url');
		$contents = file_get_contents($url); // deprecated by ice, for performance reason
		$handle = @fopen($url, "r");		
		stream_set_timeout($handle, 0, 200);// 200  ms	
		$this->view->contents = stream_get_contents($handle);
//		$info = stream_get_meta_data($handle);
		fclose($handle);
		$this->_helper->layout->disableLayout();
	}
}