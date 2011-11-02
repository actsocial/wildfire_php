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
$result = mysql_connect('127.0.0.1','wf','8HmmIg3T')or die('can not connect to the mysql');
mysql_set_charset('utf8');
mysql_select_db('wildfire');
if (!empty($_FILES)) {
	$tempFile = $_FILES['Filedata']['tmp_name'];
	$param = explode('_', $_GET['param']);
	$consumer = $param[1];
       $report   = $param[0];

	$targetPath = $_SERVER['DOCUMENT_ROOT'] . $_REQUEST['folder'] . '_images/';
       $targetPath =  '/home/wildfire/upload_images/';
       // $name     = iconv("utf-8","gb2312",$consumer.'_'.$report.$_FILES['Filedata']['name']);
	$name     = iconv("utf-8","gb2312",$consumer.'_'.$report.date('Y-m-d H:i:s').'.png');

 
	$targetFile =  str_replace('//','/',$targetPath) . iconv("utf-8","gb2312",$_FILES['Filedata']['name']);
       $targetFile =  str_replace('//','/',$targetPath) .$name ;	
	
	// $fileTypes  = str_replace('*.','',$_REQUEST['fileext']);
	// $fileTypes  = str_replace(';','|',$fileTypes);
	// $typesArray = split('\|',$fileTypes);
	// $fileParts  = pathinfo($_FILES['Filedata']['name']);
	
	// if (in_array($fileParts['extension'],$typesArray)) {
		// Uncomment the following line if you want to make the directory if it doesn't exist
		// mkdir(str_replace('//','/',$targetPath), 0755, true);
		
		move_uploaded_file($tempFile,$targetFile);
	    	$name = iconv("gb2312","utf-8",$name);
	   	$targetFile = str_replace('//','/',$targetPath).$name;
		mysql_query("insert into report_images(name,consumer,report,path,crdate) values ('$name','$consumer','$report','$targetFile','".date('Y-m-d H:i:s')."')");

		echo str_replace($_SERVER['DOCUMENT_ROOT'],'',$targetFile);
	// } else {
	// 	echo 'Invalid file type.';
	// }
}
?>
