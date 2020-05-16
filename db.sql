CREATE TABLE `home`.`oc_banner_image_content` ( `banner_image_content_id` INT NOT NULL AUTO_INCREMENT , `banner_image_id` INT NOT NULL , `content` VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , PRIMARY KEY (`banner_image_content_id`)) ENGINE = MyISAM;

ALTER TABLE `oc_banner_image` ADD `image_width` INT NOT NULL AFTER `sort_order`, ADD `image_height` INT NOT NULL AFTER `image_width`;

ALTER TABLE `oc_banner_image_content` ADD `sort_order` INT NOT NULL AFTER `content`;

CREATE TABLE `home`.`oc_customer_product_view` ( `customer_product_view_id` INT NOT NULL AUTO_INCREMENT , `customer_id` INT NOT NULL , `session_id` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `view_date_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , `product_id` INT NOT NULL , `customer_ip` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , PRIMARY KEY (`customer_product_view_id`)) ENGINE = MyISAM;

ALTER TABLE `oc_customer` ADD `user_name_type` CHAR(1) NOT NULL AFTER `date_added`;

CREATE TABLE IF NOT EXISTS `oc_customer_otp` (
  `customer_otp_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `otp` int(11) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_validated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `no_of_attempts` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL,
  PRIMARY KEY (`customer_otp_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
COMMIT;
ALTER TABLE `oc_customer` ADD `user_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `user_name_type`;

DROP TABLE IF EXISTS `oc_log_entry`;
CREATE TABLE IF NOT EXISTS `oc_log_entry` (
  `log_entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `invoice_entity` varchar(1) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `amount` float NOT NULL,
  `status` tinyint(4) NOT NULL,
  `razorpay_order_id` varchar(100) NOT NULL,
  `razorpay_payment_id` varchar(100) NOT NULL,
  `razorpay_signature` varchar(100) NOT NULL,
  PRIMARY KEY (`log_entry_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `oc_log_error`;
CREATE TABLE IF NOT EXISTS `oc_log_error` (
  `log_error_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `invoice_entity` varchar(1) NOT NULL,
  `error_message` varchar(500) NOT NULL,
  `error_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_error_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `oc_maintenance`;
CREATE TABLE IF NOT EXISTS `oc_maintenance` (
  `maintenance_id` int(11) NOT NULL AUTO_INCREMENT,
  `maintenance_name` varchar(100) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `date_added` timestamp NOT NULL,
  `created_by` int(11) NOT NULL,
  `date_modified` timestamp NOT NULL,
  `modified_by` int(11) NOT NULL,
  PRIMARY KEY (`maintenance_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `oc_maintenance_detail`;
CREATE TABLE IF NOT EXISTS `oc_maintenance_detail` (
  `maintenance_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `maintenance_id` int(11) NOT NULL,
  `maintenance_value` varchar(100) NOT NULL,
  `short_name` varchar(100) NOT NULL,
  PRIMARY KEY (`maintenance_detail_id`)
) ENGINE=MyISAM AUTO_INCREMENT=70 DEFAULT CHARSET=latin1;

/* create record for maintenance_name='RAZORPAY'
	maintenance_value, & short_name values as follows
	1. KEY_ID, 'give the key'
	2. KEY_SECRET, 'give the secret'
	3. DISPLAY_CURRENCY, 'INR'

*/

