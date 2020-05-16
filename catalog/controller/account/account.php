<?php
class ControllerAccountAccount extends Controller {
	public function index() {
        $logger = new Log("account_index");
	    if (!$this->customer->isLogged()) {
	        $this->session->data['redirect'] = $this->url->link('account/account', '', true);
	        $this->response->redirect($this->url->link('account/login', '', true));
	    }
	    
	    $this->load->model('common/common');
	    $this->load->model('account/customer');
	    $this->load->model('account/address');
	    $this->load->model('localisation/country');
	    
	    $this->load->language('account/account');
	    $this->load->language('account/edit');
	    $this->load->language('account/password');
	    $this->load->language('account/address');
	    
	    $this->document->setTitle($this->language->get('heading_title'));

	    $data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		//banner
		$data['banner'] = $this->model_common_common->getDisplayImages('SLIDER', 1920, 300);
		
		
		/*************Profile starts******************/ 
		
		$customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
		
		$data['user_name'] = $customer_info['user_name'];
		$data['firstname'] = $customer_info['firstname'];
		$data['lastname'] = $customer_info['lastname'];
		$data['email'] = $customer_info['email'];
		$data['telephone'] = $customer_info['telephone'];
		
		/*************Profile ends******************/
		
		/*************address starts******************/		
		
		//address list
		$data['addresses'] = array();
		
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
		        'address'    => str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format)))),
		        'update'     => $this->url->link('account/address/edit', 'address_id=' . $result['address_id'], true),
		        'delete'     => $this->url->link('account/address/delete', 'address_id=' . $result['address_id'], true)
		    );
		    
		    $data['edit_addresses'][$result['address_id']] = array(
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
		    
		    
		}
		
		if (ISSET($this->request->get['display_block'])){
		    $data['display_block'] = $this->request->get['display_block'];
		} else {
		    $data['display_block'] = '';
		}
		
		
		//address form
		$data['text_address'] = !isset($this->request->get['address_id']) ? $this->language->get('text_address_add') : $this->language->get('text_address_edit');

		/*
		if (isset($this->error['firstname'])) {
		    $data['error_firstname'] = $this->error['firstname'];
		} else {
		    $data['error_firstname'] = '';
		}
		
		if (isset($this->error['lastname'])) {
		    $data['error_lastname'] = $this->error['lastname'];
		} else {
		    $data['error_lastname'] = '';
		}
		
		if (isset($this->error['address_1'])) {
		    $data['error_address_1'] = $this->error['address_1'];
		} else {
		    $data['error_address_1'] = '';
		}
		
		if (isset($this->error['city'])) {
		    $data['error_city'] = $this->error['city'];
		} else {
		    $data['error_city'] = '';
		}
		
		if (isset($this->error['postcode'])) {
		    $data['error_postcode'] = $this->error['postcode'];
		} else {
		    $data['error_postcode'] = '';
		}
		
		if (isset($this->error['country'])) {
		    $data['error_country'] = $this->error['country'];
		} else {
		    $data['error_country'] = '';
		}
		
		if (isset($this->error['zone'])) {
		    $data['error_zone'] = $this->error['zone'];
		} else {
		    $data['error_zone'] = '';
		}
		*/
		$data['add_firstname'] = '';
		$data['add_lastname'] = '';
		$data['address_1'] = '';
		$data['address_2'] = '';
		$data['postcode'] = '';
		$data['city'] = '';
		$data['country_id'] = $this->config->get('config_country_id');
		$data['zone_id'] = '';
		
		
		$data['countries'] = $this->model_localisation_country->getCountries();
		
		
		
		/*************address ends******************/
		
		
		
		
		if ($this->config->get('total_reward_status')) {
			$data['reward'] = $this->url->link('account/reward', '', true);
		} else {
			$data['reward'] = '';
		}		
		
		
		
		$affiliate_info = $this->model_account_customer->getAffiliate($this->customer->getId());
		
		if (!$affiliate_info) {	
			$data['affiliate'] = $this->url->link('account/affiliate/add', '', true);
		} else {
			$data['affiliate'] = $this->url->link('account/affiliate/edit', '', true);
		}
		
		if ($affiliate_info) {		
			$data['tracking'] = $this->url->link('account/tracking', '', true);
		} else {
			$data['tracking'] = '';
		}
		
		$logger->write($data);
		
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		
		$this->response->setOutput($this->load->view('account/account', $data));
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
	public function saveProfile() {
	    $logger = new Log("saveProfile");
	    
	    $this->load->model('account/customer');
	    $this->load->language('account/edit');
	    
	    $logger->write("post message");
	    $logger->write($this->request->post);
	    $json = array();
	    
	    
	    if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
	        $json['error'][] = $this->language->get('error_firstname');
	    }
	    
	    if ((utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) {
	        $json['error'][] = $this->language->get('error_lastname');
	    }
	    
	    if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
	        $json['error'][] = $this->language->get('error_email');
	    }
	    
	    if (($this->customer->getEmail() != $this->request->post['email']) && $this->model_account_customer->getTotalCustomersByEmail($this->request->post['email'])) {
	        $json['error'][] = $this->language->get('error_exists');
	    }
	    
	    if ((utf8_strlen($this->request->post['telephone']) != 10)) {
	        $json['error'][] = $this->language->get('error_telephone');
	    }
	    
	    if (!isset($json['error'])) {
	        $this->load->model('catalog/review');
	        
	        $this->model_account_customer->editCustomer($this->customer->getId(), $this->request->post);
	        
	        $json['success'] = $this->language->get('text_success');
	    }
	    
	    $logger->write("json message");
	    $logger->write($json);
	    
	    
	    $this->response->addHeader('Content-Type: application/json');
	    $this->response->setOutput(json_encode($json));
	}
	
	public function changePassword() {
	    $logger = new Log("changePassword");
	    
	    $this->load->model('account/customer');
	    $this->load->language('account/password');
	    
	    $logger->write("post message");
	    $logger->write($this->request->post);
	    $json = array();
	    
	    
	    if ((utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
	        $json['error'][] = $this->language->get('error_password');
	    }
	    
	    if ($this->request->post['confirm'] != $this->request->post['password']) {
	        $json['error'][] = $this->language->get('error_confirm');
	    }
	    
	    if (!isset($json['error'])) {
	    
	        $this->model_account_customer->editPassword($this->customer->getUserName(), $this->request->post['password']);	        
	        $json['success'] = $this->language->get('text_success');
	    }
	    
	    $logger->write("json message");
	    $logger->write($json);
	    
	    
	    $this->response->addHeader('Content-Type: application/json');
	    $this->response->setOutput(json_encode($json));
	}
	
	public function saveAddress() {
	    $logger = new Log("saveAddress");
	    
	    $this->load->model('account/address');
	    $this->load->language('account/address');
	    
	    $logger->write("post message");
	    $logger->write($this->request->post);
	    $json = array();
	    
	    $address_id = $this->request->get['address_id'];
	    
	    if (ISSET($this->request->post['postcode'.$address_id])){
	       $this->request->post['postcode'] = $this->request->post['postcode'.$address_id];
	    }
	    
	    if (ISSET($this->request->post['zone_id'.$address_id])){
	       $this->request->post['zone_id'] = $this->request->post['zone_id'.$address_id];
	    }
	    
	    $this->request->post['company'] = '';

	    if ((utf8_strlen(trim($this->request->post['add_firstname'])) < 1) || (utf8_strlen(trim($this->request->post['add_firstname'])) > 32)) {
	        $json['error'][] = $this->language->get('error_firstname');
	    }
	    
	    if ((utf8_strlen(trim($this->request->post['add_lastname'])) < 1) || (utf8_strlen(trim($this->request->post['add_lastname'])) > 32)) {
	        $json['error'][] = $this->language->get('error_lastname');
	    }
	    
	    if ((utf8_strlen(trim($this->request->post['address_1'])) < 3) || (utf8_strlen(trim($this->request->post['address_1'])) > 128)) {
	        $json['error'][] = $this->language->get('error_address_1');
	    }
	    
	    if ((utf8_strlen(trim($this->request->post['city'])) < 2) || (utf8_strlen(trim($this->request->post['city'])) > 128)) {
	        $json['error'][] = $this->language->get('error_city');
	    }
	    
	    $this->load->model('localisation/country');
	    
	    $country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);
	    
	    if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($this->request->post['postcode'])) < 2 || utf8_strlen(trim($this->request->post['postcode'])) > 10)) {
	        $json['error'][] = $this->language->get('error_postcode');
	    }
	    
	    if ($this->request->post['country_id'] == '' || !is_numeric($this->request->post['country_id'])) {
	        $json['error'][] = $this->language->get('error_country');
	    }
	    
	    if (!isset($this->request->post['zone_id']) || $this->request->post['zone_id'] == '' || !is_numeric($this->request->post['zone_id'])) {
	        $json['error'][] = $this->language->get('error_zone');
	    }

	    $logger->write("all good till here");
	    
	    if (!isset($json['error'])) {
	        $logger->write("no error");
	        $this->request->post['firstname'] = $this->request->post['add_firstname'];
	        $this->request->post['lastname'] = $this->request->post['add_lastname'];
	        
	        if ($address_id == 0){
    	       $this->model_account_address->addAddress($this->customer->getId(), $this->request->post);
	        } else {
	            $this->model_account_address->editAddress($address_id, $this->request->post);
	        }
    	    $json['success'] = $this->language->get('text_add');
    	    $json['redirect'] = $this->url->link('account/account', '', true);
    	    $json['redirect_chkout'] = $this->url->link('checkout/checkout', '', true);
	    }
	    
   	    $logger->write("json message");
	    $logger->write($json);
	    
	    
	    $this->response->addHeader('Content-Type: application/json');
	    $this->response->setOutput(json_encode($json));
	}
	
}
