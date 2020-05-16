<?php
namespace Cart;
Use Log;

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class Comlib {
    
    public function __construct($registry) {
        $this->db = $registry->get('db');
        $this->request = $registry->get('request');
        $this->session = $registry->get('session');
        
    }
    
	public function getValues($maintenance_name){
	    $logger = new Log('getValues');

	    $logger->write("SELECT md.* FROM " . DB_PREFIX . "maintenance_detail md, " . DB_PREFIX . "maintenance m  WHERE m.maintenance_id = md.maintenance_id AND m.maintenance_name = '" . $maintenance_name . "'");
	    
	    $query = $this->db->query("SELECT md.* FROM " . DB_PREFIX . "maintenance_detail md, " . DB_PREFIX . "maintenance m  WHERE m.maintenance_id = md.maintenance_id AND m.maintenance_name = '" . $maintenance_name . "'");
	    return $query->rows;
	}
	
	
	public function getValueRow($maintenance_detail_id){
	    $logger = new Log('getValueRow');
	    $logger->write("SELECT * FROM " . DB_PREFIX . "maintenance_detail WHERE maintenance_detail_id = '" . $maintenance_detail_id . "'");
	    
	    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "maintenance_detail WHERE maintenance_detail_id = '" . $maintenance_detail_id . "'");
	    return $query->row;
	}
	
	public function getValue($maintenance_detail_id){
	    $logger = new Log('getValue');
	    $logger->write("SELECT * FROM " . DB_PREFIX . "maintenance_detail WHERE maintenance_detail_id = '" . $maintenance_detail_id . "'");
	    
	    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "maintenance_detail WHERE maintenance_detail_id = '" . $maintenance_detail_id . "'");
	    
	    if ($query->row){
	        return $query->row['maintenance_value'];
	    } else {
	        return 'Not Available';
	    }
	}
	
	public function getShortName($maintenance_detail_id){
	    $logger = new Log('getShortName');
	    $logger->write("SELECT short_name FROM " . DB_PREFIX . "maintenance_detail WHERE maintenance_detail_id = '" . $maintenance_detail_id . "'");
	    
	    $query = $this->db->query("SELECT short_name FROM " . DB_PREFIX . "maintenance_detail WHERE maintenance_detail_id = '" . $maintenance_detail_id . "'");
	    
	    if ($query->row){
	        return $query->row['short_name'];
	    } else {
	        return '';
	    }
	}
	
	public function getName($maintenance_id){
	    $logger = new Log('getName');
	    $logger->write("SELECT * FROM " . DB_PREFIX . "maintenance WHERE maintenance_id = '" . $maintenance_id . "'");
	    
	    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "maintenance WHERE maintenance_id = '" . $maintenance_id . "'");
	    return $query->row['maintenance_name'];
	}

	public function getMaintenanceDetailId($maintenance_name, $short_name){
	    $logger = new Log('getValues');
	    
	    $logger->write("SELECT md.* FROM " . DB_PREFIX . "maintenance_detail md, " . DB_PREFIX . "maintenance m  WHERE m.maintenance_id = md.maintenance_id AND m.maintenance_name = '" . $maintenance_name . "' AND md.short_name = '". $short_name ."'");
	    
	    $query = $this->db->query("SELECT md.* FROM " . DB_PREFIX . "maintenance_detail md, " . DB_PREFIX . "maintenance m  WHERE m.maintenance_id = md.maintenance_id AND m.maintenance_name = '" . $maintenance_name . "' AND md.short_name = '". $short_name ."'");
	    return $query->row['maintenance_detail_id'];
	}

	public function getPaymentData($invoice_info){
	    
	    $logger = new Log("getPayment_data");
	    $logger->write("inside getPayment_data");
	    
	    $razor_pay_details = $this->getValues('RAZORPAY');
	    
	    $keyId = '';
	    $keySecret = '';
	    $displayCurrency = '';
	    
	    $logger->write("printing razor_pay_details ");
	    
	    $logger->write($razor_pay_details);
	    
	    
	    foreach ($razor_pay_details as $result){
	        if ($result['maintenance_value'] == 'KEY_ID'){
	            $keyId = $result['short_name'];
	        }
	        if ($result['maintenance_value'] == 'KEY_SECRET'){
	            $keySecret = $result['short_name'];
	        }
	        if ($result['maintenance_value'] == 'DISPLAY_CURRENCY'){
	            $displayCurrency = $result['short_name'];
	        }
	    }
	    
	    $api = new Api($keyId, $keySecret);
	    
	    $logger->write($invoice_info);
	    
	    $orderData = [
	        'receipt'         => $invoice_info['invoice_id'],
	        'amount'          => $invoice_info['amount'] * 100, // 2000 rupees in paise
	        'currency'        => $displayCurrency,
	        'payment_capture' => 1 // auto capture
	    ];
	    
	    $logger->write("printing order data");
	    $logger->write($orderData);
	    
	    $razorpayOrder = $api->order->create($orderData);
	    
	    $logger->write("printing razor pay order");
	    $logger->write($razorpayOrder);
	    
	    $razorpayOrderId = $razorpayOrder['id'];
	    
	    $_SESSION['razorpay_order_id'] = $razorpayOrderId;
	    
	    $displayAmount = $amount = $orderData['amount'];
	    
	    
	    $data = [
	        "key"               => $keyId,
	        "amount"            => $amount,
	        "currency"            => $displayCurrency,
	        "displayAmount"   =>$displayAmount,
	        "name"              => $invoice_info['buyer_name'],
	        "description"       => $invoice_info['purpose'],
	        "image"             => "https://s29.postimg.org/r6dj1g85z/daft_punk.jpg",
	        "prefill"           => [
	            "name"              => $invoice_info['buyer_name'],
	            "email"             => $invoice_info['email'],
	            "contact"           => $invoice_info['phone'],
	        ],
	        "notes"             => [
	            "address"           => "Hello World",
	            "merchant_order_id" => $invoice_info['invoice_id'],
	        ],
	        "theme"             => [
	            "color"             => "#F37254"
	        ],
	        "order_id"          => $razorpayOrderId,
	    ];
	    
	    $logger->write(" printing data");
	    
	    $logger->write($data);
	    
	    return $data;
	    
	}

	public function getPaymentHtml($payment_data, $verify){
	    $logger = new Log("payment_data_lib");
	    $logger->write("inside payment_data_lib");

	    $action = $verify;
	    $src = 'https://checkout.razorpay.com/v1/checkout.js';
	    $key = $payment_data['key'];
	    $amount = $payment_data['amount'];
	    $ccy = $payment_data['currency'];
	    $rp_order_id = $payment_data['order_id'];
	    $name = $payment_data['name'];
	    $desc = $payment_data['description'];
	    $img = $payment_data['image'];
	    $email = $payment_data['prefill']['email'];
	    $contact = $payment_data['prefill']['contact'];
	    $color = $payment_data['theme']['color'];
	    $merchant_order_id = $payment_data['notes']['merchant_order_id'];
	    
	    
	    $html = '';
		
		$html .= '<form action="';
		$html .= $action;
		$html .= '" method="POST"> ';
		
		$html .= '<script ';
		
		$html .= 'src="';
		$html .= $src;
		$html .= '" ';
		
		$html .= 'data-key="';
		$html .= $key;
		$html .= '" ';
		
		$html .= 'data-amount="';
		$html .= $amount;
		$html .= '" ';
		
		$html .= 'data-currency="';
		$html .= $ccy;
		$html .= '" ';
		
		$html .= 'data-order_id="';
		$html .= $rp_order_id;
		$html .= '" ';
		
		$html .= 'data-buttontext="Review & Pay Securely" ';
		
		$html .= 'data-name="';
		$html .= $name;
		$html .= '" ';
		
		$html .= 'data-description="';
		$html .= $desc;
		$html .= '" ';
		
		$html .= 'data-image="';
		$html .= $img;
		$html .= '" ';
		
		$html .= 'data-prefill.name="';
		$html .= $name;
		$html .= '" ';
		
		$html .= 'data-prefill.email="';
		$html .= $email;
		$html .= '" ';
		
		$html .= 'data-prefill.contact="';
		$html .= $contact;
		$html .= '" ';
		
		
		$html .= 'data-theme.color="';
		$html .= $color;
		$html .= '" ';
		
		$html .= '></script> ';
		
		$html .= '<input type="hidden" name="order_id" value="';
		$html .= $merchant_order_id;
		$html .= '" > ';
		
		$html .= '</form>';

		$logger->write($html);
		
		return $html;

	}

	public function verifyPayment($order_id, $order_entity){
	    $logger = new Log("verify_payment_lib");
	    $logger->write("inside verify_payment_lib");
	    
	    $success = true;
	    
	    $error = "Payment Failed";
	    
	    $razor_pay_details = $this->getValues('RAZORPAY');
	    
	    foreach ($razor_pay_details as $result){
	        if ($result['maintenance_value'] == 'KEY_ID'){
	            $keyId = $result['short_name'];
	        }
	        if ($result['maintenance_value'] == 'KEY_SECRET'){
	            $keySecret = $result['short_name'];
	        }
	        if ($result['maintenance_value'] == 'DISPLAY_CURRENCY'){
	            $displayCurrency = $result['short_name'];
	        }
	    }
	    
	    
	    if (empty($_POST['razorpay_payment_id']) === false)
	    {
	        $api = new Api($keyId, $keySecret);
	        
	        try
	        {
	            // Please note that the razorpay order ID must
	            // come from a trusted source (session here, but
	            // could be database or something else)
	            $logger->write("Merchant Order id :".$order_id);
	            $logger->write("razorpay_order_id :".$_POST['razorpay_order_id']);
	            $logger->write("razorpay_payment_id :".$_POST['razorpay_payment_id']);
	            $logger->write("razorpay_signature :".$_POST['razorpay_signature']);
	            
	            
	            $attributes = array(
	                'razorpay_order_id' => $_POST['razorpay_order_id'],
	                'razorpay_payment_id' => $_POST['razorpay_payment_id'],
	                'razorpay_signature' => $_POST['razorpay_signature']
	            );
	            
	            $api->utility->verifyPaymentSignature($attributes);
	        }
	        catch(SignatureVerificationError $e)
	        {
	            $success = false;
	            $error = 'Razorpay Error : ' . $e->getMessage();
	        }
	    }
	    
	    $url = '';
	    
	    if ($success === true)
	    {
	        $this->logEntries($attributes, $order_id, $order_entity);
	        $status = 1;
	        
	        
	    } else {
	        $this->logError($error, $order_id, $order_entity);
	        $status = 0;
	    }
	    
	    return $status;
	}
	
	public function logEntries($attributes, $order_id, $order_entity){
	    $logger = new Log("logEntries");
	    
	    $logger->write("INSERT INTO " . DB_PREFIX . "log_entry SET invoice_id = '". $order_id ."', invoice_entity = '". $order_entity ."', payment_date = now(), razorpay_order_id = '". $attributes['razorpay_order_id'] ."', razorpay_payment_id = '". $attributes['razorpay_payment_id'] ."', razorpay_signature = '". $attributes['razorpay_signature'] ."', status = 1");
	    $this->db->query("INSERT INTO " . DB_PREFIX . "log_entry SET invoice_id = '". $order_id ."', invoice_entity = '". $order_entity ."', payment_date = now(), razorpay_order_id = '". $attributes['razorpay_order_id'] ."', razorpay_payment_id = '". $attributes['razorpay_payment_id'] ."', razorpay_signature = '". $attributes['razorpay_signature'] ."', status = 1");
	    
	}
	
	public function logError($error, $invoice_id, $invoice_entity){
	    $logger = new Log("logError");
	    
	    $logger->write("INSERT INTO " . DB_PREFIX . "log_error SET invoice_id = '". $order_id ."', invoice_entity = '". $order_entity ."', error_date = now(), error_message = '". $error ."'");
	    $this->db->query("INSERT INTO " . DB_PREFIX . "log_error SET invoice_id = '". $order_id ."', invoice_entity = '". $order_entity ."', error_date = now(), error_message = '". $error ."'");
	    
	}
	
	
}