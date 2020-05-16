<?php
class ModelCommonCommon extends Model {
    
    public function getBannerImages($banner_name) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner b, " . DB_PREFIX . "banner_image bi WHERE b.banner_id = bi.banner_id AND b.status = 1 AND b.name = '" . $banner_name . "'");
        
        return $query->rows;
        
    }
    
    public function getImageContent($banner_image_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner_image_content WHERE banner_image_id = '" . $banner_image_id . "' ORDER BY sort_order");
        
        return $query->rows;
        
    }
    
    public function getSetProducts($name, $code) {
        $logger = new Log("getSetProducts");
        
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "module WHERE name = '" . $name . "' AND code = '". $code ."'");
        $setting = $query->row['setting'];
        
        $logger->write($setting);
        
        $setting_array = json_decode($setting, true);
        
        $logger->write($setting_array);
        
        $product_info = array();
        
        foreach ($setting_array['product'] as $product_id) {
            $product_info[] = $this->model_catalog_product->getProduct($product_id);
        }
        
        return $product_info;
    }
    
    public function getProductData($data){
        
        $this->load->model('tool/image');
        
        $products = array();
        
        foreach ($data as $result) {
            
            $images = array();
            
            $product_images = $this->model_catalog_product->getProductImages($result['product_id']);
            
            if (!empty($product_images)){
                foreach ($product_images as $img) {
                    $images[] = array(
                        'image_thumb' => $this->model_tool_image->resize($img['image'], 45, 55),
                        'image_large' => $this->model_tool_image->resize($img['image'], 900, 1024),
                        'image_medium' => $this->model_tool_image->resize($img['image'], 250, 300)
                    );
                }
            } else {
                $images[] = array(
                    'image_thumb' => $this->model_tool_image->resize('placeholder.png', 45, 55),
                    'image_large' => $this->model_tool_image->resize('placeholder.png', 900, 1024),
                    'image_medium' => $this->model_tool_image->resize('placeholder.png', 250, 300)
                );
            }
            
            $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            
            if ((float)$result['special']) {
                $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $special = false;
            }
            
            if ($this->config->get('config_tax')) {
                $tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
            } else {
                $tax = false;
            }
            
            if ($this->config->get('config_review_status')) {
                $rating = (int)$result['rating'];
            } else {
                $rating = false;
            }
            
            $products[] = array(
                'product_id'  => $result['product_id'],
                'images'       => $images,
                'name'        => $result['name'],
                'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                'price'       => $price,
                'special'     => $special,
                'tax'         => $tax,
                'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
                'rating'      => $result['rating'],
                'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'] )
            );
        }
        
        return $products;
    }
    
    public function getRecentViews(){
        $this->load->model('catalog/product');
        
        if ($this->customer->isLogged()){
            $customer_id = $this->customer->isLogged();
        } else {
            $customer_id = 0;
        }
        
        $session_id = $this->session->getId();
        
        if ($customer_id > 0){
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_product_view WHERE customer_id = '" . $customer_id . "' ORDER BY view_date_time DESC LIMIT 6");
        } else {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_product_view WHERE session_id = '" . $session_id . "'  ORDER BY view_date_time DESC LIMIT 6");
        }
        
        $product_data = array();
        
        foreach ($query->rows as $result) {
            $product_data[$result['product_id']] = $this->model_catalog_product->getProduct($result['product_id']);
        }
        
        return $product_data;
       
    }
    
    public function getDisplayImages($banner_name, $width, $height){
        $logger = new Log("getDisplayImages");
        
        $this->load->model('tool/image');
        $banners = $this->getBannerImages($banner_name);
        
        $logger->write($banners);
        
        $banner_data = array();
        
        $count = 0;
        foreach ($banners as $result){
            
            if ($result['image']) {
                $image = $this->model_tool_image->resize($result['image'], 1920, 300);
            } else {
                $image = $this->model_tool_image->resize('placeholder.png', 1920, 300);
            }
            $image_content = $this->getImageContent($result['banner_image_id']);
            
            $image_content_data = array();
            
            foreach ($image_content as $content){
                $image_content_data[] = $content['content'];
            }
            
            $banner_data[] = array(
                'image' => $image,
                'content' => $image_content_data
            );
            
            $count++;
            
        }
        
        $logger->write("count:".$count);
        
        $logger->write($banner_data);
        
        if ($count > 0){
        
            $rand = rand(0, $count-1);
            
            $logger->write("rand:".$rand);
            
            
            $banner_array['image'] = $banner_data[$rand]['image'];
            if ($banner_data[$rand]['content']){
                $logger->write("heee 1");
                $banner_array['content'] = $banner_data[$rand]['content'][0];
            } else {
                $logger->write("heee 2");
                $banner_array['content'] = 'Awesome Products';
            }
            
        
        } else {
            $banner_array['image'] = $this->model_tool_image->resize('placeholder.png', 1920, 300);
            $banner_array['content'] = 'Awesome Products';
        }
        
        $logger->write($banner_array);
        
        return $banner_array;
        
    }
    
    public function sendSms($smstext, $mobile_no){
        
        $logger = New Log("sendSMS");
        $logger->write("Inside sendSMS");
        
        $data = $this->comlib->getSenderDetails();

        $senderid = $data['SENDER_ID'];
        $senderidapikey = $data['SENDER_API_KEY'];
        $api_partner = $data['API_PARTNER'];
        
        $api_url = $api_partner.$senderidapikey.'&type=text&contacts='.$mobile_no.'&senderid='.$senderid.'&msg='.urlencode($smstext);
     //   $api_url ='http://softsms.in/app/smsapi/index.php?key='.$senderidapikey.'&type=text&contacts='.$mobile_no.'&senderid='.$senderid.'&msg='.urlencode($smstext);
        //$api_url ='http://api-alerts.solutionsinfini.com/v3/?method=sms.xml&api_key=A943f24b0b748bad767864c98f6ac26fa&xml='.urlencode($xmldata);
        $logger->write($api_url);
        $response = file_get_contents($api_url);
        $logger->write("response");
        $logger->write($response);
        
        
    }
    
    public function sendMail($message, $subject, $emailid){
        
        $logger=new Log("sendMail_model");
        $logger->write("inside send mail");
        
        $logger->write("message is:".$message);
        $logger->write("subject is:".$subject);
        $logger->write("emailid is:".$emailid);
        
        $mail = new Mail($this->config->get('config_mail_engine'));
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
        
        $mail->setTo($emailid);
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
        $mail->setSubject(sprintf($subject, html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8')));
        $mail->setText($message);
        $logger->write("printing mail parameter");
        $logger->write($mail);
        $mail->send();
        $logger->write("message sent success");
        
    }
    
    public function random($type, $length){
        
        if ($type == 'N'){ //Numeric only
            $string = "0123456789";
        } else if ($type == 'CO'){ //caps only
            $string = "ABCDEFGHIJKLMNOPQRSTUWXYZ";
        } else if ($type == 'SO') { //small only
            $string = "abcdefghijklmnopqrstuwxyz";
        } else if ($type == 'CN') { //capital numeric
            $string = "ABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        } else if ($type == 'SN') { //small NUMERIC
            $string = "abcdefghijklmnopqrstuwxyz0123456789";
        } else {
            $string = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        }
        
        $random = array(); //remember to declare $pass as an array
        $stringLength = strlen($string) - 1; //put the length -1 in cache
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $stringLength);
            $random[] = $string[$n];
        }
        return implode($random);
        
    }
    
    public function saveOTP($otp, $user_name, $user_name_type){
        $logger = New Log("saveOTP_mod");
        $logger->write("Inside saveOTP");
        
        $logger->write("SELECT * FROM " . DB_PREFIX . "customer WHERE email = '" . $user_name . "'");
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE email = '" . $user_name . "'");
        
        if (empty($query->row)){
            if ($user_name_type == 'M'){
                $logger->write("INSERT INTO " . DB_PREFIX . "customer SET store_id = '" . (int)$this->config->get('config_store_id') . "', language_id = '" . (int)$this->config->get('config_language_id') . "', email = '" . $user_name . "', telephone = '" . $user_name . "', ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "', date_added = NOW(), status = 1, user_name_type= '". $user_name_type ."'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "customer SET store_id = '" . (int)$this->config->get('config_store_id') . "', language_id = '" . (int)$this->config->get('config_language_id') . "', email = '" . $user_name . "', telephone = '" . $user_name . "', ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "', date_added = NOW(), status = 1, user_name_type= '". $user_name_type ."'");
            } else {
                $logger->write("INSERT INTO " . DB_PREFIX . "customer SET store_id = '" . (int)$this->config->get('config_store_id') . "', language_id = '" . (int)$this->config->get('config_language_id') . "', email = '" . $user_name . "', ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "', date_added = NOW(), status = 1, user_name_type= '". $user_name_type ."'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "customer SET store_id = '" . (int)$this->config->get('config_store_id') . "', language_id = '" . (int)$this->config->get('config_language_id') . "', email = '" . $user_name . "', ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "', date_added = NOW(), status = 1, user_name_type= '". $user_name_type ."'");                
            }
            $customer_id = $this->db->getLastId();
        } else {
            $customer_id = $query->row['customer_id'];
        }
        
        $logger->write("INSERT INTO " . DB_PREFIX . "customer_otp SET customer_id = '" . $customer_id . "', otp = '" . $otp . "', date_added = NOW(), status = 0");
        $this->db->query("INSERT INTO " . DB_PREFIX . "customer_otp SET customer_id = '" . $customer_id . "', otp = '" . $otp . "', date_added = NOW(), status = 0");
        
        
        return $customer_id;
    }
    
    public function validateOTP($otp, $customer_id){
        $logger = New Log("validateOTP_mod");
        $logger->write("Inside validateOTP");
        
        $logger->write("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_otp WHERE customer_id = '" . (int)$customer_id . "' AND otp = '" . $otp . "' AND status = 0");
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_otp WHERE $customer_id = '" . (int)$customer_id . "' AND otp = '" . $otp . "' AND status = 0");
        
        if ($query->row['total'] > 0){
            $logger->write("UPDATE " . DB_PREFIX . "customer_otp SET no_of_attempts = no_of_attempts + 1, date_validated = NOW(), status = 1 WHERE customer_id = '" . (int)$customer_id . "' AND otp = '". $otp ."' AND status = 0");
            $this->db->query("UPDATE " . DB_PREFIX . "customer_otp SET no_of_attempts = no_of_attempts + 1, date_validated = NOW(), status = 1 WHERE customer_id = '" . (int)$customer_id . "' AND otp = '". $otp ."' AND status = 0");
            
            //login the customer
            $logger->write("before loggin in :".$customer_id);
            //$login = $this->customer->loginCustomer($customer_id);
            //$logger->write("after loggin in :". $login);
            $validated = 1;
        } else {
            $logger->write("UPDATE " . DB_PREFIX . "customer_otp SET no_of_attempts = no_of_attempts + 1 WHERE customer_id = '" . (int)$customer_id . "' AND otp = '". $otp ."' AND status = 0");
            $this->db->query("UPDATE " . DB_PREFIX . "customer_otp SET no_of_attempts = no_of_attempts + 1 WHERE customer_id = '" . (int)$customer_id . "' AND otp = '". $otp ."' AND status = 0");
            
            $validated = 0;
        }
        
        return $validated;
    }
    
    public function changePassword($password, $customer_id){
        $this->db->query("UPDATE " . DB_PREFIX . "customer SET salt = '" . $this->db->escape($salt = token(9)) . "', password = '" . $this->db->escape(sha1($salt . sha1($salt . sha1($password)))) . "', code = '' WHERE customer_id = '" . (int)$customer_id . "'");
        $login = $this->customer->loginCustomer($customer_id);
        return $login;
        
    }
    
    public function getCartData(){
        $this->load->model('tool/upload');
        
        $data['products'] = array();
        
        foreach ($this->cart->getProducts() as $product) {
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
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                );
            }
            
            $recurring = '';
            
            if ($product['recurring']) {
                $frequencies = array(
                    'day'        => $this->language->get('text_day'),
                    'week'       => $this->language->get('text_week'),
                    'semi_month' => $this->language->get('text_semi_month'),
                    'month'      => $this->language->get('text_month'),
                    'year'       => $this->language->get('text_year'),
                );
                
                if ($product['recurring']['trial']) {
                    $recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
                }
                
                if ($product['recurring']['duration']) {
                    $recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                } else {
                    $recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                }
            }
            $this->load->model('tool/image');
            
            if ($product['image']) {
                $data['thumb'] = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
            } else {
                $data['thumb'] = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
            }
            
            
            $data['products'][] = array(
                'cart_id'    => $product['cart_id'],
                'product_id' => $product['product_id'],
                'name'       => $product['name'],
                'model'      => $product['model'],
                'option'     => $option_data,
                'recurring'  => $recurring,
                'quantity'   => $product['quantity'],
                'subtract'   => $product['subtract'],
                'price'      => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
                'total'      => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'], $this->session->data['currency']),
                'href'       => $this->url->link('product/product', 'product_id=' . $product['product_id'])
            );
        }
        
        // Gift Voucher
        $data['vouchers'] = array();
        
        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $data['vouchers'][] = array(
                    'description' => $voucher['description'],
                    'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency'])
                );
            }
        }
        
        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0;
        
        // Because __call can not keep var references so we put them into an array.
        $total_data = array(
            'totals' => &$totals,
            'taxes'  => &$taxes,
            'total'  => &$total
        );
        
        $this->load->model('setting/extension');
        
        $sort_order = array();
        
        $results = $this->model_setting_extension->getExtensions('total');
        
        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
        }
        
        array_multisort($sort_order, SORT_ASC, $results);
        
        foreach ($results as $result) {
            if ($this->config->get('total_' . $result['code'] . '_status')) {
                $this->load->model('extension/total/' . $result['code']);
                
                // We have to put the totals in an array so that they pass by reference.
                $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
            }
        }
        
        $sort_order = array();
        
        foreach ($totals as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }
        
        array_multisort($sort_order, SORT_ASC, $totals);
        
        foreach ($totals as $total) {
            $data['totals'][] = array(
                'title' => $total['title'],
                'text'  => $this->currency->format($total['value'], $this->session->data['currency'])
            );
        }
        
        $cart_data['products'] = $data['products'];
        $cart_data['vouchers'] = $data['vouchers'];
        $cart_data['totals'] = $data['totals'];
        
        return $cart_data;
    }
    
}