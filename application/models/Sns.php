<?php
class Sns
{
	public function get_enable_source() {
		return array(
			'Weibo' => array('name' => 'Weibo'),
			'Douban'=> array('name' => 'Douban'),
			'Renren' => array('name' => 'Renren'),
			'Tencent' => array('name' => 'Tencent'),
			'Kaixin' => array('name' => 'Kaixin'),
			'Netease' => array('name' => 'Netease'),
		);
	}
}