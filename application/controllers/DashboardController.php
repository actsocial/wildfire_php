<?php
include_once 'Indicate2Connect.php';
include_once 'pChart/pData.php';
include_once 'pChart/pChart.php';

class DashboardController extends MyController
{
    protected $_rowsPerPage = 5;
    protected $_curPage = 1;
    
	function clientindexAction()
    {
    	$this->_helper->layout->setLayout("layout_client");
    	$this->view->title = "Dashboard For Campaigns";
        $this->view->activeTab = "List Campaigns";
        $campaign = new Campaign();
        $order = "expire_date desc";
        $this->view->campaigns = $campaign->fetchAll(null, $order, null, null);
    }
    
    function clientstatAction() {
    	if(!$this->clientdashboardfilter()){
    		return;
    	}
    	$this->view->activeTab = 'clientstat';
        // process data for chart
        include 'open-flash-chart.php';
    	$request = $this->getRequest();
		$this->view->campaign_id = $request->getParam('id');
		
        $campaignModel = new Campaign();
        $campaign = $campaignModel->fetchRow('id = '.$this->view->campaign_id);
        $this->_helper->layout->setLayout($this->getCampaignTemplate($campaign->id));
        //if session not exist, get data from webservice
        $reportMap = array();
        $array_data = $this->getReportCountbyCampaign($campaign, 0, $reportMap);
		
		$sum = 0;
		foreach($array_data as $date){
			$sum += $date;
		}
		$i = 0;
		$maxY = 0;
    	foreach($array_data as $date){
			$array_data[$i] = round($date*100/$sum, 1);
			if($array_data[$i] > $maxY){
				$maxY = $array_data[$i];
			}
			$i++;
		}
		// create chart
		$array_create_date = array('0','1','2','3','4','5','6','7');
		$title = new title( "Kraft Spraks Comments" );
		$title->set_style( '{font-size: 14px; color: #FFFFFF; }' );
		$max_y = floor($maxY/10)*10+10;
		$y = new y_axis();
		$y->set_range( 0, $max_y, 10 );
		
		$x = new x_axis();
		$x_labels = new x_axis_labels();
		$x_labels->set_labels($array_create_date);
		$x_labels->set_size(11);
		$x->set_labels( $x_labels );
		$x->set_range( 0, 7, 1 );
		//There is a bug on the tooltip of bar: can not show #x_label#. So use bar_stack instead of bar here.
		$bar = new bar_filled();
		$array_bar_data[0] = new bar_value($array_data[0]);
		$array_bar_data[0]->set_colour( '#606060' );
		$array_bar_data[1] = new bar_value($array_data[1]);
		$array_bar_data[1]->set_colour( '#BE3304' );
		$array_bar_data[2] = new bar_value($array_data[2]);
		$array_bar_data[2]->set_colour( '#F2B538' );
		$array_bar_data[3] = new bar_value($array_data[3]);
		$array_bar_data[3]->set_colour( '#EE7904' );
		$array_bar_data[4] = new bar_value($array_data[4]);
		$array_bar_data[4]->set_colour( '#D4FD32' );
		$array_bar_data[5] = new bar_value($array_data[5]);
		$array_bar_data[5]->set_colour( '#B4EB35' );
		$array_bar_data[6] = new bar_value($array_data[6]);
		$array_bar_data[6]->set_colour( '#B1D764' );
		$array_bar_data[7] = new bar_value($array_data[7]);
		$array_bar_data[7]->set_colour( '#A1C463' );
		$bar->set_values($array_bar_data);
//		$bar->set_tooltip('#x_label#: #val#');
//		$bar = new bar_stack();
//		$bar->set_colours(array( '#E2D66A', '#A0C361' ));
//		foreach ($array_data as $date):
//		$bar->append_stack(array((int)$date));
//		endforeach;
		$bar->set_tooltip('#val#%');

		$x_legend = new x_legend( 'Positive' );
		$x_legend->set_style( '{font-size: 30px; color: #FFFFFF; }' );
		$y_legend = new y_legend( $this->view->translate('(%)') );
		$y_legend->set_style( '{font-size: 30px; color: #FFFFFF;}' );
		
		$tags = new ofc_tags();
		$tags->font("Verdana", 14)
		    ->align_x_right();
		$tag_y_value = -($max_y*0.3);   
	    $t = new ofc_tag(6.6, $tag_y_value);
		$t->text($this->view->translate('Client_Report Positive'))
		 ->colour("#177A16");
		$tags->append_tag($t);
		
		$t2 = new ofc_tag(0.5, $tag_y_value);
		$t2->text($this->view->translate('Client_Report Negative'))
		->colour("#D88569");
		$tags->append_tag($t2);
		
		$t3 = new ofc_tag(-1, $max_y);
		$t3->text($this->view->translate('(%)'))
		->colour("#000000");
		$tags->append_tag($t3);
	
		$t4 = new ofc_tag(-0.7, $tag_y_value);
		$t4->text($this->view->translate('Client_Report No opinion'))
		->colour("#5D5D5D");
		$tags->append_tag($t4);
		
		$this->view->chart3 = new open_flash_chart();
		$this->view->chart3->set_title( $title );
		$this->view->chart3->add_element( $bar );
		$this->view->chart3->set_bg_colour( '#FFFFFF' );
		$this->view->chart3->set_x_axis( $x );
		$this->view->chart3->set_y_axis( $y );
		$this->view->chart3->set_x_legend( $x_legend );
		$this->view->chart3->set_y_legend( $y_legend );
		$this->view->chart3->add_element( $tags );
		
    }
    function clientcloudtagAction(){
    	if(!$this->clientdashboardfilter()){
    		return;
    	}

    	$this->view->activeTab = 'clientcloudtag';

    	$campaign_id = $this->getRequest()->getParam('id');
        $this->_helper->layout->setLayout($this->getCampaignTemplate($campaign_id));
        
    	$this->view->skin = $campaign_id;
    	$this->view->campaign_id = $campaign_id;
    	$clientCampaignListNamespace = new Zend_Session_Namespace('ClientCampaignList');
    }
    
    function getCampaignTemplate($campaign_id) {
      if(file_exists(APPLICATION_PATH. "/layouts/campaign_template_". $campaign_id .".phtml")) {
        return ("/layouts/campaign_template_". $campaign_id .".phtml");
      } else {
        return "layout_client";
      }
    }
    
	function clientdashboardfilter()
	{
     	$clientCampaignListNamespace = new Zend_Session_Namespace('ClientCampaignList');
		if($clientCampaignListNamespace->list == null){
			$db = Zend_Registry::get('db');
			$clientCampaginSelect = $db->select();
			$clientCampaginSelect->from('client_campaign', 'campaign_id')
			->join('campaign', 'client_campaign.campaign_id = campaign.id', array('name'))
			->where('client_campaign.client_id = ?', $this->_currentClient->id)
			->order('campaign.id desc');
			$clientCampaign = $db->fetchAll($clientCampaginSelect);
			$campaignlist = array();
			foreach($clientCampaign as $temp){
				$campaignlist[$temp['campaign_id']] = $temp['name'];
			}
			
			$clientCampaignListNamespace->list = $campaignlist;
		}
		$request = $this->getRequest();
		$campaign_id = $request->getParam('id');
		$this->view->clientCampaignList = $clientCampaignListNamespace->list;
//		Zend_Debug::dump($clientCampaignListNamespace->list);
		$this->view->baselink = explode('/id/', 'http://'.$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"]);
		if(!array_key_exists((int)$campaign_id, $clientCampaignListNamespace->list)){
			echo "I'm sorry, you can not access this campaign!";
			return false;
		}
		return true;
    }
	private function getReportCountbyCampaign($campaign, $limit, &$reportMap)
	{
		//Zend_Debug::dump("campaign start:".(date("Y-m-d H:i:s")).".".floor(microtime()*1000));
		if(!isset($campaign) || $campaign == null) {
			return null;
		}
		// fetch the Zend_Cache object
		$cache = Zend_Registry::get('cache');
		// attempt to load the reaction from cache
		$cacheKey = 'ws_report_' . $campaign->id;
		$cacheArray = $cache->load($cacheKey);
		if($cacheArray) {
			$reportMap = $cacheArray['reports'];
			return $cacheArray['reactions'];
		}
		
		// init reaction count array
		$reactionCounts = array(0, 0, 0, 0, 0, 0, 0, 0);
		
		$db = Zend_Registry::get('db');
		$surveyIdList = array($campaign->i2_survey_id, $campaign->i2_survey_id_en);
		$totalAvailableReport = 0;
		
		$ReportMapping = new DashboardMapping();
		$mappings = $ReportMapping->findBy(array('survey_id' => $campaign->i2_survey_id));
		$mappingList = array();
		//Map<ContextIndex_QuestionIndex, mark> e.g. Map<'1_1', 'SCORE'>
		$indexMarkMap = array();
		$i = 0;
		foreach($mappings as $row) {
			array_push($mappingList, array('SurveyId' => $row->survey_id, 'ContextIndex' => $row->context_index, 'QuestionIndex' => $row->question_index));
			$key = $row->context_index . "_" . $row->question_index;
			$indexMarkMap[$key] = $row->mark;
		}
		// get data from webservice
		$accessCodeList = array();
		if($campaign->id > 5) {
			$select = $db->select();
			$select->from('report', array('accesscode'));
			$select->join('report_tag', 'report_tag.report_id=report.id', null);
			$select->where('report_tag.tag_id = 25');
			$select->where('report.campaign_id = ?', $campaign->id);
			$select->order('report.id');
			$rs = $db->fetchAll($select);
			foreach($rs as $row) {
				array_push($accessCodeList, $row["accesscode"]);
			}
		} else {
			$select = $db->select();
			$select->from('report', array('accesscode'));
			$select->where('report.campaign_id = ?', $campaign->id);
			$select->where('report.state = ?', "APPROVED");
			$select->order('report.id');
			$rs = $db->fetchAll($select);
			foreach($rs as $row) {
				array_push($accessCodeList, $row["accesscode"]);
			}
		}
		$countsArray = array(0, 0, 0, 0, 0, 0, 0, 0);
		$accessCodeArray = $this->getDataFromWebservice($surveyIdList, $accessCodeList, $mappingList, $limit, $indexMarkMap, $countsArray, $reportMap);
		//save total
		$totalAvailableReport += count($accessCodeArray);
		for($i = 0; $i <= count($countsArray); $i++) {
			$reactionCounts[$i] += $countsArray[$i];
		}
		//if reaction chart skip consumer info
		//3. get consumer info
		$accessCodeStr = '';
		if(count($accessCodeArray) > 0) {
			$accessCodeStr = implode("','", $accessCodeArray);
		}
		$select = $db->select();
		$select->from('consumer', array('id as consumer_id', 'recipients_name as consumer_name', 'email', 'phone', 'province', 'city', 'address1', 'gender'));
		$select->join('report', 'consumer.id=report.consumer_id', array('id as report_id', 'create_date as report_time', 'source', 'accesscode'));
		$select->where("report.accesscode in ('" . $accessCodeStr . "')");
		$select->where('report.state = ?', "APPROVED");
		$results = $db->fetchAll($select);
		foreach($results as $row) {
			$array = &$reportMap[$row['accesscode']];
			$array['REPORT_ID'] = $row['report_id'];
			$array['CONSUMER_NAME'] = $row['consumer_name'];
			$array['REPORT_TIME'] = $row['report_time'];
			$array['REPORT_SOURCE'] = $row['source'];
		}
		//4.get tags for each report
		$select = $db->select();
		$select->from('report_tag', array('tag_id'));
		$select->join('report', 'report.id=report_tag.report_id', array('accesscode'));
		$select->where("report.accesscode in ('" . $accessCodeStr . "')");
		$select->where('report.state = ?', "APPROVED");
		$results = $db->fetchAll($select);
		
		foreach($results as $row) {
			$accessCode = $row["accesscode"];
			if(array_key_exists($accessCode, $reportMap)) {
				if(isset($reportMap[$accessCode]['TAG_IDS'])) {
					$reportMap[$accessCode]['TAG_IDS'] .= "," . $row["tag_id"];
				} else {
					$reportMap[$accessCode]['TAG_IDS'] = $row["tag_id"];
				}
			}
		}
		//Zend_Debug::dump($reportMap);
		//5. sort by time desc
//		function cmp($a, $b)
//		{
//			if($a["REPORT_TIME"] == $b["REPORT_TIME"]) {
//				return 0;
//			}
//			return ($a["REPORT_TIME"] > $b["REPORT_TIME"]) ? -1 : 1;
//		}
//		usort($reportMap, "cmp");
		
		//add no answer of reaction to no reaction
		foreach($reactionCounts as $num) {
			$totalAvailableReport -= $num;
		}
		if($totalAvailableReport > 0) {
			$reactionCounts[0] += $totalAvailableReport;
		}
		//save to cache
		$cache->save(array('reports' => $reportMap, 'reactions' => $reactionCounts), $cacheKey);
		//Zend_Debug::dump("campaign end:".(date("Y-m-d H:i:s")).".".floor(microtime()*1000));
		return $reactionCounts;
	}
    
    function clientdripAction()
    {
        $this->view->activeTab = 'clientdrip';
    	$request = $this->getRequest();
        $campaign_id = $request->getParam('id');
        $this->_helper->layout->setLayout($this->getCampaignTemplate($campaign_id));
        $campaignModel = new Campaign();
        $campaign = $campaignModel->fetchRow('id = '.$campaign_id);
        $reportMap = array();
        $this->getReportCountbyCampaign($campaign, 500, $reportMap);
        $this->view->reportMap = $reportMap;
    }
    
    private function getDataFromWebservice($surveyIdList, $accessCodeList, $conditions, $limit,
                $indexMarkMap, &$countsArray, &$reportMap) {
        $accessCodeArray = array();
        $indicate2Connect = new Indicate2_Connect();
        //Zend_Debug::dump("request start:".(date("Y-m-d H:i:s")).".".floor(microtime()*1000));
//        Zend_Debug::dump($surveyIdList);
//        Zend_Debug::dump($accessCodeList);
//        Zend_Debug::dump($conditions);
//        Zend_Debug::dump($limit);die;
        
        $response = $indicate2Connect->getAnswerSetForSurvey($surveyIdList, $accessCodeList, $conditions, $limit);
        //Zend_Debug::dump("request end:".(date("Y-m-d H:i:s")).".".floor(microtime()*1000));
//        Zend_Debug::dump($response);
        //Map<QuestionId, mark> e.g. Map<1693, 'SCORE'>
        $questionMap = array();
        //Map<OptionId, OptionText> e.g. Map<7134, "粥/稀饭">
        $optionMap = array();
        //Map<QuestionId, true or false>
        $IsSelectionQuestionMap = array();
        //Zend_Debug::dump("decode start:".(date("Y-m-d H:i:s")).".".floor(microtime()*1000));
        //1.get QuestionType
        if (is_array($response->QuestionType) && !empty($response->QuestionType)) {
            foreach ($response->QuestionType as $question) {
                $key = $question->ContextIndex."_".$question->QuestionIndex;
                $questionMap[$question->QuestionId] = $indexMarkMap[$key];
                //deal with SelectionQuestionOption
                if (isset($question->SelectionQuestionOptionType)) {
                    $this->addOption2Map($question->SelectionQuestionOptionType, $optionMap);
                    $IsSelectionQuestionMap[$question->QuestionId] = true;
                }
            }
        } else {
            $question = $response->QuestionType;
            $key = $question->ContextIndex."_".$question->QuestionIndex;
            $questionMap[$indexMarkMap[$key]] = $question->QuestionId;
            //deal with SelectionQuestionOption
            if (isset($question->SelectionQuestionOptionType)) {
                $this->addOption2Map($question->SelectionQuestionOptionType, $optionMap);
                $IsSelectionQuestionMap[$question->QuestionId] = true;
            }
        }
        //2.get AnswerSetType
        if (!isset($response->AnswerSetType)) {
        	return;
        }
        if (is_array($response->AnswerSetType) && !empty($response->AnswerSetType)) {
            foreach ($response->AnswerSetType as $answerSet) {
//                if (count($answerSet->AnswerType) < 3) {
//                    continue;
//                }
                //save current access code to return
                array_push($accessCodeArray, $answerSet->AccessCode);
                if (count($answerSet->AnswerType) == 3  // skip incomplete answerset
                    && array_key_exists($answerSet->AccessCode,$reportMap)) {
                    $reportMap[$answerSet->AccessCode] = array();
                }
                $this->addAnswerSet2Map($answerSet, $questionMap, $optionMap, $IsSelectionQuestionMap,
                        $countsArray, $reportMap[$answerSet->AccessCode]);
            }
        } else {
            $answerSet = $response->AnswerSetType;
            //save current access code to return
            array_push($accessCodeArray, $answerSet->AccessCode);
            $reportMap[$answerSet->AccessCode] = array();
            $this->addAnswerSet2Map($answerSet, $questionMap, $optionMap, $IsSelectionQuestionMap,
                    $countsArray, $reportMap[$answerSet->AccessCode]);
        }
        //Zend_Debug::dump("decode end:".(date("Y-m-d H:i:s")).".".floor(microtime()*1000));
        
        return $accessCodeArray;
    }
    
    private function addOption2Map($selectionQuestionOptionType, &$optionMap) {
        if (is_array($selectionQuestionOptionType) && !empty($selectionQuestionOptionType)) {
            foreach ($selectionQuestionOptionType as $option) {
                $optionMap[$option->OptionId] = $option->OptionText;
            }
        } else {
            $option = $selectionQuestionOptionType;
            $optionMap[$option->OptionId] = $option->OptionText;
        }
    }
    
    private function addAnswerSet2Map($answerSet, $questionMap, $optionMap, $IsSelectionQuestionMap,
            &$countsArray, &$array) {
        
        if (is_array($answerSet->AnswerType) && !empty($answerSet->AnswerType)) {
            foreach ($answerSet->AnswerType as $answer) {
                $text = $this->decodeAnswerText($answer->QuestionId,$answer->AnswerText,$IsSelectionQuestionMap,$optionMap);
                if ($questionMap[$answer->QuestionId] == 'SCORE') {
                    $array[$questionMap[$answer->QuestionId]] = $this->countScore($text,$countsArray);
                } else {
                    $array[$questionMap[$answer->QuestionId]] = $text;
                }
            }
        } else {
            $answer = $answerSet->AnswerType;
            $text = $this->decodeAnswerText($answer->QuestionId,$answer->AnswerText,$IsSelectionQuestionMap,$optionMap);
            if ($questionMap[$answer->QuestionId] == 'SCORE') {
                $array[$questionMap[$answer->QuestionId]] = $this->countScore($text,$countsArray);
            } else {
                $array[$questionMap[$answer->QuestionId]] = $text;
            }
        }
        
    }
    
    private function decodeAnswerText($questionId, $answerText, $IsSelectionQuestionMap, $optionMap) {
        if (array_key_exists($questionId, $IsSelectionQuestionMap)) { //selection question
            return $optionMap[base64_decode($answerText)];
        } else {
        	//print_r($answerText);
            $str = base64_decode($answerText);
         	$str = str_replace("\n", "", $str);
            $str = str_replace("\r", "", $str);
        	return $str;
        }
    }
    
    private function countScore($scoreText, &$countsArray) {
        $score = '';
        if(!(strpos($scoreText, '1') === false)) {
            $countsArray[1]++;
            $score = '1';
        } else if(!(strpos($scoreText, '2') === false)) {
            $countsArray[2]++;
            $score = '2';
        } else if(!(strpos($scoreText, '3') === false)) {
            $countsArray[3]++;
            $score = '3';
        } else if(!(strpos($scoreText, '4') === false)) {
            $countsArray[4]++;
            $score = '4';
        } else if(!(strpos($scoreText, '5') === false)) {
            $countsArray[5]++;
            $score = '5';
        } else if(!(strpos($scoreText, '6') === false)) {
            $countsArray[6]++;
            $score = '6';
        } else if(!(strpos($scoreText, '7') === false)) {
            $countsArray[7]++;
            $score = '7';
        } else {
            $countsArray[0]++;
            $score = '0';
        }
        return $score;
    }
	public function clientimpressionAction(){
		ini_set('display_errors', 1);
		$frontController = Zend_Controller_Front::getInstance();
		$frontController->throwExceptions(true);
		
		// filter!
		if(!$this->clientdashboardfilter()){
    		return;
    	}
    	
		$this->view->activeTab = 'clientimpression';
		include 'open-flash-chart.php';
		$request = $this->getRequest();
		if ($request->isPost()) {
			$formData = $request->getPost();
			$campaign_id  = $formData['campaign_id'];
		}else{
			$campaign_id = $request->getParam('id');
		}
		$this->_helper->layout->setLayout($this->getCampaignTemplate($campaign_id));
		$this->view->campaign_id = $campaign_id;
		// get date from db
        $db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('report', array('left(create_date,10) as date', 'accesscode'))
		->where('report.campaign_id = ?',$campaign_id )
		->where('report.state = "APPROVED"')
		->order('date');
		$results = $db->fetchAll($select);
		$accesscodeDbMatchArray = array();
		foreach($results as $result):
			$accesscodeDbMatchArray[$result['accesscode']] = $result['date'];
		endforeach;
		$accesscodeArray   =   array_keys($accesscodeDbMatchArray);
		$campaignModel = new Campaign();
		
		$campaign = $campaignModel->fetchRow('id = ' . $campaign_id);
	
		// get impression value from ws
		$db = Zend_Registry::get('db');
		$select = $db->select();
		$select->from('dashboard_mapping');
		$select->where('survey_id = ?',$campaign->i2_survey_id);
		$select->where('mark="FRIENDS"');
		$mappings = $db->fetchRow($select);
		$indicate2Connect = new Indicate2_Connect();
        $response = $indicate2Connect->getAnswerSetForSurvey(array($campaign->i2_survey_id,$campaign->i2_survey_id_en), null, 
        array(array('ContextIndex' => $mappings['context_index'],'QuestionIndex' => $mappings['question_index'])), 0);
//        Zend_Debug::dump($mappings);die;
        $optionArray = array();
		if(isset($response->QuestionType) && is_array($response->QuestionType)){
        	foreach($response->QuestionType as $questionType)
        	foreach($questionType->SelectionQuestionOptionType as $optionObject):
        		//get min value, like '11-15' = 11 
        		$optionArray[$optionObject->OptionId] = (int)$optionObject->OptionText;
        	endforeach;
        }else{
	        if(isset($response->QuestionType->SelectionQuestionOptionType)){
	        	foreach($response->QuestionType->SelectionQuestionOptionType as $optionObject):
	        		//get min value, like '11-15' = 11 
	        		if ($optionObject->OptionText=='>=10') {
	        			$optionArray[$optionObject->OptionId] = 10;
	        		}
	        		$optionArray[$optionObject->OptionId] = (int)$optionObject->OptionText;
	        	endforeach;
	        }
        }
        $answerArray = array();
        $i =0;
        if(isset($response->AnswerSetType)){
        	foreach($response->AnswerSetType as $answerObject):
        		$answerArray[$i++] = array($answerObject->AccessCode, iconv("UTF-8","gb18030",preg_replace('/[\n\r\t]/', ' ',base64_decode($answerObject->AnswerType->AnswerText))));
        	endforeach;
        }
        // create x axis date value (endDate = expireDate/now + 10 days)
        $this->view->startDate = $startDate = date("Y-m-d",strtotime($campaign->create_date));
		if(strtotime($campaign->expire_date)>strtotime(date("Y-m-d"))){
			$this->view->xmax = $endDate = date("Y-m-d",(strtotime("+10 days",strtotime(date("Y-m-d")))));
		}else{
			$this->view->xmax = $endDate = date("Y-m-d",(strtotime("+10 days",strtotime($campaign->expire_date))));
		}
		if ($request->isPost()) {
			$formData = $request->getPost();
			$this->view->xmax = $endDate = $formData['x_max'];
		}
		$resultArray = array();
		$xDateArrayLength = 0;
		while(1){
			$resultArray[$startDate] = 0;
			$startDate = date("Y-m-d",(strtotime("+1 days",strtotime($startDate))));
			$xDateArrayLength ++;
			if($startDate == $endDate){
				$resultArray[$startDate] = 0;
				$xDateArrayLength ++;
				break;
			}
		}
	    //var_dump($resultArray);die;
		// set sparks initial impressions for each campaign, it should be added if a new campaign is lanuched!
		switch ($campaign_id){
			case '1': 
				$staticsparks = array(50,110);
				break;
			case '2': 
				$staticsparks = array(50,150,250,400,450);
				break;
			case '3': 
				$staticsparks = array(100,350,750,1200,1500);
				break;
			case '4': 
				$staticsparks = array(50,200,250);
				break;
			case '5': 
				$staticsparks = array(50,150);
				break;	
			case '6': 
				$staticsparks = array(50,150,350,500,900,1700,2300,2500);
				break;
			case '7': 
				$staticsparks = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,50,150,350,500,900,1038);
				break;
			case '8': 
				$staticsparks = array(0,0,0,0,0,0,0,0,0,0,0,0,0,150,300,450,600,750,900,1000);
				break;
			case '9': 
				$staticsparks = array(0,0,0,150,300,450,600,750,900,1000);
				break;
			case '10': 
				$staticsparks = array(0,0,0,0,0,0,0,0,150,300,450,600,750,900,1000);
				break;
			case '13': 
				$staticsparks = array(0,0,0,0,0,0,50,100,150,300,450,600,750,900,1200);
				break;
			default:
				break;
		}
		for($temp = count($staticsparks); $temp<$xDateArrayLength; $temp++){
			$staticsparks[$temp] = $staticsparks[$temp-1];
		}
		
		// set everyday impression by using ws data
        foreach($answerArray as $answer){
        	if(!isset($accesscodeDbMatchArray[$answer[0]])){
        		continue;
        	}
        	if( date("Y-m-d",strtotime($accesscodeDbMatchArray[$answer[0]])) > date("Y-m-d",strtotime($this->view->xmax)) ){
        		continue;
        	}
        	if(!array_key_exists($accesscodeDbMatchArray[$answer[0]], $resultArray)){
        		$resultArray[$accesscodeDbMatchArray[$answer[0]]] = $optionArray[$answer[1]];
        	}else{
        		$resultArray[$accesscodeDbMatchArray[$answer[0]]] += $optionArray[$answer[1]];
        	}
        }
        //Zend_Debug::dump($optionArray);
  		//Zend_Debug::dump($resultArray);
        //Zend_Debug::dump($answerArray);
        //Zend_Debug::dump($accesscodeDbMatchArray);
        // set line value
        $sparks = 0;
        $data_1 = array();
        $data_2min = array();
        $data_2max = array();
        $data_3min = array();
        $data_3max = array();
        $i = 0;
        $data_1[0] = $data_2max[0] = $data_2min[0] = $data_3min[0] = $data_3max[0] = $accumulate = 0; 
        $temp = 0;
        foreach($resultArray as $result){
        	if($result != 0){
        		$xTodayLength = $temp;
        	}
        	$temp++;
        }
        foreach($resultArray as $result){
        	if($i == 0){
        		$i ++;
        		continue;
        	}
        	$accumulate = $result+$accumulate;
        	$data_1[$i] = $accumulate + $staticsparks[$i];
        	$data_2min[$i] = floor($accumulate*2.5+$staticsparks[$i]);
        	$data_2max[$i] = $accumulate*5+$staticsparks[$i];
        	$data_2avg[$i] = $accumulate*3.75;
        	$data_3min[$i] = floor($data_2avg[$i]*3)+$data_2min[$i];
        	$data_3max[$i] = floor($data_2avg[$i]*4)+$data_2max[$i];
        	$max = $data_3max[$i];
	        if($i == $xTodayLength){
	        		break;
	        	}
        	$i ++;
        }
       	// set max y axis value
		$dateArray = array_keys($resultArray);
		$y = new y_axis();
		if ($request->isPost()) {
			$formData = $request->getPost();
			$max = $formData['y_max'];
		}else{
			if($max < 10000){
				$max = (ceil($max/100))*100;
			}else{
				$max = (ceil($max/1000))*1000;
			}
		}
		$y->set_range( 0, $max, $max/10);
		$this->view->ymax = $max;
		// draw lines
		$x = new x_axis();
		$x_labels = new x_axis_labels();
		$x_labels->set_labels($dateArray);
		$x_labels->set_steps( floor($xDateArrayLength/42)+1 );
		$x_labels->rotate(40);
		$x->set_labels( $x_labels );

		$line_1_default_dot = new dot();
		$line_1_default_dot->colour('#007DCD')->tooltip('#x_label#: #val#');
		$line_1 = new line();
		$line_1->set_default_dot_style($line_1_default_dot);
		$line_1->set_values( $data_1 );
		$line_1->set_colour("#007DCD");
		$line_1->set_width( 1 );

		$line_2min_default_dot = new dot();
		$line_2min_default_dot->colour('#81C909')->tooltip('#x_label#: #val#');
		$line_2min = new line();
		$line_2min->set_default_dot_style($line_2min_default_dot);
		$line_2min->set_values( $data_2min );
		$line_2min->set_colour("#81C909");
		$line_2min->set_width( 1 );
		
		$line_2_default_dot = new dot();
		$line_2_default_dot->colour('#81C909')->tooltip('#x_label#: #val#');
		$line_2 = new line();
		$line_2->set_default_dot_style($line_2_default_dot);
		$line_2->set_values( $data_2max );
		$line_2->set_colour("#81C909");
		$line_2->set_width( 1 );
		
		$line_3_default_dot = new dot();
		$line_3_default_dot->colour('#FF0000')->tooltip('#x_label#: #val#');
		$line_3 = new line();
		$line_3->set_default_dot_style($line_3_default_dot);
		$line_3->set_values( $data_3min );
		$line_3->set_colour("#FF0000");
		$line_3->set_width( 1 );
		
		$line_3max_default_dot = new dot();
		$line_3max_default_dot->colour('#FF0000')->tooltip('#x_label#: #val#');
		$line_3max = new line();
		$line_3max->set_default_dot_style($line_3max_default_dot);
		$line_3max->set_values( $data_3max );
		$line_3max->set_colour("#FF0000");
		$line_3max->set_width( 1 );
		
		//tags
		$tags = new ofc_tags();
		$tags->font("Verdana", 10)
		    ->colour("#2F2F2F")
		    ->align_x_right();
		$this->view->chart = new open_flash_chart();
		// create event
		$campaignEventModel = new CampaignEvent();
		$campaignEvents = $campaignEventModel->fetchAll('campaign_id = ' . $campaign_id, 'event_date');
		$eventTotal = count($campaignEvents);
		$eventTemp = 0;
		foreach($campaignEvents as $campaignEvent){
			$eventDate = floor((strtotime($campaignEvent->event_date)- strtotime($campaign->create_date))/86400);
			$eventDescription = $campaignEvent->event_name;
			// event line
			$eventline = new scatter_line( '#C5BE97', 1 );
			$def = new hollow_dot();
			$def->size(0)->halo_size(0);
			$eventline->set_default_dot_style( $def );
			$v = array(new scatter_value( $eventDate, 0 ),new scatter_value( $eventDate, $this->view->ymax ));
			$eventline->set_values( $v );
			$this->view->chart->add_element( $eventline );
			// event description
			$tagAndArrow_Yvalue = 1-($eventTotal-$eventTemp++)/10;
			if($tagAndArrow_Yvalue == 0){
				$tagAndArrow_Yvalue = 0.1;
			}
			$tag_xvalue = $eventDate+2;
			$t = new ofc_tag($tag_xvalue, $this->view->ymax*$tagAndArrow_Yvalue);
			$t->text($eventDescription)
			->style(false, false, false, 1.0)->padding(0, 0);
			$tags->append_tag($t);
			// event arrow
			$arrowStart_x = $tag_xvalue;
			$arrowStart_y = $this->view->ymax*$tagAndArrow_Yvalue;
			$arrowEnd_x = $tag_xvalue-1.5;
			$arrowEnd_y = $this->view->ymax*$tagAndArrow_Yvalue;
			$arrowColor = '#000000';
			$arrowBarbLength = 7;
			$a = new ofc_arrow($arrowStart_x, $arrowStart_y, $arrowEnd_x, $arrowEnd_y, $arrowColor, $arrowBarbLength);
			$this->view->chart->add_element( $a );
		}
		$this->view->chart->add_element( $line_1 );
		$this->view->chart->add_element( $line_2min );
		$this->view->chart->add_element( $line_2 );
		$this->view->chart->add_element( $line_3 );
		$this->view->chart->add_element( $line_3max );
		$this->view->chart->add_element( $tags );
		$this->view->chart->set_y_axis( $y );
		$this->view->chart->set_x_axis( $x );
		$this->view->chart->set_bg_colour( '#FFFFFF' );
	}
	
    private function drawBarChart($countArray, $nameArray, $title) {

        // Dataset definition
        $DataSet = new pData;
        $DataSet->AddPoint($countArray,"Serie1");
        $DataSet->AddPoint($nameArray,"Serie2");
        $DataSet->AddSerie("Serie1");
        $DataSet->SetAbsciseLabelSerie("Serie2");
        //data legend
        $DataSet->SetSerieName("Reports","Serie1");
        $DataSet->SetYAxisName('Report Count');
        //$DataSet->SetXAxisName('Score');

        // Initialise the graph
        $Test = new pChart(700,250);
        $font = "fonts/tahoma.ttf";
        $Test->loadColorPalette("fonts/pchart_palette.txt");
        //diagram size and style
        $Test->setFontProperties($font,8);
        $offset = 25;
        $Test->setGraphArea(50+$offset,30,620+$offset,200);
        $Test->drawFilledRoundedRectangle(7,7,693,233,5,240,240,240);
        $Test->drawRoundedRectangle(5,5,695,235,5,230,230,230);
        $Test->drawGraphArea(255,255,255,TRUE);
        $Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,2,TRUE,1);
        $Test->drawGrid(4,TRUE,230,230,230,50);
        
//        // Draw the 0 line
//        $Test->setFontProperties($font,6);
//        $Test->drawTreshold(0,143,55,72,TRUE,TRUE);

        // Draw the bar graph
        $Test->drawBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(),TRUE,80);


        // Finish the graph
        $Test->setFontProperties($font,10);
        
        //$Test->drawLegend(610+$offset-5,140,$DataSet->GetDataDescription(),255,255,255);
        $Test->drawAxisLegend(10,208,"Negative",255,255,255,-1,-1,-1,191,50,3);
        $Test->drawAxisLegend(610+$offset-5,210,"Positive",255,255,255,-1,-1,-1,139,203,70);
        $Test->setFontProperties($font,10);
        $Test->drawTitle(60+$offset,20,$title,50,50,50,585);

        $imageFile = "images/drip_bar_chart.png";
        $Test->Render($imageFile);
    }
    
    function clientsavecommentAction() {
    	$this->_helper->layout->disableLayout();
        $result = '';
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            $reportId = $formData['report_id'];
            $subject = $formData['subject'];
            $content = $formData['content'];
            //Zend_Debug::dump($formData);
            if ($content != '') {
            	//get spark_id by report
            	$reportModel = new Report();
            	$consumer_id = $reportModel->getConsumerIdByReportId($reportId);
	            //save message
	            $currentTime = date("Y-m-d H:i:s");
	            $clientMessageModel = new ClientMessage();
	            $newMessage = $clientMessageModel->createRecord('Client',$this->_currentClient->id,'Spark',
	                $consumer_id,$subject,'Report',$content,$this->_currentClient->id,$currentTime,$reportId);
	            if ($newMessage > 0) {
	                $result = "Success";
	            }
	            $result->flag = "Success";
            }
        }
        $this->_helper->json($result);
    }
    
    function testAction() {
        $this->_helper->layout->setLayout("layout_client");
        $this->view->activeTab = 'clientdrip';
        
        $campaignModel = new Campaign();
        $campaign = $campaignModel->fetchRow('id = 6');
        $reportMap = array();
        $this->getReportCountbyCampaign($campaign, 500, $reportMap);
        $this->view->reportMap = $reportMap;
//        $reportArray = array();
//        foreach($reportMap as $row) {
//            array_push($reportArray, array($row['SCORE'],$row['FRIENDS'],$row['REPORT_TIME'],$row['COMMENTS']));
//        }
//        $this->view->json = json_encode($reportArray);
//        Zend_Debug::dump(json_encode($reportArray));
    }
    
    function getreplyAction() {
    	$this->_helper->layout->disableLayout();
    	$result = null;
    	if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            $reportId = $formData['report_id'];
            $replyModel = new Reply();
            $reply = $replyModel->fetchRow('report_id = '.$reportId);
	        if (isset($reply)) {
	            $result->content = $reply->content;
	        }
	        $this->_helper->json($result);
    	}
    }
    
    function getconsumerAction() {
        $this->_helper->layout->disableLayout();
        $consumer = null;
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            $reportId = $formData['report_id'];
            $db = Zend_Registry::get('db');
            $select = $db->select();
            $select->from('consumer', array('id','recipients_name','province','city','address1','gender'));
            $select->join('report', 'consumer.id=report.consumer_id', array('id as report_id'));
            $select->where('report.id = ?', $reportId);
            $rs = $db->fetchRow($select);
            if (isset($rs)) {
                $consumer->id = $rs["id"];
            	$consumer->name = $rs["recipients_name"];
                $consumer->province = $rs["province"];
                $consumer->city = $rs["city"];
                $consumer->address = $rs["address1"];
                $consumer->gender = $rs["gender"];
            }
            $this->_helper->json($consumer);
        }
    }
    
    function clientreportAction() {

    	if(!$this->clientdashboardfilter()){
    		return;
    	}
        $this->view->activeTab = 'clientreport';
        
        $request = $this->getRequest();
        if ($request->isPost()) {
			$formData = $request->getPost();
        	$this->view->searchText = $formData['searchText'];
        	$this->view->campaign_id = $campaign_id = $formData['campaign_id'];
        }else{
        	$this->view->campaign_id = $campaign_id = $request->getParam('id');
        	$this->view->searchText = $request->getParam('searchText');
        }
        $this->_helper->layout->setLayout($this->getCampaignTemplate($campaign_id));
        
        //get tags
        $tagMap = array();
        $reporttabModel = new ReportTab();
        $taggingModel = new Tagging();
        $tabList = $reporttabModel->findBy(array('campaign_id' => $campaign_id));
        foreach($tabList as $tab) {
            $tagIds = $taggingModel->getTagIds(array('report_tab_id' => $tab->id));
	        $tagMap[$tab->name] = implode("|", $tagIds);
        }
        $this->view->tagMap = $tagMap;
        
        $campaignModel = new Campaign();
        $campaign = $campaignModel->fetchRow('id = '.$campaign_id);
        $reportMap = array();
        $this->getReportCountbyCampaign($campaign, 0, $reportMap);
        $this->view->reportMap = $reportMap;
        
    }
    
    function getsourceAction() {
        $this->_helper->layout->disableLayout();
        $campaign_id = $this->_request->getParam('id');
        $campaignModel = new Campaign();
        $campaign = $campaignModel->fetchRow('id = '.$campaign_id);
        $reportMap = array();
        $this->getReportCountbyCampaign($campaign, 0, $reportMap);
        $result = '{ "aaData": [';
        foreach ($reportMap as $row) {
        	$result .= '[ "'.($row['SCORE']==''?'n/a':$row['SCORE']);
        	$result .= '", "'.($row['FRIENDS']==''?'n/a':$row['FRIENDS']);
        	$result .= '", "'.$row['REPORT_TIME'];
        	$result .= '", "'.$row['REPORT_SOURCE'];
        	$result .= '", "'.$row['COMMENTS'];
        	$result .= '", "'.$row['TAG_IDS'];
        	$result .= '" ],';
        }
        $result .= '] }';
        $this->_helper->json($result);
    }
    
    function clientmessageAction() {
        $this->view->activeTab = 'clientmessage';
        
        if(!$this->clientdashboardfilter()) {
            return;
        }
        $campaign_id = $this->_request->getParam('id');
        $this->_helper->layout->setLayout($this->getCampaignTemplate($campaign_id));
        // get current page(default page = 1)
        if($this->_request->getParam('page')) {
            $this->_curPage = $this->_request->getParam('page');
        }
        
        $clientMessageModel = new ClientMessage();
        $where = "client_message.to=".$this->_currentClient->id." or client_message.from=".$this->_currentClient->id;
        $order = "client_message.id desc";
        $messageList = $clientMessageModel->fetchAll($where, $order);
        $msgMap = array();
        foreach ($messageList as $message) {
            if ($message->parent_id != null) { //reply
            	$key = $message->parent_id;
                if (!array_key_exists($key, $msgMap)) {
                	$msgMap[$key] = array();
                }
                if(!isset($msgMap[$key]['LASTTIME'])) { //save last time
                	$msgMap[$key]['LASTTIME'] = $message->create_date;
                }
                if (!isset($msgMap[$key]['REPLY'])) {
                	$msgMap[$key]['REPLY'] = array();
                }
                array_push($msgMap[$key]['REPLY'], $message);
	            
            } else { //message
            	$key = $message->id;
            	if (!array_key_exists($key, $msgMap)) {
            		$msgMap[$key] = array();
            	}
                $msgMap[$key]['MESSAGE'] = $message;
            }
            //get from name depending on from_type
            if($message->from_type=="Wildfire") {
                $adminModel = new Admin();
                $admin = $adminModel->fetchRow('id = '.$message->from);
                $msgMap[$key]['FROM_NAME'] = $admin->name;
            } else if($message->from_type=="Spark") {
                $consumerModel = new Consumer();
                $consumer = $consumerModel->fetchRow('id = '.$message->from);
                $msgMap[$key]['FROM_NAME'] = $consumer->recipients_name;
            }
        }
        $this->view->msgMap = $msgMap;
        //Zend_Debug::dump($msgMap);
        //paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array(array_values($msgMap)));
        $paginator->setCurrentPageNumber($this->_curPage)
        ->setItemCountPerPage($this->_rowsPerPage); 
        $this->view->paginator = $paginator;
        
        //update client message count in session.
        $clientMessageNamespace = new Zend_Session_Namespace('ClientMessage');
        $db = Zend_Registry::get('db');
        $messageCount = $db->fetchOne("SELECT count(*) FROM client_message cm WHERE cm.to_type='Client' and cm.to=:clientId and state='NEW'", array('clientId' => $this->_currentClient->id ));
        if($messageCount > 0) {
        	$attrName = "count_".$this->_currentClient->id;
            $clientMessageNamespace->$attrName = $messageCount;
            $this->view->client_message_count = "(".$messageCount.")";
        }
        
        //get current message for client reply save
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $message_id = $request->getParam('message_id');
            if(isset($message_id)) {
            	$this->view->current_message_id = $message_id;
            }
        }
        
        $this->view->campaign_id = $request->getParam('id');
        $clientCampaignListNamespace = new Zend_Session_Namespace('ClientCampaignList');
    }
    
    function clientreplysaveAction() {
        $this->_helper->layout->disableLayout();
        
        $campaign_id = $this->_request->getParam('id');
        
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            $messageId = (int) $formData['message_id'];
            if ($messageId > 0) {
                //TODO: get to type and to from message
                $clientMessageModel = new ClientMessage();
                $message = $clientMessageModel->fetchRow('id = '.$messageId);
                $currentTime = date("Y-m-d H:i:s");
                //TODO: get from type from client table
                $newReply = $clientMessageModel->saveReply($message->to_type,$this->_currentClient->id,$message->from_type,$message->from,
                   $formData['subject'],$formData['content'],$this->_currentClient->id,$currentTime,$messageId);
                if ($newReply > 0) {
                	//update message state as "REPLIED"
                	//$clientMessageModel->updateState($messageId,"REPLIED");
                }
            }
            $this->_helper->redirector('clientmessage','dashboard', null, array('id'=>$campaign_id,'message_id'=>$messageId));
        }
        $this->_helper->redirector('clientmessage','dashboard');
    }
    
    function clientmessagesaveAction() {
        $this->_helper->layout->disableLayout();
        $result = "Failure";
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            //save message
            $currentTime = date("Y-m-d H:i:s");
            $clientMessageModel = new ClientMessage();
            $newMessage = $clientMessageModel->createRecord($formData['from_type'],$formData['from'],$formData['to_type'],
                $formData['to'],$formData['subject'],$formData['type'],$formData['content'],$this->_currentClient->id,$currentTime);
            if ($newMessage > 0) {
                $result = "Success";
            }
        }
        $this->_helper->redirector('clientmessage','dashboard');
    }
    
    function clientmessagestateAction() {
        $this->_helper->layout->disableLayout();
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $message_id = $request->getParam('message_id');
            $clientMessageModel = new ClientMessage();
            $state = $clientMessageModel->updateState($message_id);
        }
        $this->_helper->json($state);
    }
    
    function clientmessagestarAction() {
        $this->_helper->layout->disableLayout();
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $message_id = $request->getParam('message_id');
            $clientMessageModel = new ClientMessage();
            $star = $clientMessageModel->updateStar($message_id);
        }
        $this->_helper->json($star);
    }
    
    function adminmessageAction() {
        $this->_helper->layout->setLayout("layout_admin");
        $this->view->activeTab = 'Message';
        $clientMessageModel = new ClientMessage();
        $order = "create_date desc";
        $this->view->messages = $clientMessageModel->find_all_message($order);
        
        // get current page(default page = 1)
        if($this->_request->getParam('page'))
        {
            $this->_curPage = $this->_request->getParam('page');
        }
        //paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Iterator($this->view->messages));
        $paginator->setCurrentPageNumber($this->_curPage)
        ->setItemCountPerPage($this->_rowsPerPage); 
        $this->view->paginator = $paginator;
        
        // Get reply count for each message.
        $replycountMap = array();
        if(isset($this->view->messages)) {
	        $db = Zend_Registry::get('db');
            $rs = $db->fetchAll("SELECT parent_id, count(id) as num FROM client_message ".
                                "WHERE parent_id is not null group by parent_id");
            foreach ($rs as $row) {
                $replycountMap[$row['parent_id']] = $row['num'];
            }
            $this->view->replycount = $replycountMap;
        }
        //Zend_Debug::dump($replycountMap);
    }
    
    function adminmessageviewAction() {
        $this->_helper->layout->setLayout("layout_admin");
        
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $messageId = $request->getParam('id');
            $state = $request->getParam('state');
            $clientMessageModel = new ClientMessage();
            $this->view->message = $clientMessageModel->fetchRow('id = '.$messageId);
            if(isset($this->view->message)) {
	            $where = "parent_id =".$messageId;
	            $order = "create_date asc";
	            $this->view->oldreply = $clientMessageModel->find_by_condition($where, $order);
            }
        }
    }
    
    function adminmessageaddAction() {
        $this->_helper->layout->setLayout("layout_admin");
        $clientModel = new Client();
        $order = "id desc";
        $this->view->clients = $clientModel->fetchAll(null,$order);
    }
    
    function adminmessagesaveAction() {
        $this->_helper->layout->disableLayout();
        $result = "Failure";
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            //save message
            $currentTime = date("Y-m-d H:i:s");
            $clientMessageModel = new ClientMessage();
            $newMessage = $clientMessageModel->createRecord($formData['from_type'],(int)$formData['from'],$formData['to_type'],
                (int)$formData['to'],$formData['subject'],$formData['type'],$formData['content'],$this->_currentClient->id,$currentTime);
            if ($newMessage > 0) {
                $result = "Success";
            }
        }
        $this->_helper->redirector('adminmessage','dashboard');
    }
    
    function adminmessagedeleteAction() {
        $this->_helper->layout->disableLayout();
        $result = "Failure";
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $messageId = $request->getParam('id');
            $clientMessageModel = new ClientMessage();
            $this->view->message = $clientMessageModel->fetchRow('id = '.$messageId);
            if(isset($this->view->message)) {
                $where = "parent_id =".$messageId;
                $rows = $clientMessageModel->delete($where);
                $clientMessageModel->delete('id = '.$messageId);
            }
        }
        $this->_helper->redirector('adminmessage','dashboard');
    }
    
    function adminreplysaveAction() {
        $this->_helper->layout->disableLayout();
        
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            $messageId = (int) $formData['message_id'];
            if ($messageId > 0) {
                //TODO: get to type and to from message
                $clientMessageModel = new ClientMessage();
                $message = $clientMessageModel->fetchRow('id = '.$messageId);
                $currentTime = date("Y-m-d H:i:s");
                //TODO: get from type from client table
                $newReply = $clientMessageModel->saveReply($formData['from_type'],$formData['from'],$formData['to_type'],$formData['to'],
                   $formData['subject'],$formData['content'],$this->_currentClient->id,$currentTime,$messageId);
                if ($newReply > 0) {
                    $this->_helper->redirector('adminmessageview','dashboard', null, array('id'=>$messageId));
                }
            }
        }
        
    }
    
    function adminreplydeleteAction() {
        $this->_helper->layout->disableLayout();
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $replyId = $request->getParam('id');
            $clientMessageModel = new ClientMessage();
            $where = "id = ".$replyId;
            $reply = $clientMessageModel->fetchRow($where);
            if(isset($reply)) {
            	$messageId = $reply->parent_id;
                $clientMessageModel->delete($where);
            }
        }
        $this->_helper->redirector('adminmessageview','dashboard', null, array('id'=>$messageId));
    }
    
    
}
