<?
//2005-6-8 kychou
//2006-02-18 xhg for sms
//require_once("../api/xmlbase.inc.php");
class XMLClient
{
	var $responseXML;
	var $sendXML;
	var $DEBUG=0;
	var $serverURL;
	var $XMLType;
	var $VCP;
	var $VCPPassword;

	function getCode()
	{
		$start_pos = strpos($this->responseXML, "<result code=\"");
		return trim(substr($this->responseXML, $start_pos + 14, 4));
	}

	function getMessage()
	{
		return getValue("msg",$this->responseXML);
	}

	function isSuccess()
	{
		return eregi("successfully",$this->responseXML);
	}

	function sendXMLData($XMLDATA)
	{
			$this->sendXML=$XMLDATA;
			$buffer=8192;	//设置缓冲大小
			$timeout=20;	//设置socket连接超时时间
			$this->responseXML="";
			if($this->DEBUG)echo "sendxml:".$XMLDATA;
			$this->serverURL=eregi_replace("^http://","",$this->serverURL);
			$pos=strpos($this->serverURL,"/");
			if($pos>0){
				$deshost=substr($this->serverURL,0,$pos);
				$despath=substr($this->serverURL,$pos);
			}else{
				$deshost=$this->serverURL;
				$despath="/";
			}
			$pos=strpos($deshost,":");
			if($pos>0){
				$port=substr($deshost,$pos+1);
				$deshost=substr($deshost,0,$pos);
			}else $port=80;
			if($this->DEBUG) echo "$deshost:$port:$despath";
			if(strlen($deshost)==0 || strlen($despath)==0 || $port<=0)
			{
				$this->responseXML="HOST:".$deshost."   PORT:".$port."   PATH:".$despath;
				return $this->responseXML;
			}
			$fp =@fsockopen($deshost, $port, $errno, $errstr, $timeout);
			if(!$fp)
			{
				$this->responseXML="unable to connect to ".$deshost.":".$port;
				return $this->responseXML;
			}
			/*
			$out = "POST $despath HTTP/1.1\r\n";
			$out .= "Host: $deshost\r\n";
			$out .= "Content-Length: ".strlen($XMLDATA)."\r\n";
			$out .= "Connection: Close\r\n\r\n";
			*/
			$out=$XMLDATA;
			fputs($fp, $out);
			do {
				$data = fread($fp, $buffer);
				if (strlen($data) == 0) break;
				$contents = $data;
				if (eregi("</scp>", $contents)) break;
			} while(true);
			fclose($fp);
			$this->responseXML=$contents;

			if($this->DEBUG) echo $this->responseXML;
			if(!$this->responseXML) $this->responseXML="NO XML Error,please check the xml which post to Server.";
			return $this->responseXML;
	}

    function toPlain(){
		$contents=trim($this->responseXML);
		$contents=strstr($contents,"<?xml");
		$end=strpos($contents,"</scp>")+strlen("</scp>");
		$contents=substr($contents,0,$end);
		if($contents)	$this->responseXML=$contents;
		return $this->responseXML;
	}

	function sendSCPData($desurl,$XMLDATA){
		 $XMLDATA=$this->toSCPXML($XMLDATA);
         return $this->sendXMLData($XMLDATA);
	}

	function toArray($content="response",$parsetype=0){
		$parser = xml_parser_create();
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,1);
		xml_parse_into_struct($parser,$this->responseXML,$values,$tags);
		xml_parser_free($parser);
		$showMess=XMLtoArray($values,$tags,$content,$parsetype);
		if(!$showMess)	$showMess[Error]=$this->responseXML;
		return $showMess;
	}

	function toSCPXML($CommandXML)
	{
		$cltrid=CltrID();
		$clientid=getENCID($cltrid,$this->VCPPassword);
		$xmlns = $this->getXMLNS();
		$secruser = $this->getSecurityUser();
		$respxml="<?xml version=\"1.0\" encoding=\"GB2312\"?>
		<scp $xmlns>
			<command>
				   $CommandXML
			</command>
			<security>
				$secruser
				<cltrid>$cltrid</cltrid>
				<login>$clientid</login>
		   </security>
		</scp>";
		//格式化XML,以便显示时好看一点
        //$respxml=eregi_replace("\t","    ",$respxml);
		return $respxml;
	}

	function getXMLNS()
	{
		if(strtoupper($this->XMLType)=="SMS"){
			$xmlns = "xmlns=\"urn:mobile:params:xml:ns:scp-1.0\"
			  xmlns:sms=\"urn:todaynic.com:sms\"
			  xmlns:user=\"urn:todaynic.com:user\"";
		}
		else $xmlns = "xmlns=\"urn:scp:params:xml:ns:scp-3.01\"
			  xmlns:host=\"urn:todayisp.com:client\"";
		return $xmlns;
	}

	function getSecurityUser()
	{
		if(strtoupper($this->XMLType)=="SMS"){
			$secruser = "<smsuser>$this->VCP</smsuser>";
		}
		else $secruser = "<vcpuser>$this->VCP</vcpuser>";
		return $secruser;
	}

	function getUserInfo($contactI)
	{
		$sendxml="<action>user:detail</action>
					<userid>$contactI[userid]</userid>
					<userpassword>$contactI[userpassword]</userpassword>";
		$this->sendSCPData($this->serverURL,$sendxml);
		$this->toPlain();
		return $this->responseXML;
	}

}
?>