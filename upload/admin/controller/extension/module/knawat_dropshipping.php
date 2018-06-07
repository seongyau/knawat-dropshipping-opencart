<?php
class ControllerExtensionModuleKnawatDropshipping extends Controller { 
	
	private $error = array();
	private $route = 'extension/module/knawat_dropshipping';

	public function index() {
		$this->load->language( $this->route );
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		// Validate and Submit Posts 
		if ( ($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate() ) {
			$this->model_setting_setting->editSetting('module_knawat_dropshipping', $this->request->post );

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		// Load Laguage strings
		$data = array(
			'entry_consumer_key' 			=> $this->language->get('entry_consumer_key'),
			'consumer_key_placeholder' 		=> $this->language->get('consumer_key_placeholder'),
			'entry_consumer_secret' 		=> $this->language->get('entry_consumer_secret'),
			'consumer_secret_placeholder' 	=> $this->language->get('consumer_secret_placeholder')
		);

		// Check and set warning.
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		// Status Error
		if (isset($this->error['error_status'])) {
            $data['error_status'] = $this->error['error_status'];
        } else {
            $data['error_status'] = '';
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
			'href' => $this->url->link( $this->route, 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link( $this->route, 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		// Set module status
		if (isset($this->request->post['module_knawat_dropshipping_status'])) {
			$data['module_knawat_dropshipping_status'] = $this->request->post['module_knawat_dropshipping_status'];
		} else {
			$data['module_knawat_dropshipping_status'] = $this->config->get('module_knawat_dropshipping_status');
		}

		// Set Consumer Key
		if (isset($this->request->post['module_knawat_dropshipping_consumer_key'])) {
			$data['module_knawat_dropshipping_consumer_key'] = $this->request->post['module_knawat_dropshipping_consumer_key'];
		} else {
			$data['module_knawat_dropshipping_consumer_key'] = $this->config->get('module_knawat_dropshipping_consumer_key');
		}

		// Set Consumer Secret
		if (isset($this->request->post['module_knawat_dropshipping_consumer_secret'])) {
			$data['module_knawat_dropshipping_consumer_secret'] = $this->request->post['module_knawat_dropshipping_consumer_secret'];
		} else {
			$data['module_knawat_dropshipping_consumer_secret'] = $this->config->get('module_knawat_dropshipping_consumer_secret');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view( $this->route, $data) );
	}

	protected function validate() {
		if (!$this->user->hasPermission( 'modify', $this->route ) ) {
            $this->error['warning'] = $this->language->get('error_permission');
            return false;
        }

        if(!isset( $this->request->post['module_knawat_dropshipping_status'])){
            $this->error['error_status'] = $this->language->get('error_status');
            return false;
        }
		return true;
	}
}
?>