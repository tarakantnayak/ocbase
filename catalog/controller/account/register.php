<?php
class ControllerAccountRegister extends Controller {
	private $error = array();

	public function index() {
	    $logger = new Log("register_index");
	    $this->load->model('common/common');
	    $data['banner'] = $this->model_common_common->getDisplayImages('HEADER_BANNER', 1920, 300);
		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$this->load->language('account/register');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

		$this->load->model('account/customer');

		if ($this->request->server['REQUEST_METHOD'] == 'POST'){
		    $logger->write("post");
		    $logger->write($this->request->post);
		} else {
		    $logger->write("not post");
		}
		
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
		    $logger->write("again printing post");
		    $logger->write($this->request->post);
		    
		    
			$customer_id = $this->model_account_customer->addCustomer($this->request->post);

			// Clear any previous login attempts for unregistered accounts.
			$this->model_account_customer->deleteLoginAttempts($this->request->post['email']);

			$this->customer->login($this->request->post['user_name'], $this->request->post['password']);

			unset($this->session->data['guest']);

			$this->response->redirect($this->url->link('account/success'));
		}


		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_register'),
			'href' => $this->url->link('account/register', '', true)
		);
		$data['text_account_already'] = sprintf($this->language->get('text_account_already'), $this->url->link('account/login', '', true));

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}


		if (isset($this->error['user_name'])) {
			$data['error_user_name'] = $this->error['user_name'];
		} else {
			$data['error_user_name'] = '';
		}

		if (isset($this->error['password'])) {
			$data['error_password'] = $this->error['password'];
		} else {
			$data['error_password'] = '';
		}


		$data['action'] = $this->url->link('account/register', '', true);

		$data['customer_groups'] = array();

		if (is_array($this->config->get('config_customer_group_display'))) {
			$this->load->model('account/customer_group');

			$customer_groups = $this->model_account_customer_group->getCustomerGroups();

			foreach ($customer_groups as $customer_group) {
				if (in_array($customer_group['customer_group_id'], $this->config->get('config_customer_group_display'))) {
					$data['customer_groups'][] = $customer_group;
				}
			}
		}

		if (isset($this->request->post['customer_group_id'])) {
			$data['customer_group_id'] = $this->request->post['customer_group_id'];
		} else {
			$data['customer_group_id'] = $this->config->get('config_customer_group_id');
		}

		if (isset($this->request->post['firstname'])) {
			$data['firstname'] = $this->request->post['firstname'];
		} else {
			$data['firstname'] = '';
		}

		if (isset($this->request->post['lastname'])) {
			$data['lastname'] = $this->request->post['lastname'];
		} else {
			$data['lastname'] = '';
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($this->request->post['telephone'])) {
			$data['telephone'] = $this->request->post['telephone'];
		} else {
			$data['telephone'] = '';
		}

		// Custom Fields
		$data['custom_fields'] = array();
		
		$this->load->model('account/custom_field');
		
		$custom_fields = $this->model_account_custom_field->getCustomFields();
		
		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'account') {
				$data['custom_fields'][] = $custom_field;
			}
		}
		
		if (isset($this->request->post['custom_field']['account'])) {
			$data['register_custom_field'] = $this->request->post['custom_field']['account'];
		} else {
			$data['register_custom_field'] = array();
		}

		if (isset($this->request->post['password'])) {
			$data['password'] = $this->request->post['password'];
		} else {
			$data['password'] = '';
		}

		if (isset($this->request->post['confirm'])) {
			$data['confirm'] = $this->request->post['confirm'];
		} else {
			$data['confirm'] = '';
		}

		if (isset($this->request->post['newsletter'])) {
			$data['newsletter'] = $this->request->post['newsletter'];
		} else {
			$data['newsletter'] = '';
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
		} else {
			$data['captcha'] = '';
		}

		if ($this->config->get('config_account_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

			if ($information_info) {
				$data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_account_id'), true), $information_info['title'], $information_info['title']);
			} else {
				$data['text_agree'] = '';
			}
		} else {
			$data['text_agree'] = '';
		}

		if (isset($this->request->post['agree'])) {
			$data['agree'] = $this->request->post['agree'];
		} else {
			$data['agree'] = false;
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/login', $data));
	}

	private function validate() {
	    $logger = new Log("register_validate");
	    $logger->write("printing error in the begining");
	    
	    $logger->write($this->error);
	    
	    $logger->write($this->request->post['user_name']);
	
	    $user_name = $this->request->post['user_name'];
	    
	    $user_name_type = 'X';
	    
	    if (is_numeric($user_name) == 1) {
	        if ((utf8_strlen($user_name) == 10)) {
	            $logger->write("1");
	            $user_name_type = 'M';
	        } else {
	            $logger->write("2");
	            $this->error['user_name'] = $this->language->get('error_user_name');
	        }
	    } else {
	        if ((utf8_strlen($user_name) > 96) || !filter_var($user_name, FILTER_VALIDATE_EMAIL)) {
	            $logger->write("3");
	            $this->error['user_name'] = $this->language->get('error_user_name');
	        } else {
	            $logger->write("4");
	            $user_name_type = 'E';
	        }
	    }
	    $logger->write("user_name_type is:".$user_name_type);
	    
	    if ($this->model_account_customer->getTotalCustomersByUsername($this->request->post['user_name'], $user_name_type)) {
	        $this->error['warning'] = $this->language->get('error_exists');
	    }

		// Customer Group
		if (isset($this->request->post['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->post['customer_group_id'], $this->config->get('config_customer_group_display'))) {
			$customer_group_id = $this->request->post['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		// Custom field validation
		$this->load->model('account/custom_field');

		$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'account') {
				if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
					$this->error['custom_field'][$custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
				} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
					$this->error['custom_field'][$custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
				}
			}
		}

		if ((utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
			$this->error['password'] = $this->language->get('error_password');
		}


		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
			$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

			if ($captcha) {
				$this->error['captcha'] = $captcha;
			}
		}

		// Agree to terms
		if ($this->config->get('config_account_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

			if ($information_info && !isset($this->request->post['agree'])) {
				$this->error['warning'] = sprintf($this->language->get('error_agree'), $information_info['title']);
			}
		}
		
		$this->request->post['user_name_type'] = $user_name_type;
		$logger->write("printing error in the end");
		
		$logger->write($this->error);
		
		
		return !$this->error;
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
	
	public function sendOTP() {
	    $logger = new Log("sendOTP");

	    $this->load->model('common/common');
	    $this->load->model('account/customer');
	    
	    $this->load->language('account/register');
	    $json = array();

	    
	    $user_name_type = 'X';
	    
	    
	    $user_name = $this->request->get['user_name'];

	    $logger->write("user_name :".$user_name);
	    
	    if (is_numeric($user_name) == 1) {
	        if ((utf8_strlen($user_name) == 10)) {
	            $user_name_type = 'M';
	        } else {
	            $json['error'] = $this->language->get('error_user_name');
	        }
	    } else {
	        if ((utf8_strlen($user_name) > 96) || !filter_var($user_name, FILTER_VALIDATE_EMAIL)) {
	            $logger->write("2");
	            $json['error'] = $this->language->get('error_user_name');
	        } else {
	            $user_name_type = 'E';
	        }	        
	    }
	    
	    $total = $this->model_account_customer->getTotalCustomersByUsername($user_name);
	   
	    if ($total == 0){
	       $json['error'] = $this->language->get('error_not_exist');
	       $json['register'] = $this->url->link('account/account', '', true);
	    }
	    
	    $logger->write("printing json");
	    $logger->write($json);
	    
	    if (!isset($json['error'])) {
	        
	        $logger->write("mobile no :".$user_name);
	        $this->load->model('common/common');
	        
	        $otp = $this->model_common_common->random('N',4);
	        
	        $logger->write("otp :".$otp);
	        $customer_id = $this->model_common_common->saveOTP($otp, $user_name, $user_name_type);
	        
	        $logger->write("customer_id :".$customer_id);
	        
    	    if ($user_name_type == 'M'){ //send sms
    	        $smstext = $otp . ' is your OTP. Please do not share with anyone';
    	        
    	        $this->model_common_common->sendSms($smstext, $user_name);
    	        
    	    } else { //send email
    	        $message = $otp . ' is your OTP. Please do not share with anyone';
    	        $subject = 'Confidential: OTP';
    	        $this->model_common_common->sendMail($message, $subject, $user_name);
    	    }
    	    $json['customer_id'] = $customer_id;
    	      
	    } 
	    
	    
	    $this->response->addHeader('Content-Type: application/json');
	    $this->response->setOutput(json_encode($json));
	    
	}
	
	public function validateOTP() {
	    $logger = new Log("validateOTP");
	    
	    $json = array();
	    $customer_id = $this->request->get['customer_id'];
	    $otp = $this->request->get['otp'];
	    
	    $logger->write("customer_id :".$customer_id);
	    $logger->write("otp :".$otp);
	    
	    
	    $this->load->model('common/common');
	    
	    $validated = $this->model_common_common->validateOTP($otp, $customer_id);
	    $logger->write("validated :".$validated);
	    
	    $json['customer_id'] = $customer_id;
	    if ($validated == 1){
	        $json['value'] = 1;
	        $json['message'] = 'OTP Validation successful';
	    } else {
	        $json['value'] = 0;
	        $json['error'] = 'Please enter the correct OTP';
	    }
	    
	    $this->response->addHeader('Content-Type: application/json');
	    $this->response->setOutput(json_encode($json));
	    
	}
	public function changePassword() {
	    $logger = new Log("changePassword");
	    
	    $json = array();
	    $customer_id = $this->request->get['customer_id'];
	    $password1 = $this->request->get['password1'];
	    $password2 = $this->request->get['password2'];
	    
	    $logger->write("customer_id :".$customer_id);
	    $logger->write("password1 :".$password1);
	    $logger->write("password2 :".$password2);
	    
	    if (!($password1) || !($password2) || ($password1 != $password2)){
	        $json['error'] = 'Both Passwords must have some value and they must be same';
	        
	    } else {
	        if ((utf8_strlen($password1) < 6) && (utf8_strlen($password1) > 10)){
	            $json['error'] = 'Password must be between 6 & 10 characters';
	        }
	    }
	    
	    if (!isset($json['error'])) {
    	    $this->load->model('common/common');
    	    //change password and login
    	    $this->model_common_common->changePassword($password1, $customer_id);
    	    $json['redirect'] = $this->url->link('account/account', '', true);
	    }
	    $this->response->addHeader('Content-Type: application/json');
	    $this->response->setOutput(json_encode($json));
	    
	}
	
	
}