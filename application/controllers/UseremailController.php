<?php
include_once 'Indicate2Connect.php';
class UseremailController extends MyController {
	function createAction() {
	    $this->_helper->layout->disableLayout();
		$postData = $this->_request->getPost();
		$accessCode = $postData['accessCode'];
		$email = $postData['email'];
		$currentTime = date("Y-m-d H:i:s");
		if($this->checkEmail($email)) {
		  $useremailModel = new UserEmail();
		  $useremail = $useremailModel->createRow();
		  $useremail->accessCode = $accessCode;
		  $useremail->email = $email;
		  $useremail->create_date = $currentTime;
		  $useremail->save();
		  $this->view->message = "发送成功, 再推荐一位吧";
		} else {
		  $this->view->message = "请输入正确的Email地址";
		}
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$this->view->ajax = true;
		} else {
			$this->view->type = $postData['type'];
			$this->view->ajax = false;
		}
	}

	function checkEmail($email) {
		if (preg_match('/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/', $email)) {
			return true;
		}
		return false;
	}
}