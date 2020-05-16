<?php
class ModelCommonMaintenance extends Model {
	public function getTotalmaintenances() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "maintenance");

		return $query->row['total'];
	}

	public function getMaintenances($data = array()) {
			$sql = "SELECT * FROM " . DB_PREFIX . "maintenance";

			$sort_data = array(
				'maintenance_name',
				'partner_id',
				'status',
				'modified_date'
			);

			if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
				$sql .= " ORDER BY " . $data['sort'];
			} else {
				$sql .= " ORDER BY maintenance_name";
			}

			if (isset($data['order']) && ($data['order'] == 'DESC')) {
				$sql .= " DESC";
			} else {
				$sql .= " ASC";
			}

			if (isset($data['start']) || isset($data['limit'])) {
				if ($data['start'] < 0) {
					$data['start'] = 0;
				}

				if ($data['limit'] < 1) {
					$data['limit'] = 20;
				}

				$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
			}

			$query = $this->db->query($sql);

			return $query->rows;

	}

	public function getMaintenanceValues($maintenance_id){
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "maintenance_detail WHERE maintenance_id = '" . $maintenance_id . "'");

		return $query->rows;
	}

	public function getTotalmaintenanceValues($maintenance_id){
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "maintenance_detail WHERE maintenance_id = '" . $maintenance_id . "'");

		return $query->row['total'];
	}

	public function saveMaintenance($maintenance_name) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "maintenance SET maintenance_name = '" . $this->db->escape($maintenance_name) . "', partner_id = 0, status = 1, date_modified = NOW(), date_added = NOW()");
		$maintenance_id = $this->db->getLastId();
		return $maintenance_id;
	}

	public function saveMaintenanceValue($maintenance_value, $short_name, $maintenance_id) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "maintenance_detail SET maintenance_value = '" . $this->db->escape($maintenance_value) . "', short_name = '" . $this->db->escape($short_name) . "', maintenance_id = '". $maintenance_id ."'");
		$maintenance_detail_id = $this->db->getLastId();
		return $maintenance_detail_id;
	}




	public function editMaintenance($maintenance_name, $maintenance_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "maintenance SET maintenance_name = '" . $this->db->escape($maintenance_name) . "' WHERE maintenance_id = '" . (int)$maintenance_id . "'");
	}

	public function editMaintenanceValue($maintenance_value, $short_name, $maintenance_detail_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "maintenance_detail SET maintenance_value = '" . $this->db->escape($maintenance_value) . "', short_name = '" . $this->db->escape($short_name) . "' WHERE maintenance_detail_id = '" . (int)$maintenance_detail_id . "'");
	}

	public function deleteMaintenance($maintenance_id){
		$this->db->query("DELETE FROM " . DB_PREFIX . "maintenance WHERE maintenance_id = '" . (int)$maintenance_id . "'");
	}

	public function deleteMaintenanceValues($maintenance_detail_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "maintenance_detail WHERE maintenance_detail_id = '" . (int)$maintenance_detail_id . "'");
	}

	public function getMaintenance($maintenance_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "maintenance WHERE maintenance_id = '" . (int)$maintenance_id . "'");

		return $query->row;
	}


	public function checkUniqueMaintenance($maintenance_name){
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "maintenance WHERE maintenance_name = '" . $this->db->escape($maintenance_name) . "'");
		if ($query->row['total'] > 0){
			return 0;
		} else {
			return 1;
		}

	}

	public function checkUniqueMaintenanceValueAdd($maintenance_value, $maintenance_id){
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "maintenance_detail WHERE maintenance_value = '" . $this->db->escape($maintenance_value) . "' AND maintenance_id = '" . $maintenance_id . "'");
		if ($query->row['total'] > 0){
			return 0;
		} else {
			return 1;
		}

	}

	public function checkUniqueMaintenanceValueEdit($maintenance_value, $maintenance_id, $maintenance_detail_id){
	    $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "maintenance_detail WHERE maintenance_value = '" . $this->db->escape($maintenance_value) . "' AND maintenance_id = '" . $maintenance_id . "' AND maintenance_detail_id != '" . $maintenance_detail_id . "'");
	    if ($query->row['total'] > 0){
	        return 0;
	    } else {
	        return 1;
	    }
	    
	}
	


}
