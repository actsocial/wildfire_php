<?php
class Sns extends Zend_Db_Table
{
	protected $_name = "sns_user";
  
  public static $_enabled_source = array(
      'Weibo' => array('name' => 'Weibo'),
      'Douban'=> array('name' => 'Douban'),
      'Renren' => array('name' => 'Renren'),
      'Tencent' => array('name' => 'Tencent'),
      'Kaixin' => array('name' => 'Kaixin'),
      'Netease' => array('name' => 'Netease'),
    );
    
  function loadByConsumerAndSource($consumer,$source){
    $select = $this->select();
    $select->where( 'consumer = ?', $consumer);
    $select->where( 'source = ?', $source);
    return $this->fetchRow($select);
  }  
}