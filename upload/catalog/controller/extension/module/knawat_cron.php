<?php
class ControllerExtensionModuleKnawatCron extends Controller { 
	
	public function __construct($registry) {
		parent::__construct($registry);
	}
	
	public function index() {
		//do nothing
	}

	public function ajax_import($data){
		if( $this->is_admin ){
			$this->load->model( $this->route );
		}else{
			$admin_dir = str_replace( 'system/', 'admin/', DIR_SYSTEM );
			require_once $admin_dir . "model/extension/module/knawat_dropshipping.php";
			$this->model_extension_module_knawat_dropshipping = new ModelExtensionModuleKnawatDropshipping( $this->registry );
		}

		require_once( DIR_SYSTEM . 'library/knawat_dropshipping/knawatimporter.php');

		$item = array();
		if( isset($data) && !empty($data) ){
			$process_data = $data;
			$item = array();
			if( isset( $process_data['limit'] ) ){
				$item['limit'] = (int)$process_data['limit'];
			}
			if( isset( $process_data['page'] ) ){
				$item['page'] = (int)$process_data['page'];
			}
			if( isset( $process_data['force_update'] ) ){
				$item['force_update'] = (boolean)$process_data['force_update'];
			}
			if( isset( $process_data['prevent_timeouts'] ) ){
				$item['prevent_timeouts'] = (boolean)$process_data['prevent_timeouts'];
			}
			if( isset( $process_data['is_complete'] ) ){
				$item['is_complete'] = (boolean)$process_data['is_complete'];
			}
			if( isset( $process_data['imported'] ) ){
				$item['imported'] = (int)$process_data['imported'];
			}
			if( isset( $process_data['failed'] ) ){
				$item['failed'] = (int)$process_data['failed'];
			}
			if( isset( $process_data['updated'] ) ){
				$item['updated'] = (int)$process_data['updated'];
			}
			if( isset( $process_data['batch_done'] ) ){
				$item['batch_done'] = (boolean)$process_data['batch_done'];
			}
		}

		$knawatimporter = new KnawatImporter( $this->registry, $item );

		$import_data = $knawatimporter->import();
		
		$params = $knawatimporter->get_import_params();

		$item = $params;

		if( true == $params['batch_done'] ){
			$item['page']  = $params['page'] + 1;
		}else{
			$item['page']  = $params['page'];
		}
		/* add last updated time*/
		$start_time = $this->model_extension_module_knawat_dropshipping->get_knawat_meta('8158', 'time','start_time');
		if($item['is_complete'] === true){
			if( empty($start_time) ){
				$start_time = time();
			}
			$this->model_extension_module_knawat_dropshipping->update_knawat_meta('8159', 'time',$start_time, 'knawat_last_imported' );
		}
		$item['imported'] += count( $import_data['imported'] );
		$item['failed']   += count( $import_data['failed'] );
		$item['updated']  += count( $import_data['updated'] );
		return  $import_data = json_encode( $item );
	}
	
	public function importProducts($data = array()){
		$data = (array)json_decode($this->ajax_import($data));
		if(!$data['is_complete']){
			$this->importProducts($data);
		}
	}

}
