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
class Quadra_Atos_Model_Log_Request extends Mage_Core_Model_Abstract {

    protected function _construct() {
        $this->_init('atos/log_request');
    }

    public function logRequest($command) {
        $parameters = explode(' ', $command);
        $data = array();

        foreach ($parameters as $parameter) {
            $param = explode('=', $parameter);
            if (count($param) > 1) {
                $data[$param[0]] = $param[1];
            }
        }

        foreach (explode(',', $data['order_id']) as $orderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            $amount = $order->getGrandTotal() * 100;

            try {
                $this->setId(null)
                        ->addData($data)
                        ->setData('order_id', $orderId)
                        ->setData('amount', $amount)
                        ->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

}