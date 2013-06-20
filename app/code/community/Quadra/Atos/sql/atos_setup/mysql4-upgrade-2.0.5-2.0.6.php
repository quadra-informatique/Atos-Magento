<?php
/**
 * 1997-2012 Quadra Informatique
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to ecommerce@quadra-informatique.fr so we can send you a copy immediately.
 *
 *  @author Quadra Informatique <ecommerce@quadra-informatique.fr>
 *  @copyright 1997-2013 Quadra Informatique
 *  @version Release: $Revision: 2.1.2 $
 *  @license http://www.opensource.org/licenses/OSL-3.0  Open Software License (OSL 3.0)
 */

$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS `{$installer->getTable('atos/log_request')}` (
  `entity_id` int(10) unsigned NOT NULL auto_increment,
  `order_id` varchar(50) NOT NULL ,
  `send_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `merchant_id` varchar(50) NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `customer_ip_address` varchar(255) NOT NULL,
  `amount` int(50) unsigned NOT NULL default 0,
  `payment_means` varchar(50) NOT NULL,
  `capture_mode` varchar(50) NOT NULL default 'NORMAL',
  `capture_day` int(2) NOT NULL default 0,
  `data` varchar(255) NOT NULL,
  `normal_return_url` varchar(255) NOT NULL,
  `cancel_return_url` varchar(255) NOT NULL,
  `automatic_response_url` varchar(255) NOT NULL,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$installer->getTable('atos/log_response')}` (
  `entity_id` int(10) unsigned NOT NULL auto_increment,
  `transaction_id` varchar(50) NOT NULL,
  `authorisation_id` varchar(50) NOT NULL,
  `transmission_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `order_id` varchar(50) NOT NULL,
  `merchant_id` varchar(50) NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `amount` int(50) unsigned NOT NULL default 0,
  `payment_means` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `response_code` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL default 'NORMAL',
  `error` varchar(255) NOT NULL,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();