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
class Quadra_Atos_Model_Observer {

    /**
     *  Can redirect to Atos payment
     */
    public function initRedirect(Varien_Event_Observer $observer) {
        Mage::getSingleton('checkout/session')->setCanRedirect(true);
    }

    /**
     *  Return Orders Redirect URL
     *
     *  @return	  string Orders Redirect URL
     */
    public function multishippingRedirectUrl(Varien_Event_Observer $observer) {
        if (Mage::getSingleton('checkout/session')->getCanRedirect()) {
            $orderIds = Mage::getSingleton('core/session')->getOrderIds();
            $orderIdsTmp = $orderIds;
            $key = array_pop($orderIdsTmp);
            $order = Mage::getModel('sales/order')->loadByIncrementId($key);

            if (!(strpos($order->getPayment()->getMethod(), 'atos') === false)) {
                Mage::getSingleton('checkout/session')
                        ->setLastRealOrderId($order->getIncrementId())
                        ->setRealOrderIds(implode(',', $orderIds));
                Mage::app()->getResponse()->setRedirect(Mage::getUrl('atos/standard/redirect'));
            }
        } else {
            Mage::getSingleton('checkout/session')->unsRealOrderIds();
        }

        return $this;
    }

    /**
     *  Disables sending email after the order creation
     *
     *  @return	  updated order
     */
    public function disableEmailForMultishipping(Varien_Event_Observer $observer) {
        $order = $observer->getOrder();

        if (!(strpos($order->getPayment()->getMethod(), 'atos') === false)) {
            $order->setCanSendNewEmailFlag(false)->save();
        }

        return $this;
    }

    public function updateNotPayedOrders() {
        $_atosMethods = array(
            'atos_standard' => 'atos/method_standard',
            'atos_aurore' => 'atos/method_aurore',
            'atos_euro' => 'atos/method_euro',
            'atos_several' => 'atos/method_several'
        );

        $_resource = Mage::getSingleton('core/resource');
        $_readConnection = $_resource->getConnection('core_read');

        $queryR = "SELECT `order_id` FROM `{$_resource->getTableName('atos/log_response')}`;";
        $responseOrderIds = $_readConnection->fetchAll($queryR);

        if (count($responseOrderIds)) {
            $res = array();
            foreach ($responseOrderIds as $result) {
                $res[] = $result['order_id'];
            }
            $resOrderIds = "'" . implode("','", $res) . "'";
            $queryR = "SELECT `order_id` FROM `{$_resource->getTableName('atos/log_request')}` WHERE `order_id` NOT IN ({$resOrderIds});";
            $requestOrderIds = $_readConnection->fetchAll($queryR);

            if (count($requestOrderIds)) {
                $orderIds = array();
                foreach ($requestOrderIds as $result) {
                    $orderIds[] = $result['order_id'];
                }

                if (count($orderIds)) {
                    $date = Mage::app()->getLocale()->date();

                    $collection = Mage::getResourceModel('sales/order_collection')
                            ->addFieldToSelect('entity_id')
                            ->addAttributeToFilter('created_at', array('to' => ($date->subMinute(30)->toString('Y-MM-dd HH:mm:ss'))))
                            ->addAttributeToFilter('increment_id', array('in' => $orderIds));

                    foreach ($collection as $entityId) {
                        $orderId = $entityId['entity_id'];
                        $order = Mage::getModel('sales/order')->load($orderId);

                        if ($order->getState() != Mage_Sales_Model_Order::STATE_PROCESSING)
                            continue;

                        $paymentMethod = $order->getPayment()->getMethod();

                        if (!array_key_exists($paymentMethod, $_atosMethods))
                            continue;

                        $status = Mage::getModel($_atosMethods[$paymentMethod])->getConfigData('order_status_payment_canceled');
                        if (!$status) {
                            $status = Mage_Sales_Model_Order::STATE_CANCELED;
                        }

                        try {
                            if ($status == Mage_Sales_Model_Order::STATE_HOLDED && $order->canHold()) {
                                $order->hold();
                            } elseif ($status == Mage_Sales_Model_Order::STATE_CANCELED && $order->canCancel()) {
                                $order->cancel();
                            }
                            $order->save();
                        } catch (Exception $e) {
                            Mage::logException($e);
                        }
                    }
                }
            }
        }
    }

}
