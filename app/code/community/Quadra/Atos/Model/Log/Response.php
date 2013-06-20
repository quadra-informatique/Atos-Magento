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
class Quadra_Atos_Model_Log_Response extends Mage_Core_Model_Abstract {

    protected function _construct() {
        $this->_init('atos/log_response');
    }

    public function logResponse($action, $response) {
        $date = $response['transmission_date'];
        $year = substr($date, 0, 4);
        $month = substr($date, 4, 2);
        $day = substr($date, 6, 2);
        $hour = substr($date, 8, 2);
        $minute = substr($date, 10, 2);
        $second = substr($date, 12, 2);
        $time = mktime($hour, $minute, $second, $month, $day, $year);
        $transmissionDate = date('Y-m-d H:i:s', $time);

        foreach (explode(',', $response['order_id']) as $orderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            $amount = $order->getGrandTotal() * 100;

            $data = array(
                'transaction_id' => $response['transaction_id'],
                'authorisation_id' => $response['authorisation_id'],
                'transmission_date' => $transmissionDate,
                'order_id' => $orderId,
                'merchant_id' => $response['merchant_id'],
                'customer_id' => $response['customer_id'],
                'amount' => $amount,
                'payment_means' => $response['payment_means'],
                'action' => $action,
                'response_code' => $response['response_code'],
                'code' => $response['code'],
                'error' => $response['error']
            );

            try {
                $this->setId(null)
                        ->addData($data)
                        ->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }

}