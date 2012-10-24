<?php
require './facebook.php';
$facebook = new Facebook(array(
  'appId'  => '234132663359597',
  'secret' => '1886a959cd90f54b377613f8140a5669',
  'authorizationRedirectUrl' => 'http://demo.thinkdemo.com/Index/callback',
));