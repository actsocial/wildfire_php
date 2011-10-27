<?php
require_once 'Pagination/Pagination.php';
include_once 'Indicate2Connect.php';
/*
 * CampaignPreInvitation Controller is only been used for a self-selection process in kraft campaign
 * Need to be standized or deprecated.
 * 
 * 2010/10/28 by ice
 */
class CampaignPreInvitationController extends MyController {
	protected $_rowsPerPage = 50;
	protected $_curPage = 1;
	function adminindexAction() {
		// get current page(default page = 1)
		if ($this->_request->getParam('page')) {
			$this->_curPage = $this->_request->getParam('page');
		}
		$db = Zend_Registry :: get('db');
		$select = $db->select();
		$select->from("campaign_pre_invitation", '*');
		//		$select->where("campaign_invitation.state = 'ACCEPTED'");
		//		$select->where("campaign_invitation.consumer_id = ?", $this->_currentUser->id);
		$select->order('state desc');
		$campaignPreInvitations = $db->fetchAll($select);
        //paging
        $this->view->controller = $this->_request->getControllerName();
        $this->view->action = $this->_request->getActionName();
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($campaignPreInvitations));
		$paginator->setCurrentPageNumber($this->_curPage)->setItemCountPerPage($this->_rowsPerPage);
		$this->view->paginator = $paginator;
		$this->_helper->layout->setLayout("layout_admin");
	}

	function admincreateAction() {
		$request = $this->getRequest();
		if ($request->isPost()) {
			$formData = $request->getPost();
			$email = $formData['email'];
			$area = $formData['area'];
			$emails = explode(",", $email);
			if (is_array($emails)) {
				foreach ($emails as $e) {
					$this->create_campaign_pre_invitation($e, $area);
				}
			}
		}
		$this->_helper->redirector('adminindex', 'campaignpreinvitation');
	}

	function adminsendAction() {
		$request = $this->getRequest();
		if ($request->isPost()) {
			$formData = $request->getPost();
			$ids = $formData['ids'];
			if (isset ($ids)) {
				foreach ($ids as $id) {
				   $config = Zend_Registry :: get('config');
				   $smtpSender = new Zend_Mail_Transport_Smtp($config->smtp->invitation->mail->server, array (
						        'username' => $config->smtp->invitation->mail->username,
								'password' => $config->smtp->invitation->mail->password,
								'auth' => $config->smtp->invitation->mail->auth,
								'ssl' => $config->smtp->invitation->mail->ssl,
								'port' => $config->smtp->invitation->mail->port
							));
					$campaignPreInvitationModel = new CampaignPreInvitation();
					$campaignPreInvitation = $campaignPreInvitationModel->fetchRow("id = " . $id);

					$useHtmlEmail = true;
					$subject = $this->view->translate('pre_invitation_of_kraft_campaign_subject');
					$body = $this->view->translate('pre_invitation_of_kraft_campaign_body');
					$stringChange = array (
						'?CODE?' => $campaignPreInvitation['code']
					);
					$body = strtr($body, $stringChange);
					$langNamespace = new Zend_Session_Namespace('Lang');
					Zend_Mail :: setDefaultTransport($smtpSender);
					$mail = new Zend_Mail('utf-8');
					if ($langNamespace->lang == 'en' || $langNamespace->lang == 'EN') {
						$mail->setSubject($subject);
					} else {
						$mail->setSubject("=?UTF-8?B?" . base64_encode($subject) . "?=");
					}
					if ($useHtmlEmail != null && $useHtmlEmail) {
						$mail->setBodyHtml($body);
					} else {
						$mail->setBodyText($body);
					}

					$mail->setFrom($config->smtp->invitation->mail->username);
					$mail->addTo($campaignPreInvitation->email);
					$mail->send();
					$campaignPreInvitation->state="SENT";
					$campaignPreInvitation->save();
				}
			}
		}
		$this->_helper->redirector('adminindex', 'campaignpreinvitation');
	}

	function adminimportAction() {
		$request = $this->getRequest();
		if ($request->isPost()) {
			$formData = $request->getPost();
			$area = $formData['area'];
			$file = $_FILES['file'];
			$objReader = PHPExcel_IOFactory :: createReader('Excel2007');
			$objReader->setReadDataOnly(true);
			$objPHPExcel = $objReader->load($file['tmp_name']);
			$objWorksheet = $objPHPExcel->getActiveSheet();
			foreach ($objWorksheet->getRowIterator() as $row) {
				$cellIterator = $row->getCellIterator();
				$cellIterator->setIterateOnlyExistingCells(false);
				foreach ($cellIterator as $cell) {
					$this->create_campaign_pre_invitation($cell->getValue(), $area);
				}
			}
			$this->_helper->redirector('adminindex', 'campaignpreinvitation');
		}
	}

	function activateAction() {
		$request = $this->getRequest();
		if ($request->isPost()) {
			$formData = $request->getPost();
			$code = $formData['code'];
			$content = $formData['content'];
			$campaignPreInvitationModel = new CampaignPreInvitation();
			$campaignPreInvitation = $campaignPreInvitationModel->fetchRow("code = '" . $code . "'");
			if (isset ($campaignPreInvitation)) {
				$campaignPreInvitation->state = "SUBMITTED";
				$campaignPreInvitation->save();
			}
		}
	}

	function thankyouAction() {
		$this->_helper->layout->disableLayout();
		$request = $this->getRequest();
		if ($request->isGet()) {
			$messageArray = $this->_flashMessenger->getMessages();
			if($messageArray != null) {
				$this->view->message = $messageArray[0];
			}
		}
	}

	function showAction() {
		$this->_helper->layout->disableLayout();
		$request = $this->getRequest();
		if ($request->isPost()) {
			$formData = $request->getPost();
			$code = $formData['code'];
			$content = $formData['content'];
			
			$campaignPreInvitationModel = new CampaignPreInvitation();
			$code = substr($code, 0, 12);
			$campaignPreInvitation = $campaignPreInvitationModel->fetchRow("code = '" . $code . "'");
			if(isset($campaignPreInvitation) && $campaignPreInvitation != null) {
				$db = Zend_Registry :: get('db');
			    $rs = $db->fetchOne("SELECT COUNT(id) as count FROM campaign_pre_invitation WHERE area = :area and state = :state", array (
							'area' => $campaignPreInvitation->area,
							'state' => 'FILLED'
				));
				if(!isset($content) || trim($content) == '') {
				  $this->view->message = "请输入报告内容";
				} else {
					//only when the page is already viewed before submit
					if ($campaignPreInvitation->state == "VIEWED") {
						$campaignPreInvitation->state = "FILLED";
						$campaignPreInvitation->content = $content;
						$campaignPreInvitation->date_used = date("Y-m-d H:i:s");
						$campaignPreInvitation->save();
						$this->_flashMessenger->addMessage('非常感谢您对优冠馅饼活动的支持！请耐心等待您的邀请函！');
						$this->_helper->redirector('thankyou', 'campaignpreinvitation');
					} else
						if ($campaignPreInvitation->state == "FILLED") {
												$db = Zend_Registry :: get('db');
						$this->view->message = "您已经填写过了";
					}
				}
			    $this->view->people_count = $rs['count'];
			} else {
			  $db = Zend_Registry :: get('db');
			  $rs = $db->fetchOne("SELECT COUNT(id) as count FROM campaign_pre_invitation WHERE area = :area and state = :state", array (
							'area' => 'hangzhou',
							'state' => 'FILLED'
			  ));
			  $this->view->people_count = $rs['count'];
			  $this->view->message = "您的邀请码不正确";
			}
			$this->view->code = $code;
		} else if ($request->isGet()) {
				$code = $this->_request->getParam('code');
				$campaignPreInvitationModel = new CampaignPreInvitation();
				$campaignPreInvitation = $campaignPreInvitationModel->fetchRow("code = '" . $code . "'");
				if (isset ($campaignPreInvitation)) {
					if ($campaignPreInvitation->state == "FILLED") {
											$db = Zend_Registry :: get('db');
						$rs = $db->fetchOne("SELECT COUNT(id) as count FROM campaign_pre_invitation WHERE area = :area and state = :state", array (
							'area' => 'hangzhou',
							'state' => 'FILLED'
						));
					    $this->view->people_count = $rs['count'];
						$this->view->message = "您已经填写过了";
					} else {
						$campaignPreInvitation->state = "VIEWED";
						$campaignPreInvitation->save();
						$area = $campaignPreInvitation->area;
						$db = Zend_Registry :: get('db');
						$rs = $db->fetchOne("SELECT COUNT(id) as count FROM campaign_pre_invitation WHERE area = :area and state = :state", array (
							'area' => $area,
							'state' => 'FILLED'
						));
						$this->view->people_count = $rs['count'];
						$this->view->code = $code;
					}
				} else {
					$db = Zend_Registry :: get('db');
						$rs = $db->fetchOne("SELECT COUNT(id) as count FROM campaign_pre_invitation WHERE area = :area and state = :state", array (
							'area' => 'hangzhou',
							'state' => 'FILLED'
						));
					$this->view->people_count = $rs['count'];
					$this->view->message = "您的邀请码不正确";
				}
				$this->view->code = $code;
			}
	}

	private function is_email($email) {
		if (preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/', $email)) {
			return true;
		}
		return false;
	}

	private function create_code() {
		$codePattern = '1234567890ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
		$code = '';
		for ($codeCount = 0; $codeCount < 12; $codeCount++) {
			$code = $code . $codePattern {
				mt_rand(0, 35)
				};
		}
		return $code;
	}

	private function create_campaign_pre_invitation($e, $area, $phone) {
		if ($this->is_email($e)) {
			$currentTime = date("Y-m-d H:i:s");
			$campaignPreInvitationModel = new CampaignPreInvitation();
			$campaignPreInvitation = $campaignPreInvitationModel->createRow();
			$campaignPreInvitation->email = $e;
			$campaignPreInvitation->area = $area;
			$campaignPreInvitation->code = $this->create_code();
			if(isset($phone)) {
				$campaignPreInvitation->phone = $phone;
			}
			$campaignPreInvitation->create_date = $currentTime;
			$campaignPreInvitation->save();
		}
	}
}