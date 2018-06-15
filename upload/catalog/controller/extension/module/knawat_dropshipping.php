<?php
class ControllerExtensionModuleKnawatDropshipping extends Controller { 
	
	private $error = array();
	private $route = 'extension/module/knawat_dropshipping';

	public function __construct($registry) {
        parent::__construct($registry);
	}
	
	public function index() {
		// Do nothing here.
	}

	/**
	 * Action trigger after called single product page. 
	 */
	public function after_single_product(){
		if( isset( $this->request->get['product_id'] ) && !empty( $this->request->get['product_id'] ) ){
			$action_url = $this->url->link( $this->route.'/sync_product_by_id/&product_id='.(int)$this->request->get['product_id'] );
			$this->curl_async( $action_url );
		}
	}

	/**
	 * Action trigger Just before add to cart.
	 */
	public function before_add_to_cart(){
		if( isset( $this->request->post['product_id'] ) && !empty( $this->request->post['product_id'] ) ){
			$action_url = $this->url->link( $this->route.'/sync_product_by_id/&product_id='.(int)$this->request->post['product_id'] );
			$this->curl_async( $action_url );
		}
	}

	private function curl_async( $url, $data = array() ){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500 );
		curl_exec($ch);
		curl_close($ch);
	}

	/**
	 * update product stock and price from knawat.com
	 */
	public function sync_product_by_id(){
		/** 
		 * @TODO: Add a token security for this call
		 */
		if( isset( $this->request->get['product_id'] ) && !empty( $this->request->get['product_id'] ) ){
			$product_id = $this->request->get['product_id'];
			if( !empty( $product_id ) ){
				$this->load->model( 'catalog/product' );
				$product = $this->model_catalog_product->getProduct( $product_id );
				$product_sku = isset( $product['sku'] ) ? $product['sku'] : '';
				if( empty( $product_sku ) ){
					$product_sku = isset( $product['model'] ) ? $product['model'] : '';
				}
				if( empty( $product_sku ) ){
					return;
				}
				
				// impoer product
				require_once( DIR_SYSTEM . 'library/knawat_dropshipping/knawatimporter.php' );
				$knawatimporter = new KnawatImporter( $this->registry, array( 'sku'=> $product_sku ), 'single' );
				$import_results = $knawatimporter->import();
			}
		}
	}
}
?>