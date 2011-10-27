<?php
/**
  * this class loads the client skin
  *
  */
class Zend_View_Helper_LoadSkin extends Zend_View_Helper_Abstract {
     public function loadSkin ($skin, $path) {
       // load the skin config file
       if(isset($skin)) {
          try {
          	$skinData = new Zend_Config_Xml('./skins/' . $skin . '/skin.xml');
          	$stylesheets = $skinData->stylesheets->stylesheet->toArray();
          	// append each stylesheet
            if (is_array($stylesheets)) {
              foreach ($stylesheets as $stylesheet) {
               $this->view->headLink()->appendStylesheet($path . '/skins/' . $skin . '/css/' . $stylesheet);
              }
            }
          } catch (Zend_Config_Exception $e) {
          	
          }
       }
     }
}
