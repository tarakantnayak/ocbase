<?php
class ModelDesignBanner extends Model {
	public function addBanner($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "banner SET name = '" . $this->db->escape($data['name']) . "', status = '" . (int)$data['status'] . "'");

		$banner_id = $this->db->getLastId();

		if (isset($data['banner_image'])) {
			foreach ($data['banner_image'] as $language_id => $value) {
				foreach ($value as $banner_image) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "banner_image SET banner_id = '" . (int)$banner_id . "', language_id = '" . (int)$language_id . "', title = '" .  $this->db->escape($banner_image['title']) . "', link = '" .  $this->db->escape($banner_image['link']) . "', image = '" .  $this->db->escape($banner_image['image']) . "', sort_order = '" .  (int)$banner_image['sort_order'] . "', image_width = '" .  (int)$banner_image['width'] . "', image_height = '" .  (int)$banner_image['height'] . "'");
				}
			}
		}

		return $banner_id;
	}

	public function editBanner($banner_id, $data) {
	    $logger = new Log("edit_banner_m");
	    $logger->write("banner_id :".$banner_id);
	    $logger->write($data);
		$this->db->query("UPDATE " . DB_PREFIX . "banner SET name = '" . $this->db->escape($data['name']) . "', status = '" . (int)$data['status'] . "' WHERE banner_id = '" . (int)$banner_id . "'");

//		$this->db->query("DELETE FROM " . DB_PREFIX . "banner_image WHERE banner_id = '" . (int)$banner_id . "'");

		if (isset($data['banner_image'])) {
			foreach ($data['banner_image'] as $language_id => $value) {
				foreach ($value as $banner_image) {
				    $logger->write("DELETE FROM " . DB_PREFIX . "banner_image WHERE banner_image_id = '" . (int)$banner_image['banner_image_id'] . "'");
				    $this->db->query("DELETE FROM " . DB_PREFIX . "banner_image WHERE banner_image_id = '" . (int)$banner_image['banner_image_id'] . "'");
					$this->db->query("INSERT INTO " . DB_PREFIX . "banner_image SET banner_id = '" . (int)$banner_id . "', language_id = '" . (int)$language_id . "', title = '" .  $this->db->escape($banner_image['title']) . "', link = '" .  $this->db->escape($banner_image['link']) . "', image = '" .  $this->db->escape($banner_image['image']) . "', sort_order = '" . (int)$banner_image['sort_order'] . "', image_width = '" .  (int)$banner_image['width'] . "', image_height = '" .  (int)$banner_image['height'] . "'");
					$new_banner_image_id = $this->db->getLastId();
					$this->db->query("UPDATE " . DB_PREFIX . "banner_image_content SET  banner_image_id =  '" . (int)$new_banner_image_id . "' WHERE banner_image_id = '" . (int)$banner_image['banner_image_id'] . "'");
				}
			}			
			
			$logger->write("SELECT distinct banner_image_id FROM " . DB_PREFIX . "banner_image_content bic WHERE banner_image_id NOT IN (SELECT banner_image_id FROM " . DB_PREFIX . "banner_image)");
			$query = $this->db->query("SELECT distinct banner_image_id FROM " . DB_PREFIX . "banner_image_content bic WHERE banner_image_id NOT IN (SELECT banner_image_id FROM " . DB_PREFIX . "banner_image)");

			foreach ($query->rows as $result){
			    $logger->write("DELETE FROM " . DB_PREFIX . "banner_image_content WHERE banner_image_id = '" . $result['banner_image_id'] . "'");
			    $query = $this->db->query("DELETE FROM " . DB_PREFIX . "banner_image_content WHERE banner_image_id = '" . $result['banner_image_id'] . "'");
			}
		}
	}

	public function deleteBanner($banner_id) {
	    $logger = new Log("delete_banner_m");
		$this->db->query("DELETE FROM " . DB_PREFIX . "banner WHERE banner_id = '" . (int)$banner_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "banner_image WHERE banner_id = '" . (int)$banner_id . "'");

		$logger->write("SELECT distinct banner_image_id FROM " . DB_PREFIX . "banner_image_content bic WHERE banner_image_id NOT IN (SELECT banner_image_id FROM " . DB_PREFIX . "banner_image)");
		$query = $this->db->query("SELECT distinct banner_image_id FROM " . DB_PREFIX . "banner_image_content bic WHERE banner_image_id NOT IN (SELECT banner_image_id FROM " . DB_PREFIX . "banner_image)");
		
		foreach ($query->rows as $result){
		    $logger->write("DELETE FROM " . DB_PREFIX . "banner_image_content WHERE banner_image_id = '" . $result['banner_image_id'] . "'");
		    $query = $this->db->query("DELETE FROM " . DB_PREFIX . "banner_image_content WHERE banner_image_id = '" . $result['banner_image_id'] . "'");
		}
		
	}

	public function getBanner($banner_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "banner WHERE banner_id = '" . (int)$banner_id . "'");

		return $query->row;
	}

	public function getBanners($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "banner";

		$sort_data = array(
			'name',
			'status'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
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

	public function getBannerImages($banner_id) {
		$banner_image_data = array();

		$banner_image_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner_image WHERE banner_id = '" . (int)$banner_id . "' ORDER BY sort_order ASC");

		foreach ($banner_image_query->rows as $banner_image) {
			$banner_image_data[$banner_image['language_id']][] = array(
				'title'      => $banner_image['title'],
				'link'       => $banner_image['link'],
				'image'      => $banner_image['image'],
				'sort_order' => $banner_image['sort_order'],
			    'image_width' => $banner_image['image_width'],
			    'image_height' => $banner_image['image_height'],
			    'banner_image_id' => $banner_image['banner_image_id']
			);
		}

		return $banner_image_data;
	}

	public function getTotalBanners() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "banner");

		return $query->row['total'];
	}
	
	public function saveImageContent($content_data, $banner_image_id) {
	    $this->db->query("DELETE FROM " . DB_PREFIX . "banner_image_content WHERE banner_image_id = '" . (int)$banner_image_id . "'");

	    foreach ($content_data as $content) {
	        $this->db->query("INSERT INTO " . DB_PREFIX . "banner_image_content SET banner_image_id = '" . (int)$banner_image_id . "', sort_order = '" . (int)$content['sort_order'] . "', content = '" . $content['content'] . "'");
	    }
	}

	public function getImageContent($banner_image_id) {
	    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner_image_content WHERE banner_image_id = '" . (int)$banner_image_id . "' ORDER BY sort_order");
	    
	    return $query->rows;
	    
	}
	
}
