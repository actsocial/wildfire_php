<?php
class ReporttabController extends MyController {
	function adminindexAction() {
		$reportTab = new ReportTab();
		$this->view->reporttabs = $reportTab->findBy(array (
			'campaign'
		));
	}

	function adminnewAction() {
		$campaign = new Campaign();
		$tag = new Tag();
		$this->view->campaigns = $campaign->fetchAll();
		$this->view->tags = $tag->fetchAll();
		$this->view->reportTab = new ReportTab();
	}

	function admincreateAction() {
		$reportTab = new ReportTab();
		$data = array (
			'campaign_id' => $this->getRequest()->getParam('campaign_id'),
			'name' => $this->getRequest()->getParam('name'),
			'description' => $this->getRequest()->getParam('description')
		);
		$reportTabId = $reportTab->save($data);

		$tagging = new Tagging();
		$tags = $this->getRequest()->getParam('tags');
		foreach ($tags as $tag) {
			$data = array (
				"report_tab_id" => $reportTabId,
				"tag_id" => $tag
			);
			$tagging->save($data);
		}
		$this->_helper->redirector('adminindex', 'reporttab');
	}

	function admindeleteAction() {
		$reportTabTable = new ReportTab();
		$where = $reportTabTable->getAdapter()->quoteInto('id = ?', $this->getRequest()->getParam('id'));
		$reportTabTable->delete($where);
		$this->_helper->redirector('adminindex', 'reporttab');
	}

	function admineditAction() {
		$reportTab = new ReportTab();
		$this->view->reportTab = $reportTab->fetchRow("id = " . $this->getRequest()->getParam('id'));

		$campaign = new Campaign();
		$this->view->campaigns = $campaign->fetchAll();

		$tag = new Tag();
		$this->view->tags = $tag->fetchAll();

		$tagging = new Tagging();
		//$taggings = $tagging->fetchAll($tagging->select()->from('taggings', 'tag_id')->where('report_tab_id = ? ', $this->getRequest()->getParam('id')));
		$taggings = $tagging->findBy(array('report_tab_id'=>$this->getRequest()->getParam('id')));
		$tagging_ids = array ();
		foreach ($taggings as $tagging) {
			array_push($tagging_ids, $tagging->tag_id);
		}
		$this->view->taggings = $tagging_ids;
	}

	function adminupdateAction() {
		$reportTab = new ReportTab();
		$data = array (
			'campaign_id' => $this->getRequest()->getParam('campaign_id'),
			'name' => $this->getRequest()->getParam('name'),
			'description' => $this->getRequest()->getParam('description'),
			
		);
		$reportTab->update($data, "id = " . $this->getRequest()->getParam('id'));

		$tagging = new Tagging();
		$where = $tagging->getAdapter()->quoteInto('report_tab_id = ?', $this->getRequest()->getParam('id'));
		$tagging->delete($where);

		$tags = $this->getRequest()->getParam('tags');
		foreach ($tags as $tag) {
			$data = array (
				"report_tab_id" => $this->getRequest()->getParam('id'),
				"tag_id" => $tag
			);
			$tagging->save($data);
		}
		$this->_helper->redirector('adminindex', 'reporttab');
	}
	

}