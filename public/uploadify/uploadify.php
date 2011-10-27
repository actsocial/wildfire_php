<?php
/*
Uploadify v2.1.4
Release Date: November 8, 2010

Copyright (c) 2010 Ronnie Garcia, Travis Nickels

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

//if(!is_numeric($_GET['consumer'])||!is_numeric($_GET['report'])){
//	die;
//}

//$result = mysql_connect('localhost','root','123456')or die('can not connect to the mysql');
//mysql_set_charset('utf8');
//mysql_select_db('wildfire');
if (!empty($_FILES)) {
		$tempFile = $_FILES['Filedata']['tmp_name'];
	
     	$param = explode('_', $_GET['param']);
     	
     	$num = count($param) ;
     	for ($i = 0; $i < $num ; ){
     		$request[$param[$i]] =  $param[$i+1];
     		$i = $i + 2;
     	}
        //$name     = iconv("utf-8","gb2312",$consumer.'_'.$report.$_FILES['Filedata']['name']);
        $name     = iconv("utf-8","gb2312",date('Y-m-d-H-i-s').'.png');
        $targetPath = $_SERVER['DOCUMENT_ROOT'] . $_REQUEST['folder'] . '/';
        $url      = "http://".$_SERVER['SERVER_NAME'].$request['url'].'/name/'.$name.'';
        foreach($request as $key => $val){
        	if($key != 'url'){
        		$url  .= "/$key/$val";
        	}        	
        }
		//$targetPath = $_SERVER['DOCUMENT_ROOT'] . $_REQUEST['folder'] . '_images/';
		//$targetFile =  str_replace('//','/',$targetPath) . $_FILES['Filedata']['name'];
        $targetFile =  str_replace('//','/',$targetPath) .$name ;	
	
		// $fileTypes  = str_replace('*.','',$_REQUEST['fileext']);
		// $fileTypes  = str_replace(';','|',$fileTypes);
		// $typesArray = split('\|',$fileTypes);
		// $fileParts  = pathinfo($_FILES['Filedata']['name']);
	
	    // if (in_array($fileParts['extension'],$typesArray)) {
		// Uncomment the following line if you want to make the directory if it doesn't exist
		// mkdir(str_replace('//','/',$targetPath), 0755, true);
		
	     
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$output = curl_exec($ch);
		curl_close($ch);
			   		
		
	    move_uploaded_file($tempFile,$targetFile);
	    $name = iconv("gb2312","utf-8",$name);
	    $targetFile = str_replace('//','/',$targetPath).$name;
	    //mysql_query("insert into report_images(name,consumer,report,path,crdate) values ('$name','$consumer','$report','$targetFile','".date('Y-m-d h:i:s')."')");
		echo str_replace($_SERVER['DOCUMENT_ROOT'],'',$targetFile);
		// } else {
		// 	echo 'Invalid file type.';
		// }
}
?>