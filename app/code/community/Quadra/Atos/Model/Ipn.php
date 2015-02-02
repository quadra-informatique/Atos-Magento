<?php

/**
 * 1997-2015 Quadra Informatique
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to modules@quadra-informatique.fr so we can send you a copy immediately.
 *
 * @author Quadra Informatique <modules@quadra-informatique.fr>
 * @copyright 1997-2015 Quadra Informatique
 * @license http://www.opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

/**
 * Atos/Sips Instant Payment Notification processor model
 */
class Quadra_Atos_Model_Ipn
{

    protected $_api = null;
    protected $_config = null;
    protected $_invoice = null;
    protected $_invoiceFlag = false;
    protected $_methodInstance = null;
    protected $_order = null;
    protected $_response = null;

    public function __construct()
    {

    }

    public function processIpnResponse($data, $methodInstance)
    {
        // Init instance
        $this->_api = Mage::getSingleton('atos/api_response');
        $this->_methodInstance = $methodInstance;
        $this->_config = $this->_methodInstance->getConfig();

        // Decode Sips Server Response
        $response = $this->_decodeResponse($data);
        if (!array_key_exists('hash', $response)) {
            $this->_methodInstance->debugData('Can\'t retrieve Sips decoded response.');
            Mage::app()->getResponse()
                    ->setHeader('HTTP/1.1', '503 Service Unavailable')
                    ->sendResponse();
            exit;
        }

        // Debug
        $this->_methodInstance->debugResponse($response['hash'], 'Automatic');

        // Check IP address
        if (!$this->_checkIpAddresses()) {
            Mage::app()->getResponse()
                    ->setHeader('HTTP/1.1', '503 Service Unavailable')
                    ->sendResponse();
            exit;
        }

        // Update order
        $this->_processOrder();
    }

    /**
     * Decode Sips server response
     *
     * @param string $response
     * @return array
     */
    protected function _decodeResponse($response)
    {
        $this->_response = $this->_api->doResponse($response, array(
            'bin_response' => $this->_config->getBinResponse(),
            'pathfile' => $this->_config->getPathfile()
        ));

        return $this->_response;
    }

    /**
     * Check if the server IP Address is allowed
     *
     * @return boolean
     */
    protected function _checkIpAddresses()
    {
        if ($this->_config->getCheckByIpAddress()) {
            $ipAdresses = $this->_response['atos_server_ip_adresses'];
            $authorizedIps = $this->_config->getAuthorizedIps();
            $isIpOk = false;

            foreach ($ipAdresses as $ipAdress) {
                if (in_array(trim($ipAdress), $authorizedIps)) {
                    $isIpOk = true;
                    break;
                }
            }

            if (!$isIpOk) {
                $filename = 'payment_' . $this->getMethodInstance()->getCode() . '.log';
                Mage::log(implode(', ', $ipAdresses) . ' tries to connect to our server' . "\n", Zend_Log::WARN, $filename, true);
                return false;
            }
        }

        return true;
    }

    /**
     * Load order
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        if (empty($this->_order)) {
            // Check order ID existence
            if (!array_key_exists('order_id', $this->_response['hash'])) {
                $this->_methodInstance->debugData('No order ID found in response data.');
                Mage::app()->getResponse()
                        ->setHeader('HTTP/1.1', '503 Service Unavailable')
                        ->sendResponse();
                exit;
            }

            // Load order
            $id = $this->_response['hash']['order_id'];
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($id);
            if (!$this->_order->getId()) {
                $this->_methodInstance->debugData(sprintf('Wrong order ID: "%s".', $id));
                Mage::app()->getResponse()
                        ->setHeader('HTTP/1.1', '503 Service Unavailable')
                        ->sendResponse();
                exit;
            }
        }
        return $this->_order;
    }

    /**
     * Update order with Sips response
     */
    protected function _processOrder()
    {
        // Check response code existence
        if (!array_key_exists('response_code', $this->_response['hash'])) {
            $this->_methodInstance->debugData('No response code found in response data.');
            Mage::app()->getResponse()
                    ->setHeader('HTTP/1.1', '503 Service Unavailable')
                    ->sendResponse();
            exit;
        }

        // Get order to update
        $order = $this->_getOrder();
        $messages = array();

        switch ($this->_response['hash']['response_code']) {
            case '00': // Success order
                // Get sips return data
                $messages[] = $this->__('Payment accepted by Sips') . '<br /><br />' . $this->_api->describeResponse($this->_response['hash']);

                // Update payment
                $this->_processOrderPayment($order);

                // Create invoice
                if ($this->_invoiceFlag) {
                    $invoiceId = $this->_processInvoice($order);
                    $messages[] = Mage::helper('atos')->__('Invoice #%s created', $invoiceId);
                }

                // Add messages to order history
                foreach ($messages as $message) {
                    $order->addStatusHistoryComment($message);
                }

                // Save order
                $order->save();

                // Send order confirmation email
                if (!$order->getEmailSent() && $order->getCanSendNewEmailFlag()) {
                    try {
                        if (method_exists($order, 'queueNewOrderEmail')) {
                            $order->queueNewOrderEmail();
                        } else {
                            $order->sendNewOrderEmail();
                        }
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
                // Send invoice email
                if ($this->_invoiceFlag) {
                    try {
                        $this->_invoice->sendEmail();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
                break;
            default: // Rejected payment or error
                $this->_processCancellation($order);
        }
    }

    /**
     * Update order payment
     *
     * @param Mage_Sales_Model_Order $order
     */
    protected function _processOrderPayment($order)
    {
        try {
            // Set transaction
            $payment = $order->getPayment();
            $payment->setTransactionId($this->_response['hash']['transaction_id']);
            $data = array(
                'cc_type' => $this->_response['hash']['payment_means'],
                'cc_exp_month' => substr($this->_response['hash']['card_validity'], 4, 2),
                'cc_exp_year' => substr($this->_response['hash']['card_validity'], 0, 4),
                'cc_last4' => $this->_response['hash']['card_number']
            );

            $payment->addData($data);
            $payment->save();

            if ($this->_response['hash']['capture_mode'] == Quadra_Atos_Model_Config::PAYMENT_ACTION_CAPTURE ||
                    $this->_response['hash']['capture_mode'] == Quadra_Atos_Model_Config::PAYMENT_ACTION_AUTHORIZE) {
                // Add authorization transaction
                if (!$order->isCanceled() && $this->_methodInstance->canAuthorize()) {
                    $payment->authorize(true, $order->getBaseGrandTotal());
                    $payment->setAmountAuthorized($order->getTotalDue());
                    $this->_invoiceFlag = true;
                }
            }

            $order->save();
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::app()->getResponse()
                    ->setHeader('HTTP/1.1', '503 Service Unavailable')
                    ->sendResponse();
            exit;
        }
    }

    /**
     * Create invoice
     *
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    protected function _processInvoice($order)
    {
        try {
            $this->_invoice = $order->prepareInvoice();
            $this->_invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
            $this->_invoice->register();

            $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($this->_invoice)->addObject($this->_invoice->getOrder())
                    ->save();
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::app()->getResponse()
                    ->setHeader('HTTP/1.1', '503 Service Unavailable')
                    ->sendResponse();
            exit;
        }

        return $this->_invoice->getIncrementId();
    }

    /**
     * Cancel order
     *
     * @param Mage_Sales_Model_Order $order
     */
    protected function _processCancellation($order)
    {
        $messages = array();
        $hasError = false;
        try {
            $messages [] = $this->__('Payment rejected by Sips') . '<br /><br />' . $this->_api->describeResponse($this->_response['hash']);
            $order->cancel();
        } catch (Mage_Core_Exception $e) {
            $hasError = true;
            Mage::logException($e);
        } catch (Exception $e) {
            $hasError = true;
            $messages[] = $this->__('The order has not been cancelled.');
            Mage::logException($e);
        }

        foreach ($messages as $message) {
            $order->addStatusHistoryComment($message)
                    ->save();
        }

        if ($hasError) {
            Mage::app()->getResponse()
                    ->setHeader('HTTP/1.1', '503 Service Unavailable')
                    ->sendResponse();
            exit;
        }
    }

}
