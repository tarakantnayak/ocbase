<?php
class ControllerCommonMaintenance extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('common/maintenance');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('common/maintenance');

		$this->getMasterList();
	}



	protected function getMasterList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'maintenance_name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('common/maintenance', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['maintenances'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$maintenance_total = $this->model_common_maintenance->getTotalmaintenances();

		$results = $this->model_common_maintenance->getMaintenances($filter_data);

		foreach ($results as $result) {
			$values = $this->model_common_maintenance->getMaintenanceValues($result['maintenance_id']);
			$maintenance_values = '';
			$count = 0;
			foreach ($values as $value){
				$maintenance_values .= $value['maintenance_value'];
				$maintenance_values .= ', ';
				$count++;
			}


			$data['maintenances'][] = array(
				'maintenance_id'   => $result['maintenance_id'],
				'maintenance_name'         => $result['maintenance_name'],
				'partner_id'          => $result['partner_id'],
				'maintenance_values' => $maintenance_values,
				'count'				=> $count,
				'status'         => $result['status'],
				'date_modified' => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
				'get_detail_list'          => $this->url->link('common/maintenance/getDetailList', 'user_token=' . $this->session->data['user_token'] . '&maintenance_id=' . $result['maintenance_id']. '&maintenance_name=' . $result['maintenance_name'] . $url, true),
				'delete_maintenance'          => $this->url->link('common/maintenance/deleteMaintenance', 'user_token=' . $this->session->data['user_token'] . '&maintenance_id=' . $result['maintenance_id'] . $url, true)

			);
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_maintenance_name'] = $this->url->link('common/maintenance', 'user_token=' . $this->session->data['user_token'] . '&sort=maintenance_name' . $url, true);
		$data['sort_partner_id'] = $this->url->link('common/maintenance', 'user_token=' . $this->session->data['user_token'] . '&sort=partner_id' . $url, true);
		$data['sort_status'] = $this->url->link('common/maintenance', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);
		$data['sort_modified_date'] = $this->url->link('common/maintenance', 'user_token=' . $this->session->data['user_token'] . '&sort=modified_date' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $maintenance_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('common/maintenance', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($maintenance_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($maintenance_total - $this->config->get('config_limit_admin'))) ? $maintenance_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $maintenance_total, ceil($maintenance_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('common/maintenance_list', $data));
	}

	public function getDetailList() {

		$this->load->model('common/maintenance');
		$this->load->language('common/maintenance');
		$data['user_token'] = $this->session->data['user_token'];

		$data['cancel'] = $this->url->link('common/maintenance', 'user_token=' . $this->session->data['user_token'] , true);

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'maintenance_value';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('common/maintenance', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);


		$maintenance_id = $this->request->get['maintenance_id'];
		$data['maintenance_name'] = $this->request->get['maintenance_name'];
		$data['maintenance_id'] = $maintenance_id;
		$data['maintenance_values'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);


		$maintenance_value_total = $this->model_common_maintenance->getTotalmaintenanceValues($maintenance_id);

		$results = $this->model_common_maintenance->getMaintenanceValues($maintenance_id);


		foreach ($results as $result) {
			$data['maintenance_values'][] = array(
				'maintenance_detail_id'   => $result['maintenance_detail_id'],
				'maintenance_value'         => $result['maintenance_value'],
			    'short_name'         => $result['short_name'],
				'delete_value'          => $this->url->link('common/maintenance/deleteValue', 'user_token=' . $this->session->data['user_token'] . '&maintenance_detail_id=' . $result['maintenance_detail_id'] . '&maintenance_id=' . $maintenance_id . '&maintenance_name=' . $data['maintenance_name'] . $url, true)
			);
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_maintenance_value'] = $this->url->link('common/maintenance', 'user_token=' . $this->session->data['user_token'] . '&sort=maintenance_value' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $maintenance_value_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('common/maintenance', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($maintenance_value_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($maintenance_value_total - $this->config->get('config_limit_admin'))) ? $maintenance_value_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $maintenance_value_total, ceil($maintenance_value_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('common/maintenance_detail_list', $data));
	}

	public function deleteValue(){
		$this->load->model('common/maintenance');
		$this->load->language('common/maintenance');
		$data['user_token'] = $this->session->data['user_token'];

		$maintenance_detail_id = $this->request->get['maintenance_detail_id'];

		$this->model_common_maintenance->deleteMaintenanceValues($maintenance_detail_id);

		if (isset($this->request->get['maintenance_id'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$this->response->redirect($this->url->link('common/maintenance/getDetailList', 'user_token=' . $this->session->data['user_token'] .'&maintenance_id=' . $this->request->get['maintenance_id'] .'&maintenance_name=' . $this->request->get['maintenance_name'] , true));
	}

	public function deleteMaintenance(){
		$this->load->model('common/maintenance');
		$this->load->language('common/maintenance');
		$data['user_token'] = $this->session->data['user_token'];

		$maintenance_id = $this->request->get['maintenance_id'];

		$this->model_common_maintenance->deleteMaintenance($maintenance_id);

		$this->response->redirect($this->url->link('common/maintenance', 'user_token=' . $this->session->data['user_token'] , true));
	}

	public function saveMaintenance(){
		$logger = new Log("saveMaintenance_con");
		$logger->write("inside saveMaintenance...");

		$json = array();
		$this->load->model('common/maintenance');
		$maintenance_name = $this->request->get['maintenance_name'];

		if ((utf8_strlen(trim($maintenance_name)) < 3) || (utf8_strlen(trim($maintenance_name)) > 100)) {
			$json['message'] = 'Please Enter A ValidName between 3 and 100 characters!!';
			$json['value'] = 0;
		} else {
			$unique = $this->model_common_maintenance->checkUniqueMaintenance($maintenance_name);
			$logger->write("Unique :".$unique);
			if ($unique == 1){ //it's a unique value, save it
				$maintenance_id = $this->model_common_maintenance->saveMaintenance($maintenance_name);
				$logger->write("maintenance_id :".$maintenance_id);
				$json['message'] = 'Successfully Added A New Maintenance Name, Please Add The Corresponding Values!!';
				$json['value'] = 1;
			} else { //it's not unique, throw an error
				$json['message'] = 'Please Enter A Valid & Unique Name between 3 and 100 characters!!';
				$json['value'] = 0;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
	  $this->response->setOutput(json_encode($json));
	}

	public function editMaintenance(){
		$logger = new Log("editMaintenance_con");
		$logger->write("inside editMaintenance...");

		$json = array();
		$this->load->model('common/maintenance');
		$maintenance_name = $this->request->get['maintenance_name'];
		$maintenance_id = $this->request->get['maintenance_id'];

		if ((utf8_strlen(trim($maintenance_name)) < 3) || (utf8_strlen(trim($maintenance_name)) > 100)) {
			$json['message'] = 'Please Enter A ValidName between 3 and 100 characters!!';
			$json['value'] = 0;
		} else {
			$unique = $this->model_common_maintenance->checkUniqueMaintenance($maintenance_name);
			$logger->write("Unique :".$unique);
			if ($unique == 1){ //it's a unique value, save it
				$this->model_common_maintenance->editMaintenance($maintenance_name, $maintenance_id);
				$logger->write("maintenance_id :".$maintenance_id);
				$json['message'] = 'Successfully Edited The Maintenance Name!!';
				$json['value'] = 1;
			} else { //it's not unique, throw an error
				$json['message'] = 'Please Enter A Valid & Unique Name between 3 and 100 characters!!';
				$json['value'] = 0;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function saveMaintenanceValue(){
		$logger = new Log("saveMaintenanceValue_con");
		$logger->write("inside saveMaintenanceValue...");

		$json = array();
		$this->load->model('common/maintenance');
		$maintenance_value = $this->request->get['maintenance_value'];
		$short_name = $this->request->get['short_name'];
		$maintenance_id = $this->request->get['maintenance_id'];

		if ((utf8_strlen(trim($maintenance_value)) < 3) || (utf8_strlen(trim($maintenance_value)) > 100)) {
			$json['message'] = 'Please Enter A Valid Value between 3 and 100 characters!!';
			$json['value'] = 0;
		} else {
			$unique = $this->model_common_maintenance->checkUniqueMaintenanceValueAdd($maintenance_value, $maintenance_id);
			$logger->write("Unique :".$unique);
			if ($unique == 1){ //it's a unique value, save it
				$maintenance_detail_id = $this->model_common_maintenance->saveMaintenanceValue($maintenance_value, $short_name, $maintenance_id);
				$logger->write("maintenance_detail_id :".$maintenance_detail_id);
				$json['message'] = 'Successfully Added A New Maintenance Value!!';
				$json['value'] = 1;
			} else { //it's not unique, throw an error
				$json['message'] = 'Please Enter A Valid & Unique Value between 3 and 100 characters!!';
				$json['value'] = 0;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
	  $this->response->setOutput(json_encode($json));
	}

	public function editMaintenanceValue(){
		$logger = new Log("editMaintenanceValue_con");
		$logger->write("inside editMaintenanceValue...");

		$json = array();
		$this->load->model('common/maintenance');
		$maintenance_value = $this->request->get['maintenance_value'];
		$short_name = $this->request->get['short_name'];
		$maintenance_detail_id = $this->request->get['maintenance_detail_id'];
		$maintenance_id = $this->request->get['maintenance_id'];

		$logger->write("short_name :".$short_name);
		
		if ((utf8_strlen(trim($maintenance_value)) < 3) || (utf8_strlen(trim($maintenance_value)) > 100)) {
			$json['message'] = 'Please Enter A Valid Value between 3 and 100 characters!!';
			$json['value'] = 0;
		} else {
		    $unique = $this->model_common_maintenance->checkUniqueMaintenanceValueEdit($maintenance_value, $maintenance_id, $maintenance_detail_id);
			$logger->write("Unique :".$unique);
			if ($unique == 1){ //it's a unique value, save it
			    $this->model_common_maintenance->editMaintenanceValue($maintenance_value, $short_name,  $maintenance_detail_id);
				$json['message'] = 'Successfully Edited The Maintenance Value!!';
				$json['value'] = 1;
			} else { //it's not unique, throw an error
				$json['message'] = 'Please Enter A Valid & Unique Value between 3 and 100 characters!!';
				$json['value'] = 0;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}



}
