<?php
class ControllerCommonHeader extends Controller {
	public function index() {
		// Analytics
		$logger = new Log("header");
	    
	    $this->load->model('setting/extension');
	    $this->load->model('tool/image');
	    
	    
	    $this->load->language('common/search');
	    
	    $data['text_search'] = $this->language->get('text_search');
	    
	    if (isset($this->request->get['search'])) {
	        $data['search'] = $this->request->get['search'];
	    } else {
	        $data['search'] = '';
	    }

	    //$data['action'] = $this->url->link('product/search', 'search='.$this->request->get['search'], true);
	    $data['action'] = '';
	    
	    $data['analytics'] = array();

		$analytics = $this->model_setting_extension->getExtensions('analytics');

		foreach ($analytics as $analytic) {
			if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
				$data['analytics'][] = $this->load->controller('extension/analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
			}
		}

		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
		}

		$data['title'] = $this->document->getTitle();

		$data['base'] = $server;
		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts('header');
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		$data['name'] = $this->config->get('config_name');

		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $server . 'image/' . $this->config->get('config_logo');
		} else {
			$data['logo'] = '';
		}

		$this->load->language('common/header');

		// Wishlist
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), $this->model_account_wishlist->getTotalWishlist());
		} else {
			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0));
		}

		$data['text_logged'] = sprintf($this->language->get('text_logged'), $this->url->link('account/account', '', true), $this->customer->getFirstName(), $this->url->link('account/logout', '', true));
		
		$data['home'] = $this->url->link('common/home');
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['logged'] = $this->customer->isLogged();
		$data['account'] = $this->url->link('account/account', '', true);
		$data['register'] = $this->url->link('account/register', '', true);
		$data['login'] = $this->url->link('account/login', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['transaction'] = $this->url->link('account/transaction', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		$data['logout'] = $this->url->link('account/logout', '', true);
		$data['shopping_cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		$data['contact'] = $this->url->link('information/contact');
		$data['telephone'] = $this->config->get('config_telephone');
		
		//$data['language'] = $this->load->controller('common/language');
		//$data['currency'] = $this->load->controller('common/currency');
		//$data['search'] = $this->load->controller('common/search');
		//$data['cart'] = $this->load->controller('common/cart');
		//$data['menu'] = $this->load->controller('common/menu');

		//menu
		
		
		$this->load->model('catalog/category');
		
		$this->load->model('catalog/product');
		
		$data['categories'] = array();
		
		$categories = $this->model_catalog_category->getCategories(0);
		
		foreach ($categories as $category) {
		    if ($category['top']) {
		        // Level 2
		        $children_data = array();
		        
		        $children = $this->model_catalog_category->getCategories($category['category_id']);
		        
		        foreach ($children as $child) {
		            $filter_data = array(
		                'filter_category_id'  => $child['category_id'],
		                'filter_sub_category' => true
		            );
		            
		            $children_data[] = array(
		                'name'  => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
		                'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
		            );
		        }
		        
		        // Level 1
		        $data['categories'][] = array(
		            'name'     => $category['name'],
		            'children' => $children_data,
		            'column'   => $category['column'] ? $category['column'] : 1,
		            'href'     => $this->url->link('product/category', 'path=' . $category['category_id'])
		        );
		    }
		}
		
		
		$logger->write($data['categories']);
		
		//cart products
		
		$data['products'] = array();
		
		foreach ($this->cart->getProducts() as $product) {
		    if ($product['image']) {
		        $image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
		    } else {
		        $image = '';
		    }
		    
		    $option_data = array();
		    
		    foreach ($product['option'] as $option) {
		        if ($option['type'] != 'file') {
		            $value = $option['value'];
		        } else {
		            $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);
		            
		            if ($upload_info) {
		                $value = $upload_info['name'];
		            } else {
		                $value = '';
		            }
		        }
		        
		        $option_data[] = array(
		            'name'  => $option['name'],
		            'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value),
		            'type'  => $option['type']
		        );
		    }
		    
		    // Display prices
		    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
		        $unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
		        
		        $price = $this->currency->format($unit_price, $this->session->data['currency']);
		        $total = $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']);
		    } else {
		        $price = false;
		        $total = false;
		    }
		    
		    $data['products'][] = array(
		        'cart_id'   => $product['cart_id'],
		        'thumb'     => $image,
		        'name'      => $product['name'],
		        'model'     => $product['model'],
		        'option'    => $option_data,
		        'recurring' => ($product['recurring'] ? $product['recurring']['name'] : ''),
		        'quantity'  => $product['quantity'],
		        'price'     => $price,
		        'total'     => $total,
		        'href'      => $this->url->link('product/product', 'product_id=' . $product['product_id'])
		    );
		}
		
		
		return $this->load->view('common/header', $data);
	}
}
