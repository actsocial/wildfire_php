<?php
// Define path to application directory
defined('PUBLIC_PATH') || define('PUBLIC_PATH', realpath(dirname(__FILE__)));
defined('APPLICATION_PATH') || define('APPLICATION_PATH', PUBLIC_PATH . '/../application');

error_reporting(E_ALL|E_STRICT);
$testEnv = 0;
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME']=='127.0.0.1'){
	$testEnv = 1;
}
//ini_set("soap.wsdl_cache_enabled", "0");
ini_set('display_errors', $testEnv);
date_default_timezone_set('Asia/Shanghai');

// directory setup and class loading
$applicationDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'])).'/../';

// setting the include path
$include_path = get_include_path();
$include_path.= PATH_SEPARATOR . $applicationDir . 'library';
$include_path.= PATH_SEPARATOR . $applicationDir . 'application'. DIRECTORY_SEPARATOR .'controllers'. DIRECTORY_SEPARATOR; 
$include_path.= PATH_SEPARATOR . $applicationDir . 'application'. DIRECTORY_SEPARATOR .'models'. DIRECTORY_SEPARATOR;
$include_path.= PATH_SEPARATOR . $applicationDir . 'application'. DIRECTORY_SEPARATOR .'objects'. DIRECTORY_SEPARATOR;
$include_path.= PATH_SEPARATOR . $applicationDir . 'library'. DIRECTORY_SEPARATOR .'SOAP'. DIRECTORY_SEPARATOR . 'SecureClient' . DIRECTORY_SEPARATOR;
$include_path.= PATH_SEPARATOR . $applicationDir . 'library'. DIRECTORY_SEPARATOR .'ofc'. DIRECTORY_SEPARATOR; 
$include_path.= PATH_SEPARATOR . $applicationDir . 'library'. DIRECTORY_SEPARATOR .'Pagination'. DIRECTORY_SEPARATOR; 
$include_path.= PATH_SEPARATOR . $applicationDir . 'library'. DIRECTORY_SEPARATOR .'phprpc'. DIRECTORY_SEPARATOR . 'dhparams' . DIRECTORY_SEPARATOR; 
$include_path.= PATH_SEPARATOR . $applicationDir . 'library'. DIRECTORY_SEPARATOR .'phprpc'. DIRECTORY_SEPARATOR; 
$include_path.= PATH_SEPARATOR . $applicationDir . 'library'. DIRECTORY_SEPARATOR .'xiaonei'. DIRECTORY_SEPARATOR;
$include_path.= PATH_SEPARATOR . $applicationDir . 'library'. DIRECTORY_SEPARATOR .'sms'. DIRECTORY_SEPARATOR;
$include_path.= PATH_SEPARATOR . $applicationDir . 'public'. DIRECTORY_SEPARATOR .'js'. DIRECTORY_SEPARATOR.'fckeditor'. DIRECTORY_SEPARATOR;
$include_path.= PATH_SEPARATOR . $applicationDir . 'ws'. DIRECTORY_SEPARATOR;
$include_path.= PATH_SEPARATOR . $applicationDir . 'library'. DIRECTORY_SEPARATOR .'PHPExcel'. DIRECTORY_SEPARATOR;

set_include_path($include_path);

try {

include "Zend/Loader.php";
include 'Zend/Cache.php';
Zend_Loader::registerAutoload();

//Zend_Session::setOptions(array('strict' => true));
Zend_Session::start();

//load configuration
$config = new Zend_Config_Ini("../application/config.ini","dev");
$registry = Zend_Registry::getInstance();
$registry->set('config',$config);

//setup database
$db = Zend_Db::factory($config->db);
Zend_Db_Table::setDefaultAdapter($db);
$registry->set('db', $db);
$db->query("SET NAMES 'utf8'");

//setup view
$view = new Zend_View();
$view->addHelperPath('Wildfire/View/Helper','Wildfire_View_Helper');
$view->addHelperPath("ZendX/JQuery/View/Helper", "ZendX_JQuery_View_Helper");
$view->jQuery()->enable();
ZendX_JQuery_View_Helper_JQuery::enableNoConflictMode();
//$view->headScript()->appendFile($view->baseUrl().'/scripts/jquery.corner.js');
$viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer();
$viewRenderer->setView($view);
Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);

//setup cache
$frontendOptions = array(
	'lifetime' => $config->cache->frontendOptions->maxLifetime,
	'automatic_serialization' => true
);
$backendOptions = array(
	'cache_dir' => $config->cache->backendOptions->cache_dir,
	'lifetime' => $config->cache->backendOptions->maxLifetime
);
$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
$registry->set('cache', $cache);

$registry->set('testEnv',$testEnv);
// setup controller
$frontController = Zend_Controller_Front::getInstance();
if ($testEnv==1){
	$frontController->throwExceptions(true);
}
$frontController->setControllerDirectory('../application/controllers');
$frontController->addControllerDirectory('../ws/controller','ws');

Zend_Layout::startMvc(array('layoutPath'=>'../application/layouts'));

// setup acl plugin
//$frontController->registerPlugin(new Wildfire_Controller_Plugin_Acl());

// setup i18n
$automatic = !empty($config->framework->language->automatic);
$language = strval($config->framework->language->type);
$defaultLanguage = strval($config->framework->language->default); 

$langNamespace = new Zend_Session_Namespace('Lang');
//if (empty($langNamespace->lang)){
	$language = $langNamespace->lang;
//}else{
//	$language = $defaultLanguage;
//}


//$cache = Zend_Cache::factory('Core', 'File');
//Zend_Translate::setCache($cache);

try{
$translate = new Zend_Translate('tmx', strval($config->framework->language->dir). 'common.xml' , $language);
}catch(Exception $e){
	$language = $defaultLanguage;
	$translate = new Zend_Translate('tmx', strval($config->framework->language->dir). 'common.xml' , $language);
	
}

// Change language according to browsers
if ($automatic)  {
	$acceptLanguage = explode(',', $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
	$acceptLanguage = $acceptLanguage[0];
	
	$language = explode('-', $acceptLanguage);
	$language = strtolower($language[0]);
	if (!in_array($language, $translate->getAdapter()->getList())) {
		$language = $defaultLanguage;
	}
		
	$translate = new Zend_Translate('tmx', strval($config->framework->language->dir). 'common.xml', $language);
}


//load language files
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'admin.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'campaign.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'campaignemail.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'campaigninformation.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'campaigninvitation.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'common.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'commonemail.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'consumer.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'conversation.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'gift.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'history.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'home.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'login.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'point.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'profilesurvey.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'register.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'report.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'site.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'spark_home.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'training.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'xiaonei.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'client.xml', $language);
$translate->getAdapter()->addTranslation(strval($config->framework->language->dir) . 'validation.xml', $language);

Zend_Registry::set('Zend_Translate', $translate);
//$locale = new Zend_Locale(Zend_Locale::BROWSER);
//Zend_Registry::set('locale',$locale);


// run!
$frontController->dispatch();
    
} catch (Exception $exp) {
    $contentType = 'text/html';
    header("Content-Type: $contentType; charset=utf-8");
    echo 'an unexpected error occurred.';
    echo '<h3>Unexpected Exception: ' . $exp->getMessage() . '</h3><pre>';
    echo $exp->getTraceAsString();
}
