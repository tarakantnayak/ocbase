<?php
class ControllerCommonHome extends Controller {
	public function index() {

	    $logger = new Log("home_index");
	    
	    $this->document->setTitle($this->config->get('config_meta_title'));
		$this->document->setDescription($this->config->get('config_meta_description'));
		$this->document->setKeywords($this->config->get('config_meta_keyword'));

		if (isset($this->request->get['route'])) {
			$this->document->addLink($this->config->get('config_url'), 'canonical');
		}

		$this->load->model('tool/image');

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('common/common');
		
		
		$results = $this->model_common_common->getBannerImages('SLIDER');
		
		$data['slider'] = array();
		
		foreach ($results as $result){
		    
		    if ($result['image']) {
		        $image = $this->model_tool_image->resize($result['image'], $result['image_width'], $result['image_height']);
		    } else {
		        $image = $this->model_tool_image->resize('placeholder.png', $result['image_width'], $result['image_height']);
		    }
		    
		    //$image = $result['image'];
		    $image_content = $this->model_common_common->getImageContent($result['banner_image_id']);
		    
		    $image_content_data = array();
		    
		    foreach ($image_content as $content){
		        $image_content_data[] = $content['content'];
		    }
		    
		    $data['slider'][] = array(
		        'image' => $image,
		        'link' => $result['link'],
		        'content' => $image_content_data
		    );
		    
		}
		
		
		
		$results = $this->model_common_common->getBannerImages('PROMO_BANNER');
		
		$data['promo_banner'] = array();
		
		foreach ($results as $result){
		    
		    if ($result['image']) {
		        $image = $this->model_tool_image->resize($result['image'], $result['image_width'], $result['image_height']);
		    } else {
		        $image = $this->model_tool_image->resize('placeholder.png', $result['image_width'], $result['image_height']);
		    }
		    
		    $image_content = $this->model_common_common->getImageContent($result['banner_image_id']);
		   
		    $image_content_data = array();
		    
		    foreach ($image_content as $content){
		        $image_content_data[] = $content['content'];
		    }

		    $data['promo_banner'][] = array(
		        'image' => $image,
		        'link' => $result['link'],
		        'content' => $image_content_data
		    );
		    
		}


		$results = $this->model_common_common->getBannerImages('LONG_BANNER');
		
		$data['long_banner'] = array();
		
		foreach ($results as $result){
		    
		    if ($result['image']) {
		        $image = $this->model_tool_image->resize($result['image'], $result['image_width'], $result['image_height']);
		    } else {
		        $image = $this->model_tool_image->resize('placeholder.png', $result['image_width'], $result['image_height']);
		    }
		    
		    //$image = $result['image'];
		    $image_content = $this->model_common_common->getImageContent($result['banner_image_id']);
		    
		    $image_content_data = array();
		    
		    foreach ($image_content as $content){
		        $image_content_data[] = $content['content'];
		    }
		    
		    $data['long_banner'][] = array(
		        'image' => $image,
		        'link' => $result['link'],
		        'content' => $image_content_data
		    );
		    
		}
		

		$results = $this->model_common_common->getBannerImages('TESTIMONIAL');
		
		$data['testimonial'] = array();
		
		foreach ($results as $result){
		    
		    if ($result['image']) {
		        $image = $this->model_tool_image->resize($result['image'], $result['image_width'], $result['image_height']);
		    } else {
		        $image = $this->model_tool_image->resize('placeholder.png', $result['image_width'], $result['image_height']);
		    }
		    
		    //$image = $result['image'];
		    $image_content = $this->model_common_common->getImageContent($result['banner_image_id']);
		    
		    $image_content_data = array();
		    
		    foreach ($image_content as $content){
		        $image_content_data[] = $content['content'];
		    }
		    
		    $data['testimonial'][] = array(
		        'image' => $image,
		        'link' => $result['link'],
		        'content' => $image_content_data
		    );
		    
		}
		

		$results = $this->model_common_common->getBannerImages('ENTREPRENEURS');
		
		$data['entrepreneurs'] = array();
		
		foreach ($results as $result){
		    
		    if ($result['image']) {
		        $image = $this->model_tool_image->resize($result['image'], $result['image_width'], $result['image_height']);
		    } else {
		        $image = $this->model_tool_image->resize('placeholder.png', $result['image_width'], $result['image_height']);
		    }
		    
		    //$image = $result['image'];
		    $image_content = $this->model_common_common->getImageContent($result['banner_image_id']);
		    
		    $image_content_data = array();
		    
		    foreach ($image_content as $content){
		        $image_content_data[] = $content['content'];
		    }
		    
		    $data['entrepreneurs'][] = array(
		        'image' => $image,
		        'link' => $result['link'],
		        'content' => $image_content_data
		    );
		    
		}
		
		
		
		//get categories
		
		
		$data['categories'] = array();
		
		$categories = $this->model_catalog_category->getCategories(0);
		
		foreach ($categories as $category) {
		    if ($category['top']) {
    		    $products = array();
    		
    		    $filter_data = array(
    		        'filter_category_id' => $category['category_id'],
    		        'filter_filter'      => '',
    		        'sort'               => '',
    		        'order'              => '',
    		        'start'              => 0,
    		        'limit'              => 8
    		    );
    		    
    		    
    		    //get category specific products
    		    $product_results = $this->model_catalog_product->getProducts($filter_data);    		    
    		    $products = $this->model_common_common->getProductData($product_results);
    		    
    		    
    	        $data['categories'][] = array(
    	            'name'     => $category['name'],
    	            'href'     => $this->url->link('product/category', 'path=' . $category['category_id']),
    	            'products' => $products    	        
    	            
    	        );
		    }
		}
		
		//get recent view products
		$recent_views = $this->model_common_common->getRecentViews();
		$data['recent']['products'] = $this->model_common_common->getProductData($recent_views);
		$data['recent']['href'] = '';
		
		//get best sellers (Popular)
		$bestseller_results = $this->model_catalog_product->getBestSellerProducts(8);
		$data['popular']['products'] = $this->model_common_common->getProductData($bestseller_results);
		$data['popular']['href'] = '';
		
		//get featured
		$featured_results = $this->model_common_common->getSetProducts('Featured', 'featured');
		$data['featured']['products'] = $this->model_common_common->getProductData($featured_results);
		$data['featured']['href'] = '';
		//get latest
		$filter_data = array(
		    'sort'  => 'p.date_added',
		    'order' => 'DESC',
		    'start' => 0,
		    'limit' => 8
		);
		
		$latest_results = $this->model_catalog_product->getProducts($filter_data);
		$data['latest']['products'] = $this->model_common_common->getProductData($latest_results);
		$data['latest']['href'] = '';
		
        $logger->write($data);		
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/home', $data));
	}
	
	
}
