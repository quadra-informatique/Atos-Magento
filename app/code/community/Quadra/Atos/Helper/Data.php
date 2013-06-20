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
class Quadra_Atos_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * Update order state according to the answer of the Atos server
     * @param Mage_Sales_Model_Order $order
     * @param array $response
     * @param object $model
     */
    public function updateOrderState($order, $response, $model) {
        switch ($response['response_code']) {
            // Success order
            case '00':
                if ($order->getId()) {
                    if ($order->getState() == Mage_Sales_Model_Order::STATE_HOLDED) {
                        $order->unhold();
                    }

                    if (!$status = $model->getConfigData('order_status_payment_accepted')) {
                        $status = $order->getStatus();
                    }

                    $message = $this->__('Payment accepted by Atos');
                    $message .= ' - ' . Mage::getSingleton('atos/api_response')->describeResponse($response);

                    if ($status == Mage_Sales_Model_Order::STATE_PROCESSING) {
                        $order->setState(
                            Mage_Sales_Model_Order::STATE_PROCESSING, $status, $message
                        );
                    } else if ($status == Mage_Sales_Model_Order::STATE_COMPLETE) {
                        $order->setState(
                            Mage_Sales_Model_Order::STATE_COMPLETE, $status, $message, null, false
                        );
                    } else {
                        $order->addStatusToHistory($status, $message, true);
                    }

                    // Create invoice
                    if ($model->getConfigData('invoice_create')) {
                        $this->_saveInvoice($order);
                    }

                    if (!$order->getEmailSent()) {
                        $order->sendNewOrderEmail();
                    }
                }
                break;

            default:
                // Cancel order
                if ($order->getId()) {
                    $messageError = $this->__('Customer was rejected by Atos');
                    $messageError .= ' - ' . Mage::getSingleton('atos/api_response')->describeResponse($response);

                    if ($order->getState() == Mage_Sales_Model_Order::STATE_HOLDED) {
                        $order->unhold();
                    }

                    if (!$status = $model->getConfigData('order_status_payment_refused')) {
                        $status = $order->getStatus();
                    }

                    $order->addStatusToHistory($status, $messageError);

                    if ($status == Mage_Sales_Model_Order::STATE_HOLDED && $order->canHold()) {
                        $order->hold();
                    } elseif ($status == Mage_Sales_Model_Order::STATE_CANCELED && $order->canCancel()) {
                        $order->cancel();
                    }
                }
                break;
        }

        $order->save();
    }

    public function reorder($realOrderIds) {
        $cart = Mage::getSingleton('checkout/cart');

        foreach ($realOrderIds as $realOrderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderId);

            if ($order->getId()) {
                $items = $order->getItemsCollection();
                foreach ($items as $item) {
                    try {
                        $cart->addOrderItem($item);
                    } catch (Mage_Core_Exception $e) {
                        if (Mage::getSingleton('checkout/session')->getUseNotice(true)) {
                            Mage::getSingleton('checkout/session')->addNotice($e->getMessage());
                        } else {
                            Mage::getSingleton('checkout/session')->addError($e->getMessage());
                        }
                    } catch (Exception $e) {
                        Mage::getSingleton('checkout/session')->addException($e, Mage::helper('checkout')->__('Cannot add the item to shopping cart.')
                        );
                    }
                }
            }
        }

        $cart->save();
    }

    /**
     *  Save invoice for order
     *
     *  @param    Mage_Sales_Model_Order $order
     *  @return	  boolean Can save invoice or not
     */
    protected function _saveInvoice(Mage_Sales_Model_Order $order) {
        if ($order->canInvoice()) {
            $convertor = Mage::getModel('sales/convert_order');

            $invoice = $convertor->toInvoice($order);

            foreach ($order->getAllItems() as $orderItem) {
                if (!$orderItem->getQtyToInvoice()) {
                    continue;
                }

                $item = $convertor->itemToInvoiceItem($orderItem);
                $item->setQty($orderItem->getQtyToInvoice());
                $invoice->addItem($item);
            }

            $invoice->collectTotals();
            $invoice->register();

            try {
                Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();

                $order->addStatusToHistory($order->getStatus(), Mage::helper('atos')->__('Invoice %s was created', $invoice->getIncrementId()));

                $invoice->sendEmail();

                $order->addStatusToHistory($order->getStatus(), Mage::helper('atos')->__('Invoice %s was send', $invoice->getIncrementId()));

                if (!$invoice->getEmailSent())
                    $invoice->setData('email_sent', '1')
                            ->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        return false;
    }

}