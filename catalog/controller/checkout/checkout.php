<?php
class ControllerCheckoutCheckout extends Controller {
	public function index() {
        $logger = new Log("checkout_index");

        $this->load->model('common/common');
        
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('checkout/checkout', '', true);
            $this->response->redirect($this->url->link('account/login', '', true));
        }
        
        
		    // Validate cart has products and has stock.
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$this->response->redirect($this->url->link('checkout/cart'));
		}

		
		// Validate minimum quantity requirements.
		$products = $this->cart->getProducts();

		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$this->response->redirect($this->url->link('checkout/cart'));
			}
		}

		$this->load->language('checkout/checkout');

		// Required by klarna
		if ($this->config->get('payment_klarna_account') || $this->config->get('payment_klarna_invoice')) {
			$this->document->addScript('http://cdn.klarna.com/public/kitt/toc/v1.0/js/klarna.terms.min.js');
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_cart'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('checkout/checkout', '', true)
		);

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];
			unset($this->session->data['error']);
		} else {
			$data['error_warning'] = '';
		}

		$logger->write("1");
		$data['logged'] = $this->customer->isLogged();

		if (isset($this->session->data['account'])) {
			$data['account'] = $this->session->data['account'];
		} else {
			$data['account'] = '';
		}

		$data['shipping_required'] = $this->cart->hasShipping();

		//address
		$data['addresses'] = array();
		$this->load->model('account/address');
		
		$results = $this->model_account_address->getAddresses();
		
		foreach ($results as $result) {
		    if ($result['address_format']) {
		        $format = $result['address_format'];
		    } else {
		        $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
		    }
		    
		    $find = array(
		        '{firstname}',
		        '{lastname}',
		        '{company}',
		        '{address_1}',
		        '{address_2}',
		        '{city}',
		        '{postcode}',
		        '{zone}',
		        '{zone_code}',
		        '{country}'
		    );
		    
		    $replace = array(
		        'firstname' => $result['firstname'],
		        'lastname'  => $result['lastname'],
		        'company'   => $result['company'],
		        'address_1' => $result['address_1'],
		        'address_2' => $result['address_2'],
		        'city'      => $result['city'],
		        'postcode'  => $result['postcode'],
		        'zone'      => $result['zone'],
		        'zone_code' => $result['zone_code'],
		        'country'   => $result['country']
		    );
		    
		    $data['addresses'][] = array(
		        'address_id' => $result['address_id'],
		        'address'    => str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))))
		    );
		    
		}

		if (ISSET($this->request->get['display_block'])){
		    $data['display_block'] = $this->request->get['display_block'];
		} else {
		    $data['display_block'] = '';
		}
		
		
		$this->load->model('localisation/country');
		
		$data['countries'] = $this->model_localisation_country->getCountries();
		
		
		//banner
		$data['banner'] = $this->model_common_common->getDisplayImages('HEADER_BANNER', 1920, 300);
		
		$cart_data = $this->model_common_common->getCartData();
	
		$data['products'] = $cart_data['products'];
		$data['vouchers'] = $cart_data['vouchers'];
		$data['totals'] = $cart_data['totals'];
		
		// Payment Methods
		$method_data = array();
		
		$this->load->model('setting/extension');
		
		$results = $this->model_setting_extension->getExtensions('payment');

		$pmt_address['country_id'] = 99;
		$pmt_address['zone_id'] = 0;
		$total = 0;
		foreach ($results as $result) {
		    if ($this->config->get('payment_' . $result['code'] . '_status')) {
		        $this->load->model('extension/payment/' . $result['code']);
		        
		        $method = $this->{'model_extension_payment_' . $result['code']}->getMethod($pmt_address, $total);
		        
		        if ($method) {
	                $method_data[$result['code']] = $method;
		        }
		    }
		}
		
		$sort_order = array();
		
		foreach ($method_data as $key => $value) {
		    $sort_order[$key] = $value['sort_order'];
		}
		
		array_multisort($sort_order, SORT_ASC, $method_data);
		
		$this->session->data['payment_methods'] = $method_data;
		
		if (empty($this->session->data['payment_methods'])) {
		    $data['error_warning'] = sprintf($this->language->get('error_no_payment'), $this->url->link('information/contact'));
		} else {
		    $data['error_warning'] = '';
		}
		
		if (isset($this->session->data['payment_methods'])) {
		    $data['payment_methods'] = $this->session->data['payment_methods'];
		} else {
		    $data['payment_methods'] = array();
		}
		
		if (isset($this->session->data['payment_method']['code'])) {
		    $data['code'] = $this->session->data['payment_method']['code'];
		} else {
		    $data['code'] = '';
		}
		
		// Shipping Methods
		$method_data = array();
		
		$this->load->model('setting/extension');
		
		$results = $this->model_setting_extension->getExtensions('shipping');
		
		foreach ($results as $result) {
		    if ($this->config->get('shipping_' . $result['code'] . '_status')) {
		        $this->load->model('extension/shipping/' . $result['code']);
		        
		        $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);
		        
		        if ($quote) {
		            $method_data[$result['code']] = array(
		                'title'      => $quote['title'],
		                'quote'      => $quote['quote'],
		                'sort_order' => $quote['sort_order'],
		                'error'      => $quote['error']
		            );
		        }
		    }
		}
		
		$sort_order = array();
		
		foreach ($method_data as $key => $value) {
		    $sort_order[$key] = $value['sort_order'];
		}
		
		array_multisort($sort_order, SORT_ASC, $method_data);
		
		$this->session->data['shipping_methods'] = $method_data;
		

		
		
		if (isset($this->session->data['shipping_methods'])) {
		    $data['shipping_methods'] = $this->session->data['shipping_methods'];
		} else {
		    $data['shipping_methods'] = array();
		}
		
		if (isset($this->session->data['shipping_method']['code'])) {
		    $data['code'] = $this->session->data['shipping_method']['code'];
		} else {
		    $data['code'] = '';
		}
		
		
		
		if ($this->config->get('config_checkout_id')) {
		    $this->load->model('catalog/information');
		    
		    $information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));
		    
		    if ($information_info) {
		        $data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_checkout_id'), true), $information_info['title'], $information_info['title']);
		    } else {
		        $data['text_agree'] = '';
		    }
		} else {
		    $data['text_agree'] = '';
		}
		
		
		
		$logger->write($data);
		
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('checkout/checkout', $data));
	}

	public function country() {
		$json = array();

		$this->load->model('localisation/country');

		$country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

		if ($country_info) {
			$this->load->model('localisation/zone');

			$json = array(
				'country_id'        => $country_info['country_id'],
				'name'              => $country_info['name'],
				'iso_code_2'        => $country_info['iso_code_2'],
				'iso_code_3'        => $country_info['iso_code_3'],
				'address_format'    => $country_info['address_format'],
				'postcode_required' => $country_info['postcode_required'],
				'zone'              => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
				'status'            => $country_info['status']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function customfield() {
		$json = array();

		$this->load->model('account/custom_field');

		// Customer Group
		if (isset($this->request->get['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->get['customer_group_id'], $this->config->get('config_customer_group_display'))) {
			$customer_group_id = $this->request->get['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

		foreach ($custom_fields as $custom_field) {
			$json[] = array(
				'custom_field_id' => $custom_field['custom_field_id'],
				'required'        => $custom_field['required']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function setAddressSession() {
	    $logger = new Log("setAddressSession");
	    
	    
	    $json = array();
	    
	    $address_id = $this->request->get['address_id'];
	    $address_type = $this->request->get['address_type'];
	    
	    if (!empty($address_id)){
	        $this->load->model('account/address');
	        
    	    if ($address_type == 'B'){
    	        $this->session->data['payment_address'] = $this->model_account_address->getAddress($address_id);
    	    } else {
    	        $this->session->data['shipping_address'] = $this->model_account_address->getAddress($address_id);
    	       
    	        $shipping_method = $this->request->post['shipping_method'];
    	        
    	        $logger->write($this->request->post);
    	        $logger->write($this->session->data['shipping_methods']);
    	        
    	        if (!isset($this->request->post['shipping_method'])) {
    	            $json['error'][] = $this->language->get('error_shipping');
    	        } else {
    	            $shipping = explode('.', $this->request->post['shipping_method']);
    	            
    	            if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
    	                $json['error'][] = $this->language->get('error_shipping');
    	            }
    	        }
    	        
    	        if (!$json) {
    	            $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
    	            $logger->write($this->session->data['shipping_method']);
    	        }   
    	    }
    	    
	    } else{
	        $json['error'][] = 'Select an address';
	    }

	    
	    
	    $logger->write("all good till here");
	    
	    
	    $logger->write("json message");
	    $logger->write($json);
	    
	    
	    $this->response->addHeader('Content-Type: application/json');
	    $this->response->setOutput(json_encode($json));
	}

	public function setPaymentMethod() {
	    $logger = new Log("setPaymentMethod");
	    
	    
	    $json = array();
	    
	    
	    $payment_method = $this->request->get['payment_method'];
	    
	    $logger->write($payment_method);
	    $logger->write($this->session->data['payment_methods']);
	    
	    if (!empty($payment_method)){
	        $this->session->data['payment_method'] = $this->session->data['payment_methods'][$payment_method];
	    } else{
	        $json['error'][] = 'Select A Payment Method';
	    }
	    
	    
	    
	    $logger->write("all good till here");
	    
	    
	    $logger->write("json message");
	    $logger->write($json);
	    
	    
	    $this->response->addHeader('Content-Type: application/json');
	    $this->response->setOutput(json_encode($json));
	}
	
	
	public function placeOrder() {
	    $logger = new Log("placeOrder");
	    
	    $logger->write("post message");
	    $logger->write($this->request->post);
	    
	    
	    $json = array();
	    
	    
	    $redirect = '';
	    
	    if ($this->cart->hasShipping()) {
	        // Validate if shipping address has been set.
	        if (!isset($this->session->data['shipping_address'])) {
	            $logger->write("1");
	            $redirect = $this->url->link('checkout/checkout', '', true);
	        }
	        
	        // Validate if shipping method has been set.
	        if (!isset($this->session->data['shipping_method'])) {
	            $logger->write("2");
	            $redirect = $this->url->link('checkout/checkout', '', true);
	        }
	    } else {
	        $logger->write("3");
	        unset($this->session->data['shipping_address']);
	        unset($this->session->data['shipping_method']);
	        unset($this->session->data['shipping_methods']);
	    }
	    
	    
	    // Validate if payment address has been set.
	    if (!isset($this->session->data['payment_address'])) {
	        $logger->write("5");
	        $redirect = $this->url->link('checkout/checkout', '', true);
	    }
	    
	    // Validate if payment method has been set.
	    if (!isset($this->session->data['payment_method'])) {
	        $logger->write("6");
	        $redirect = $this->url->link('checkout/checkout', '', true);
	    }
	    
	    // Validate cart has products and has stock.
	    if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
	        $logger->write("7");
	        $redirect = $this->url->link('checkout/cart');
	    }
	    
	    
	    // Validate minimum quantity requirements.
	    $products = $this->cart->getProducts();
	    
	    foreach ($products as $product) {
	        $product_total = 0;
	        
	        foreach ($products as $product_2) {
	            if ($product_2['product_id'] == $product['product_id']) {
	                $product_total += $product_2['quantity'];
	            }
	        }
	        
	        if ($product['minimum'] > $product_total) {
	            $logger->write("8");
	            $redirect = $this->url->link('checkout/cart');
	            
	            break;
	        }
	    }
	    
	    $logger->write("last");
	    if (!$redirect) {
	        $logger->write("here 6.1");
	       $json['redirect'] = $this->url->link('checkout/confirm', '', true);
	    } else {
	        $logger->write("here 6.2");
	        $json['redirect'] = $redirect;
	    }
	    $logger->write("here 7");
	    
	   // $json['success'] = 'All good';
	    $logger->write("all good till here");
	    
	    
	    $logger->write("json message");
	    $logger->write($json);
	    
	    
	    $this->response->addHeader('Content-Type: application/json');
	    $this->response->setOutput(json_encode($json));
	}
	
}