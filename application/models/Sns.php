<?php
class Sns extends Zend_Db_Table
{
	protected $_name = "sns_user";
	
	function __construct($source){
		$this->source = $source;		
	}
	
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
	
	public function gen_access_token($oauth_verifier, $request_token, $request_token_secret, $oauth_token, $domain) {
		$access_token = get_oauth.gen_access_token($oauth_verifier, $request_token, $request_token_secret, $oauth_token, $domain);
		if (isset($access_token->token))
			$this->access_token = $access_token->token;
		if (isset($access_token->secret))
			$this->access_token = $access_token->secret;
	}

  public function get_oauth() {
  	if(isset($oauth))
      $oauth = new Oauth($this->source, $this->access_token, $this->refresh_token, $this->access_token_secret, $this->expires_in, $this->expires_at);    
    return $oauth;
  }

}