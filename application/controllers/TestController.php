<?php
include_once 'Indicate2Connect.php';
include_once 'curl.php';

class TestController extends MyController
{
	function indexAction()
	{
//		$this->_helper->layout->disableLayout();
   
//		$login = "bohj008";
//		$password = "bohjJQQ";
//		include_once 'sina.php';
//		$resultarray = get_sinacontacts($login, $password);
//		Zend_Debug::dump($resultarray);

//		include_once '163.php';	
//		$login = 'yun_simon';
//		$password = '19990402';
//		$resultarray = get_163contacts($login, $password);
//		Zend_Debug::dump($resultarray);

//		include_once 'yahoo.php';
//		$obj = new GrabYahoo();
//		$contacts = $obj->getAddressbook('yun_simon@yahoo.cn','19990402');
//		Zend_Debug::dump($contacts);
		
//		include_once "contacts_fn.php";
//		$ret_array = get_msncontacts('guojianyun@cse.buaa.edu.cn', '1999040211');
//		Zend_Debug::dump($ret_array);
//		$this->_helper->layout->setLayout("layout_admin");
		$this->_helper->layout->disableLayout();
		// get date from db
        $db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('report', array('left(create_date,10) as date', 'accesscode'))
		->where('report.campaign_id = 3')
		->where("report.state = 'APPROVED'")
		->order('date');
		$results = $db->fetchAll($select);
		$accesscodeDbMatchArray = array();
		foreach($results as $result):
			$accesscodeDbMatchArray[$result['accesscode']] = $result['date'];
		endforeach;
		$accesscodeArray   =   array_keys($accesscodeDbMatchArray);
//		Zend_Debug::Dump($accesscodematchArray);
		// get value from ws
		$indicate2Connect = new Indicate2_Connect();
        $response = $indicate2Connect->getAnswerSetForSurvey(385, null, 
        array(array('ContextIndex' => 1,'QuestionIndex' => 1),array('ContextIndex' => 1,'QuestionIndex' => 4)), 0);
//        Zend_Debug::Dump($response);
        $talkingdurationArray = array();
        $accesscodeResponseMathArray = array();
        if(isset( $response->AnswerSetType)){
        	foreach($response->AnswerSetType as $answerSet):
        		if(isset($answerSet->AnswerType) && is_array($answerSet->AnswerType) && !empty($answerSet->AnswerType)){
        			foreach($answerSet->AnswerType as $answer):
        				if($answer->QuestionId == 573){
        					$accesscodeResponseMathArray[$answerSet->AccessCode] = (int)iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ', base64_decode($answer->AnswerText)));
        				}
        				if($answer->QuestionId == 576){
        					$talkingdurationArray[$answerSet->AccessCode] = (int)iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ', base64_decode($answer->AnswerText)));
        				}
        			endforeach;
        		}else{
        			//get min value, like '11-15' = 11 
        			$accesscodeResponseMathArray[$answerSet->AccessCode] = (int)iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ', base64_decode($answerSet->AnswerType->AnswerText)));
        		}
        	endforeach;
        }
//		Zend_Debug::Dump($accesscodeResponseMathArray);
//		Zend_Debug::Dump($talkingdurationArray);
		$resultmatchArray = array();
		foreach($accesscodeArray as $accesscode):
			$resultmatchArray[$accesscodeDbMatchArray[$accesscode]] = 0;
		endforeach;
		foreach($accesscodeArray as $accesscode):
			if(array_key_exists($accesscode,$accesscodeResponseMathArray)){
				$resultmatchArray[$accesscodeDbMatchArray[$accesscode]] += $accesscodeResponseMathArray[$accesscode];
			}
		endforeach;
//		 Zend_Debug::Dump($resultmatchArray);
		$dateArray   =   array_keys($resultmatchArray);
		foreach($dateArray as $date):
			$resultmatchArray[$date] = floor($resultmatchArray[$date]*1.65);
		endforeach;
//        Zend_Debug::Dump($resultmatchArray);
        
        
		include 'open-flash-chart.php';
		
		$data_1 = array();
		$base = floor(1472*1.65);
		$temp = 0;
		$resultmatchArray[0] = 0;
		foreach($dateArray as $date):
		  $resultmatchArray[$date] += $resultmatchArray[$temp];
		  $data_1[] = $resultmatchArray[$date] + $base;
		  $temp = $date;
		endforeach;	
//		Zend_Debug::Dump($data_1);
		$title = new title( "Impressions of BugsLock by day" );
		$y = new y_axis();
		$y->set_range( 0, 40000, 10000);
		
		$x = new x_axis();
		$x_labels = new x_axis_labels();
		$x_labels->set_labels($dateArray);
		$x_labels->set_steps( 2 );
		$x_labels->rotate(40);
		$x->set_labels( $x_labels );
		
		$line_1_default_dot = new dot();
		$line_1_default_dot->colour('#f00000')->tooltip('#x_label#: #val#');
		
		$line_1 = new line();
		$line_1->set_default_dot_style($line_1_default_dot);
		$line_1->set_values( $data_1 );
		$line_1->set_width( 1 );
		$line_1->set_key( 'Impression', 10 );
		
		
		$this->view->chart = new open_flash_chart();
		$this->view->chart->set_title( $title );
		$this->view->chart->add_element( $line_1 );
		$this->view->chart->set_y_axis( $y );
		$this->view->chart->set_x_axis( $x );
		$this->view->chart->set_bg_colour( '#FFFFFF' );
		
//

		include 'ofc_sugar.php';
		
		
		$this->view->chart2 = new open_flash_chart();
		$this->view->chart2->set_title( new title( 'Generation Chart' ) );
		
		$line_1 = new line();
		$array_1 = array();
		$f = 1.5;
		for($i = 0; $i<=50; $i++){
			array_push($array_1,$f);
		}
		$line_1->set_values($array_1);
//		$line_1->set_default_dot_style( new s_hollow_dot('#FBB829', 4) );
		$line_1->set_width( 1 );
		$line_1->set_colour( '#FF0000' );
		$line_1->set_tooltip( "Gen0" );
		$line_1->set_key( 'Gen0', 10 );
		$line_1->loop();
		
		$area = new area();
		// set the circle line width:
		$area->set_width( 1 );
//		$area->set_default_dot_style( new s_hollow_dot('#45909F', 5) );
		$area->set_colour( '#FF0000' );
//		$area->set_fill_colour( '#FF0000' );
//		$area->set_fill_alpha( 0.4 );
		$area->set_loop();
		$area->set_values($array_1);

		$line_2 = new line();
		$array_2 = array();
		$f = 2.8;
		for($i = 0; $i<=50; $i++){
			array_push($array_2, $f);
		}
		$line_2->set_values($array_2);
//		$line_2->set_default_dot_style( new s_hollow_dot('#FBB829', 4) );
		$line_2->set_width( 1 );
		$line_2->set_colour( '#FBB829' );
		$line_2->set_tooltip( "Gold<br>#val#" );
		$line_2->set_key( 'Gen1', 10 );
		$line_2->loop();
		
		$line_4 = new line();	
		$array_4 = array();
		$f = 5;
		for($i = 0; $i<=50; $i++){
			array_push($array_4, $f);
		}
		$line_4->set_values($array_4);
		//		$line_2->set_default_dot_style( new s_star('#8000FF', 4) );
		$line_4->set_width( 1 );
		$line_4->set_colour( '#3030D0' );
		$line_4->set_tooltip( "Purple<br>#val#" );
		$line_4->set_key( 'Gen2', 10 );
		$line_4->loop();
		
		
		// add the area object to the chart:
		$this->view->chart2->add_element( $line_1 );
		$this->view->chart2->add_element( $line_2 );
		$this->view->chart2->add_element( $line_4 );
		
		$r = new radar_axis( 5 );
		$r->set_colour( '#FFFFFF' );
		$r->set_grid_colour( '#FFFFFF' );
		
		$labels = new radar_axis_labels( array('G0','','G1','','G2') );
		$labels->set_colour( '#9F819F' );
		$r->set_labels( $labels );
		
		$this->view->chart2->set_radar_axis( $r );
		
		$tooltip = new tooltip();
		$tooltip->set_proximity();
		$this->view->chart2->set_tooltip( $tooltip );
		
		$this->view->chart2->set_bg_colour( '#ffffff' );
		
//		echo $this->view->chart2->toPrettyString(); 

	}
	public function index2Action(){
		$this->_helper->layout->disableLayout();
		
		include 'open-flash-chart.php';
		
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select -> from('report', array('left(create_date,10) as date', 'count(*)'))
		->where('report.campaign_id = 3')
		->where("report.state = 'APPROVED'")
		->group('date')
		->order('date')
		->limit(0);
		$results = $db->fetchAll($select);
		$array_data = array();
		$array_create_date = array();
		foreach($results as $result):
			array_push($array_data, (int)$result["count(*)"]);
			array_push($array_create_date, $result["date"]);
		endforeach;
		
		$title = new title( "BugsLock Reports By Day" );

		
		$y = new y_axis();
		$y->set_range( 0, 100, 10 );
		
		$x = new x_axis();
		$x_labels = new x_axis_labels();
		$x_labels->set_labels($array_create_date);
		$x_labels->set_steps( 2 );
		$x_labels->rotate(40);
		$x->set_labels( $x_labels );
		//There is a bug on the tooltip of bar: can not show #x_label#. So use bar_stack instead of bar here.
//		$bar = new bar_filled( '#E2D66A', '#577261' );
//		$bar->set_values($array_data);
//		$bar->set_tooltip('#x_label#: #val#');
		$bar = new bar_stack();
		$bar->set_colours(array( '#E2D66A', '#577261' ));
		foreach ($array_data as $date):
		$bar->append_stack(array((int)$date));
		endforeach;
		$bar->set_tooltip('#x_label#: #val#');

		$this->view->chart3 = new open_flash_chart();
		$this->view->chart3->set_title( $title );
		$this->view->chart3->add_element( $bar );
		$this->view->chart3->set_bg_colour( '#FFFFFF' );
		$this->view->chart3->set_x_axis( $x );
		$this->view->chart3->set_y_axis( $y );
		
		
		
//		echo $this->view->chart3->toPrettyString();
	}
	public function test2Action(){
		//post
		if($this->_request->isPost()){
			$formData = $this->_request->getPost();
			$accessCodeList = array();
			// get access code from post
			if(isset($formData['accessCode'])){
				$accessCodeList = preg_split('/[;\s]+[\n\r\t]*/', trim($formData['accessCode']));
			}else{
			// get accesscode from campaign
				$db = Zend_Registry::get('db');
				$selectAccessCode = $db->select();
				$selectAccessCode->from('report', array('accesscode', 'create_date'))
				->joinLeft('reward_point_transaction_record', 'report.reward_point_transaction_record_id = reward_point_transaction_record.id', 'point_amount')
				->where('report.campaign_id = ?',$formData['campaign_id'])
				->limit(0);
				$reportInforArray = array();
				$accessCodeArray = $db->fetchAll($selectAccessCode);
				foreach($accessCodeArray as $accessCode):
					array_push($accessCodeList,$accessCode['accesscode']);
					$reportInforArray[$accessCode['accesscode']]['createdate'] = $accessCode['create_date'];
					$reportInforArray[$accessCode['accesscode']]['point'] = $accessCode['point_amount'];
				endforeach;
				// get reply for report
				$selectReportReply = $db->select();
				$selectReportReply->from('report', 'accesscode')
				->joinLeft('reply', 'report.id = reply.report_id', 'content')
				->where('reply.campaign_id = ?',$formData['campaign_id'])
				->limit(0);
				$reportReplyArray = $db->fetchAll($selectReportReply);
				foreach($reportReplyArray as $reply):
					$reportInforArray[$reply['accesscode']]['reply'] = $reply['content'];
				endforeach;
			}
			$this->view->reportExtraInfoArray = $reportInforArray;
			// get reports
			$indicate2Connect = new Indicate2_Connect();
			$response = $indicate2Connect->getAnswerSetForAccessCode($accessCodeList);
			$this->view->surveyQuestionArray = $response->QuestionType;
			$this->view->surveyArray = $response->AnswerSetType;
//			Zend_Debug::dump($response);
			$this->_helper->layout->disableLayout();
		}
	} 
	public function testAction()
	{	
		// post
		if ($this->_request->isPost()) {
			$formData = $this->_request->getPost();
			//get survey
			$surveyId = $this->view->surveyId = $formData['survey_id'];
			if($formData['language'] == 'en'){
				$surveyId = $formData['survey_'.$surveyId.'_en'];
			}
			$db = Zend_Registry::get('db');
			$selectEmail = $db->select();
			$selectEmail ->from('consumer', array('email', 'province', 'city', 'address1', 'phone'))
			->join('poll_participation', 'poll_participation.consumer_id = consumer.id', null)
			->join('profile_survey', 'profile_survey.id = poll_participation.poll_id', null)
			->joinLeft('consumer_extra_info', 'consumer_extra_info.consumer_id = consumer.id')
			->limit(0);
			if($formData['language'] == 'en'){
				$selectEmail->where("language_pref = 'en'")
				->where('profile_survey.i2_survey_id_en = ?', $surveyId);
			}else{
				$selectEmail->where("language_pref != 'en'")
				->where('profile_survey.i2_survey_id = ?', $surveyId);
			}
			$emailArray = $db->fetchAll($selectEmail);
			
			$emailList = array();
			$this->view->consumerExtraInfoArray = array();
			foreach($emailArray as $email){
				array_push($emailList, $email['email']);
				$this->view->consumerExtraInfoArray[$email['email']] = array($email['province'],$email['city'],$email['address1'],$email['phone'],
				$email['gender'],$email['birthdate'],$email['profession'],$email['education'],$email['have_children'],$email['children_birth_year'],
				$email['income'],$email['online_shopping'],$email['use_extra_bonus_for']);
			}
			if(count($emailList) > 0){
				$indicate2Connect = new Indicate2_Connect();
				$response = $indicate2Connect->getAnswerSetForParticipant($surveyId, $emailList);
				$this->view->surveyQuestionArray = $response->QuestionType;
				$this->view->surveyArray = $response->AnswerSetType;
			}else{
				$this->view->surveyQuestionArray = null;
				$this->view->surveyArray = null;
			}
//			Zend_Debug::dump($response);
//			return;
			$this->_helper->layout->disableLayout();
		}
	}
	
	public function testpchartAction(){
		$this->_helper->layout->disableLayout();
	}
	
	public function jschartAction(){
//		$this->_helper->layout->disableLayout();
		$this->_helper->layout->setLayout("layout_admin");
		
		$this->view->showArray = $this->testdemoAction();
		$this->view->showdate = '';
		$this->view->showreport = '';
		foreach($this->view->showArray as $show){
			$this->view->showdate .= $show['date'].';';
			$this->view->showreport .= $show['count'].';';
		}
		
		// select gender
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('consumer', array("gender", "count(*) as count"))
		->join('campaign_invitation', 'campaign_invitation.consumer_id = consumer.id', null)
		->where('campaign_invitation.campaign_id = 2')
		->where('campaign_invitation.state != "NEW"')
		->where('consumer.pest is null')
		->group("consumer.gender");
		$genderArray = $db->fetchAll($select);
		$this->view->showgender = '';
		$this->view->showgendercount = '';
		foreach($genderArray as $gender){
			if($gender['gender'] == null){
				$this->view->showgender .= 'Unkown;';
			}else{
				$this->view->showgender .= $gender['gender'].';';
			}			
			$this->view->showgendercount .= $gender['count'].';';
		}
		// select province
		$selectprovince = $db->select();
		$selectprovince->from('consumer', array("province", "count(*) as count"))
		->where("pest is null")
		->group("consumer.province")
		->order("count desc");
		$provinceArray = $db->fetchAll($selectprovince);
		$this->view->showprovince = '';
		$this->view->showprovincecount = '';
		$i = 0;
		foreach($provinceArray as $province){
			if($province['province'] == null || $province['province'] == ''){
				$this->view->showprovince .= 'Unkown;';
			}else{
				$this->view->showprovince .= $i++.'s;';
			}			
			$this->view->showprovincecount .= $province['count'].';';
		}
//		Zend_Debug::dump($provinceArray);
	}
	public function flotAction(){
		$this->_helper->layout->disableLayout();
		
		$this->view->showArray = $this->testdemoAction();
		$this->view->showdate = '';
		$this->view->showreport = '';
		foreach($this->view->showArray as $show){
			$this->view->showdate .= strtotime($show['date']).';';
			$this->view->showreport .= $show['count'].';';
		}
//		Zend_Debug::dump(strtotime("2007-8-20"));
	}
	public function pchartAction(){
//		$this->_helper->layout->disableLayout();
		$this->_helper->layout->setLayout("layout_admin");

		 include("pData.class");   
		 include("pChart.class");   
		  
		$this->view->showArray = $this->testdemoAction();
		$showdate = array();
		$showreport = array();
		foreach($this->view->showArray as $show){
			array_push($showdate, substr($show['date'],strlen($show['date'])-4,4));
			array_push($showreport, $show['count']);
		}

		 // Dataset definition    
		 $DataSet = new pData;   
		 $DataSet->AddPoint(array_slice($showreport,0,26) ,"Serie1");    
		 $DataSet->AddPoint(array_slice($showdate,0,26) ,"Serie2");     
		$DataSet->AddSerie("Serie1");  
		 $DataSet->SetAbsciseLabelSerie("Serie2");   
		 $DataSet->SetSerieName("Reports","Serie1");  
		 $DataSet->SetYAxisName('Report Count');
		 $DataSet->SetXAxisName('Date'); 

		  
		 // Initialise the graph   
		 $Test = new pChart(900,250);   
		 $Test->setFontProperties("xihei.ttf",8);   
		 $Test->setGraphArea(50,30,780,200);   
		 $Test->drawFilledRoundedRectangle(7,7,793,248,5,255,255,255);   
		 $Test->drawRoundedRectangle(5,5,795,249,5,230,230,230);   
		 $Test->drawGraphArea(255,255,255,TRUE);   
		 $Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,2,TRUE);      
		 $Test->drawGrid(4,TRUE,230,230,230,50);   
		  
		 // Draw the 0 line   
		 $Test->setFontProperties("xihei.ttf",6);   
		 $Test->drawTreshold(0,143,55,72,TRUE,TRUE);   
		  
		 // Draw the bar graph   
		 $Test->drawBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(),TRUE);   
		  
		 // Finish the graph   
		 $Test->setFontProperties("xihei.ttf",8);   
		 $Test->drawLegend(596,50,$DataSet->GetDataDescription(),255,255,255);   
		 $Test->setFontProperties("xihei.ttf",10);   
		 $Test->drawTitle(200,22,"Reports by Day in Jue.",50,50,50,585);  
		
		 $Test->Render("Naked1.png"); 
		 
		 //=====================================================================================
		 // Dataset definition    
		 $DataSet2 = new pData;   
		 $DataSet2->AddPoint(array(38,22,606),"Serie1");   
		 $DataSet2->AddPoint(array("Male","Female","Unkown"),"Serie2");   
		 $DataSet2->AddAllSeries();   
		 $DataSet2->SetAbsciseLabelSerie("Serie2");   
		  
		 // Initialise the graph   
		 $Test2 = new pChart(300,200);   
		 $Test2->loadColorPalette("softtones.txt");   
		 $Test2->drawFilledRoundedRectangle(7,7,293,193,5,255,255,255);   
		 $Test2->drawRoundedRectangle(5,5,295,195,5,230,230,230);   
		  
		 // This will draw a shadow under the pie chart   
		 $Test2->drawFilledCircle(122,102,70,200,200,200);   
		  
		 // Draw the pie chart   
		 $Test2->setFontProperties("tahoma.ttf",8);   
		 $Test2->drawBasicPieGraph($DataSet2->GetData(),$DataSet2->GetDataDescription(),120,100,70,PIE_PERCENTAGE,255,255,218);   
		 $Test2->drawPieLegend(230,15,$DataSet2->GetData(),$DataSet2->GetDataDescription(),250,250,250);   
		  
		 $Test2->Render("Naked2.png");  
		//=====================================================================================
		// select province
		$db = Zend_Registry::get('db');
		$selectprovince = $db->select();
		$selectprovince->from('consumer', array("province", "count(*) as count"))
		->where("pest is null")
		->group("consumer.province")
		->order("count desc");
		$provinceArray = $db->fetchAll($selectprovince);
		$this->view->showprovince = '';
		$this->view->showprovincecount = '';

		$showprovince = array();
		$showprovincecount = array();
		foreach($provinceArray as $province){
			if($province['province'] == null || $province['province'] == ''){
				array_push($showprovince, 'Unkown');
			}else{
				array_push($showprovince, $province['province']);
			}			
			array_push($showprovincecount, $province['count']);
		}

 		// Dataset definition    
		 $DataSet3 = new pData;   
		 $DataSet3->AddPoint(array_slice($showprovincecount,0,20) ,"Serie1");    
		 $DataSet3->AddPoint(array_slice($showprovince,0,20) ,"Serie2");     
		 $DataSet3->AddSerie("Serie1");  
		 $DataSet3->SetAbsciseLabelSerie("Serie2");   
		 $DataSet3->SetSerieName("Spark Count","Serie1"); 
		  
		 $DataSet3->SetYAxisName('Spark Count');
		 $DataSet3->SetXAxisName('Province'); 
			Zend_Debug::dump($DataSet3->GetDataDescription());
		  
		 // Initialise the graph   
		 $Test3 = new pChart(900,250);   
		 $Test3->setFontProperties("xihei.ttf",8);   
		 $Test3->setGraphArea(50,30,780,200);   
		 $Test3->drawFilledRoundedRectangle(7,7,793,248,5,255,255,255);   
		 $Test3->drawRoundedRectangle(5,5,795,249,5,230,230,230);   
		 $Test3->drawGraphArea(255,255,255,TRUE);   
		 $Test3->drawScale($DataSet3->GetData(),$DataSet3->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,2,TRUE);      
		 $Test3->drawGrid(4,TRUE,230,230,230,50);   
		  
		 // Draw the 0 line   
		 $Test3->setFontProperties("xihei.ttf",6);   
		 $Test3->drawTreshold(0,143,55,72,TRUE,TRUE);   
		  
		 // Draw the bar graph   
		 $Test3->drawBarGraph($DataSet3->GetData(),$DataSet3->GetDataDescription(),TRUE);   
		  
		 // Finish the graph   
		 $Test3->setFontProperties("xihei.ttf",8);   
		 $Test3->drawLegend(596,50,$DataSet3->GetDataDescription(),255,255,255);   
		 $Test3->setFontProperties("xihei.ttf",10);   
		 $Test3->drawTitle(200,22,"Reports by Day in Jue.",50,50,50,585);  
		
		 $Test3->Render("Naked3.png");  
		 
	}
	public function testdemoAction(){
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('report', array("date_format(create_date,'%Y.%m.%d') as date", "count(*) as count"))
		->where('campaign_id = 2')
		->group("date_format(create_date,'%Y-%m-%d')");
		$reportbydateArray = $db->fetchAll($select);
		return $reportbydateArray;
	}
	
	public function testcptableAction(){
		
	}
	public function testsendsmsAction()
	{
		
	}
	
}