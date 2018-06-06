<?php
class ControllerExtensionModuleKnawatDropshipping extends Controller { 
	private $error = array();

	public function index() {
		$this->load->language('extension/module/knawat_dropshipping');
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		// Validate and Submit Posts 
		if ( ($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate() ) {
			$this->model_setting_setting->editSetting('module_knawat_dropshipping', $this->request->post );

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		// Check and set warning.
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		// Setup Breadcrumbs Data.
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/knawat_dropshipping', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/knawat_dropshipping', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_knawat_dropshipping_status'])) {
			$data['module_knawat_dropshipping_status'] = $this->request->post['module_knawat_dropshipping_status'];
		} else {
			$data['module_knawat_dropshipping_status'] = $this->config->get('module_knawat_dropshipping_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/knawat_dropshipping', $data));
	}

	protected function validate() {
		// TODO: Form validation
		return true;
	}
}
?>