<?php

class SpamController extends MyController
{

	
	function indexAction(){
		$this->_helper->layout->setLayout("layout_admin");
	}
	
	function spamAction()
    {
        $this->view->title = "Email spam";

        if ($this->_request->isPost()) {
            $from = $this->_request->getPost('From');
            $subject = $this->_request->getPost('Subject');
            $body = $this->_request->getPost('Body');
            
	        $total_score = 0;
	        $result = array();
	        // subject
	        if ($from != null && $from != "") {
	            $file_name = "rules/Spam_From.txt";
	            $total_score += $this->spamAnalyse($file_name, $from, $result);
	        }
	        // subject
	        if ($subject != null && $subject != "") {
	            $file_name = "rules/Spam_Subject.txt";
	            //$file_name = "rules/Chinese_rules_Subject.txt";
	            $total_score += $this->spamAnalyse($file_name, $subject, $result);
	        }
	        // body
	        if ($body != null && $body != "") {
	            $file_name = "rules/Spam_Body.txt";
	            //$file_name = "rules/Chinese_rules_Body.txt";
	            $total_score += $this->spamAnalyse($file_name, $body, $result);
	        }
	        //Zend_Debug::dump($result);
	        $this->view->result = $result;
	        $this->view->score = $total_score;
        
            $this->view->from = $from;
            $this->view->subject = $subject;
            $this->view->body = $body;

        }
        $this->_helper->layout->setLayout("layout_admin");
    }
    
    private function spamAnalyse($file_name, &$content, &$result)
    {
        $total_score = 0;
        if (file_exists($file_name) && is_readable ($file_name)) {
            $file_handle = fopen($file_name, "r");
            $patten = '/\s+/';
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                $flag = 0;
                $rule = null;
                while (trim($line) != '') {
                	//first line contains rule
                	if ($flag++ == 0) {
                		$words = explode("/", $line);
                		if(count($words) > 1) {
                			if (substr($words[1], -1) == "\\") {
                				//if rule as "/http:\//"
                				$rule = "/".$words[1]."//";
                			} else {
                				$rule = "/".$words[1]."/";
                			}
                		} else { //error: there is no rule in first line.
                			break;
                		}
                	} else {
	                    $words = preg_split($patten, $line);
	                    $identifier = $words[1];
	                    $score = $words[2];
                	}
                	
                    //next line
                    $line = fgets($file_handle);
                }
                if ($rule == null) {
                    continue;
                }
                //Zend_Debug::dump($rule);
                //Zend_Debug::dump($score);
                $num = preg_match_all($rule,$content,$arr);
                while ($num-- > 0) {
                	//Zend_Debug::dump($arr);
                	//Zend_Debug::dump($arr[0][$num]);
                	$content = str_replace($arr[0][$num], "<strong>".$arr[0][$num]."</strong>", $content);
                	//Zend_Debug::dump($content);
                    
                	array_push($result, array($rule, $identifier, $score));
                    $total_score += intval($score);
                }
            }
            //Zend_Debug::dump($result);
            //Zend_Debug::dump($total_score);
            fclose($file_handle);
        }
        return $total_score;
    }
}

