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

	public function order_changed( $route, $data ){

		$order_id = $data[0];
		$order_status_id = $data[1];
		if( empty( $order_id ) || empty( $order_status_id ) ){
			return;
		}
		$is_update = false;

		$this->load->model("checkout/order");
		$this->load->model("account/order");

		/**
		 * Load admin model.
		 */
		$admin_dir = str_replace( 'system/', 'admin/', DIR_SYSTEM );
		require_once $admin_dir . "model/extension/module/knawat_dropshipping.php";
		$this->model_extension_module_knawat_dropshipping = new ModelExtensionModuleKnawatDropshipping( $this->registry );

		$order = $this->model_checkout_order->getOrder($order_id);
		$order_products = $this->model_account_order->getOrderProducts($order_id);
		if (!$order) {
			$this->log->write("Failed to load Order at send order to knawat.com, getOrder() failed. order_id:" . $order_id);
			return;
		}

		if (!$order_products) {
			$this->log->write("Failed to load Order Products at send order to knawat.com, getOrderProducts() failed. order_id:" . $order_id);
			return;
		}

		// Check Order if already created at MP or not.
		$knawat_order_id = $this->model_extension_module_knawat_dropshipping->get_knawat_meta( $order_id, 'knawat_order_id', 'order' );
		if( $knawat_order_id != '' ){
			$is_update = true;
		}

		/**
		 * Check is order contain Knawat Products.
		 */
		$knawat_order = $this->order_has_knawat_product( $order_products );
		if( !$knawat_order ){
			return;
		}

		/**
		 * @TODO: Check here token is valid or not.
		 */

		$order_status = $this->model_extension_module_knawat_dropshipping->get_order_status_name( $order_status_id );
		if( $order_status != '' ){
			$order['order_status'] = $order_status;
		}

		$mp_order = $this->format_order( $order, $order_products, $is_update );

		if( empty( $mp_order ) ){
			$this->log->write("Failed to format Order as per MP API at send order to knawat.com, format_order() failed. order_id:" . $order_id);
			return;
		}

		require_once( DIR_SYSTEM . 'library/knawat_dropshipping/knawatmpapi.php' );
		$knawatapi = new KnawatMPAPI( $this->registry );

		if( $is_update ){
			$failed = false;
			$failed_message = 'Something went wrong during order update to knawat.com';
			try{
				$result = $knawatapi->put( 'orders/'.$knawat_order_id, $mp_order );
				if( !empty( $result ) && isset( $result->status ) && 'success' === $result->status ){
					$korder_id = $result->data->id;
				}else{
					$failed = true;
				}
			}catch( Exception $e ){
				$failed = true;
				$failed_message = $e->getMessage();
				$this->log->write( $failed_message . " => order_id:" . $order_id);
			}
			if( $failed ){
				$this->model_extension_module_knawat_dropshipping->update_knawat_meta( $order_id, 'knawat_sync_failed', $failed_message, 'order' );
			}
		}else{

			$failed = false;
			$failed_message = 'Something went wrong during order create on knawat.com';
			try{
				$result = $knawatapi->post( 'orders', $mp_order );
				if( !empty( $result ) && isset( $result->status ) && 'success' === $result->status ){

					$korder_id = $result->data->id;
					$this->model_extension_module_knawat_dropshipping->update_knawat_meta( $order_id, 'knawat_order','1', 'order' );
					$this->model_extension_module_knawat_dropshipping->update_knawat_meta( $order_id, 'knawat_order_id', $korder_id, 'order' );

					$this->model_extension_module_knawat_dropshipping->delete_knawat_meta( $order_id, 'knawat_sync_failed', 'order' );

				}else{
					$failed = true;
				}
			}catch( Exception $e ){
				$failed = true;
				$failed_message = $e->getMessage();
				$this->log->write( $failed_message . " => order_id:" . $order_id);
			}
			if( $failed ){
				$this->model_extension_module_knawat_dropshipping->update_knawat_meta( $order_id, 'knawat_sync_failed', $failed_message, 'order' );
			}
		}
	}

	/**
	 * Format Order for MP API
	 */
	private function format_order( $order, $order_products, $is_update = false ){
		$this->load->model("account/order");

		if( !$is_update ){
			// Load Product options
			$temp_products = array();
			foreach ($order_products as $op) {
				$order_options = $this->model_account_order->getOrderOptions( $order['order_id'], $op['order_product_id']);
				$op['order_options'] = $order_options;
				$temp_products[] = $op;
			}
			$order_products = $temp_products;
		}

		$korder_products = $this->get_knawat_order_product( $order_products, $is_update );

		$mp_order = array(
			'id'	=> $order['order_id'],
			'status'=> strtolower( $order['order_status'] ),
			'items'	=> $korder_products,
		);

		$billing = array(
			'first_name'=> isset( $order['payment_firstname'] ) ? $order['payment_firstname'] : '',
			'last_name' => isset( $order['payment_lastname'] ) ? $order['payment_lastname'] : '',
			'company' 	=> isset( $order['payment_company'] ) ? $order['payment_company'] : '',
			'address_1' => isset( $order['payment_address_1'] ) ? $order['payment_address_1'] : '',
			'address_2' => isset( $order['payment_address_2'] ) ? $order['payment_address_2'] : '',
			'city' 		=> isset( $order['payment_city'] ) ? $order['payment_city'] : '',
			'state' 	=> isset( $order['payment_zone'] ) ? $order['payment_zone'] : '',
			'postcode' 	=> isset( $order['payment_postcode'] ) ? $order['payment_postcode'] : '',
			'country' 	=> isset( $order['payment_iso_code_2'] ) ? $order['payment_iso_code_2'] : $order['payment_country'],
			'email' 	=> isset( $order['email'] ) ? $order['email'] : '',
			'phone' 	=> isset( $order['telephone'] ) ? $order['telephone'] : ''
		);

		$shipping = array(
			'first_name'=> isset( $order['shipping_firstname'] ) ? $order['shipping_firstname'] : '',
			'last_name' => isset( $order['shipping_lastname'] ) ? $order['shipping_lastname'] : '',
			'company' 	=> isset( $order['shipping_company'] ) ? $order['shipping_company'] : '',
			'address_1' => isset( $order['shipping_address_1'] ) ? $order['shipping_address_1'] : '',
			'address_2' => isset( $order['shipping_address_2'] ) ? $order['shipping_address_2'] : '',
			'city' 		=> isset( $order['shipping_city'] ) ? $order['shipping_city'] : '',
			'state' 	=> isset( $order['shipping_zone'] ) ? $order['shipping_zone'] : '',
			'postcode' 	=> isset( $order['shipping_postcode'] ) ? $order['shipping_postcode'] : '',
			'country' 	=> isset( $order['shipping_iso_code_2'] ) ? $order['shipping_iso_code_2'] : $order['shipping_country'],
			'email' 	=> isset( $order['email'] ) ? $order['email'] : '',
			'phone' 	=> isset( $order['telephone'] ) ? $order['telephone'] : ''
		);

		/**
		 * @TODO: PDF invoice URL is pending.
		 */

		$mp_order['billing']	= $billing;
		$mp_order['shipping']	= $shipping;
		$mp_order['invoice_url'] = '';

		return $mp_order;
	}

	/**
	 * format product into knawat's order product
	 */
	private function get_knawat_order_product( $order_products, $is_update = false ){

		$knawat_products = array();
		foreach( $order_products as $product ){
			$is_knawat = $this->is_knawat_product( $product );
			if( $is_knawat ){
				if( $is_update ){
					$order_product_sku = $this->model_extension_module_knawat_dropshipping->get_knawat_meta( $product['order_product_id'], 'sku', 'order_product' );
					if( !$order_product_sku ){
						// Get SKU based on product ID.
						$order_product_sku = isset( $product['model'] ) ? $product['model'] : '';
					}
					$temp = array(
						'quantity'	=> $product['quantity'],
						'sku'		=> $order_product_sku
					);
					$knawat_products[] = $temp;
				}else{
					$product_options = $product['order_options'];
					foreach( $product_options as $option ){
						$product_option_sku = $this->model_extension_module_knawat_dropshipping->get_knawat_meta( $option['product_option_value_id'], 'sku', 'product_option_value' );
						if( $product_option_sku != '' ){
							$this->model_extension_module_knawat_dropshipping->update_knawat_meta( $product['order_product_id'], 'sku', $product_option_sku, 'order_product' );
							break;
						}
					}
					if( !$product_option_sku ){
						// Get SKU based on product ID (if its not there for options )
						$product_option_sku = isset( $product['model'] ) ? $product['model'] : '';
					}
					$temp = array(
						'quantity'	=> $product['quantity'],
						'sku'		=> $product_option_sku
					);
					$knawat_products[] = $temp;
				}
			}
		}
		return $knawat_products;
	}

	/**
	 * is it knawat's Product?
	 */
	private function is_knawat_product( $order_product ){
		if( !empty( $order_product ) ){
			$product_id = $order_product['product_id'];
			if( !$product_id ){
				return false;
			}

			$is_knawat = $this->model_extension_module_knawat_dropshipping->get_knawat_meta( $product_id, 'is_knawat', 'product' );
			if( $is_knawat == '1' ){
				return true;
			}
		}
		return false;
	}

	/**
	 * order has knawat's products?
	 */
	private function order_has_knawat_product( $order_products ){
		if( empty( $order_products ) ){
			return false;
		}
		foreach( $order_products as $product ){
			$is_knawat = $this->is_knawat_product( $product );
			if( $is_knawat ){
				return true;
			}
		}
		return false;
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
				
				// import product
				require_once( DIR_SYSTEM . 'library/knawat_dropshipping/knawatimporter.php' );
				$knawatimporter = new KnawatImporter( $this->registry, array( 'sku'=> $product_sku ), 'single' );
				$import_results = $knawatimporter->import();
			}
		}
	}
}
?>