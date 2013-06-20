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
class Quadra_Atos_PaymentController extends Mage_Core_Controller_Front_Action {

    protected $_session;
    protected $_atosResponse = null;
    protected $_realOrderIds;
    protected $_quote;

    public function preDispatch() {
        parent::preDispatch();
        $this->_session = Mage::getSingleton('checkout/session');
    }

    /**
     * Get Atos configuration
     *
     * @return type
     */
    public function getConfig() {
        return Mage::getSingleton('atos/config');
    }

    /**
     * Get Atos Method Singleton
     *
     * @return object Quadra_Atos_Model_Aurore
     */
    public function getAtosMethod($method = false) {
        switch ($method) {
            case 'atos_standard' : $atosMethod = Mage::getSingleton('atos/method_standard');
                break;
            case 'atos_several' : $atosMethod = Mage::getSingleton('atos/method_several');
                break;
            case 'atos_aurore' : $atosMethod = Mage::getSingleton('atos/method_aurore');
                break;
            case 'atos_euro' : $atosMethod = Mage::getSingleton('atos/method_euro');
                break;
            default : $atosMethod = Mage::getSingleton('atos/method_default');
        }

        return $atosMethod;
    }

    /**
     * Redirect action to send data to bank's server
     */
    public function redirectAction() {
        $method   = $this->getRequest()->getParam('method', 'atos_standard');

        switch ($method) {
            case 'atos_several' : $block = 'atos/several_redirect';
                break;
            case 'atos_aurore' : $block = 'atos/aurore_redirect';
                break;
            case 'atos_euro' : $block = 'atos/euro_redirect';
                break;
            default : $block = 'atos/standard_redirect';
        }

        if ($this->_session->getQuote()->getHasError()) {
            $this->_redirect('checkout/cart');
        } else {
            if (($quoteId = $this->_session->getLastQuoteId())) {
                $this->_session->setAtosQuoteId($quoteId);
            }
            $this->getResponse()
                 ->setBody(
                    $this->getLayout()
                         ->createBlock($block)
                         ->toHtml()
                 );
        }
    }

    /**
     * Cancel action called by customer's action
     */
    public function cancelAction() {
        if (!$this->getRequest()->isPost('DATA')) {
            $this->_redirect('');
            return;
        }

        // Model par défaut
        $model = $this->getAtosMethod();
        $response = $model->getApiResponse()
                ->doResponse($_REQUEST['DATA'], array('bin_response' => $model->getBinResponse()));

        if ($response) {
            unset($model);
            $this->_setAtosResponse($response);
            Mage::getModel('atos/log_response')->logResponse('cancel', $response);

            $realOrderIds = $this->_getRealOrderIds();
            if (count($realOrderIds) > 0) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderIds[0]);
                $model = $this->getAtosMethod($order->getPayment()->getMethod());
                unset($order);
            }

            foreach ($realOrderIds as $realOrderId) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderId);

                if ($order->getId()) {
                    if (!($status = $model->getConfigData('order_status_payment_canceled'))) {
                        $status = $order->getStatus();
                    }

                    $order->addStatusToHistory(
                            $status, $this->__('Order was canceled by customer')
                    );

                    if (($status == Mage_Sales_Model_Order::STATE_HOLDED) && $order->canHold()) {
                        $order->hold();
                    } elseif (($status == Mage_Sales_Model_Order::STATE_CANCELED) && $order->canCancel()) {
                        $order->cancel();
                    }

                    $order->save();
                }
            }

            if (!$model->getConfigData('empty_cart')) {
                Mage::helper('atos')->reorder($this->_getRealOrderIds());
            } else {
                $this->_session->setQuoteId($this->_session->getAtosQuoteId(true));
            }

            $this->_session->setCanRedirect(false);
            $this->_session->addNotice($this->__('The payment was canceled.'));
        }

        $this->_redirect('checkout/cart');
    }

    /**
     * Normal action called after return of the bank's website
     */
    public function normalAction() {
        if (!$this->getRequest()->isPost('DATA')) {
            $this->_redirect('');
            return;
        }

        // Model par défaut
        $model    = $this->getAtosMethod();
        $response = $model->getApiResponse()
                          ->doResponse($_REQUEST['DATA'],array('bin_response' => $model->getBinResponse()));

        if ($response) {
            unset($model);
            $this->_setAtosResponse($response);
            Mage::getModel('atos/log_response')->logResponse('normal', $response);

            $realOrderIds = $this->_getRealOrderIds();
            if (count($realOrderIds) > 0) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderIds[0]);
                $model = $this->getAtosMethod($order->getPayment()->getMethod());
                unset($order);
            }

            if ($response['merchant_id'] != $model->getMerchantId()) {
                Mage::log(sprintf('Response Merchant ID (%s) is not valid with configuration value (%s)' . "\n", $response['merchant_id'], $model->getMerchantId()), null, 'atos.log');

                $this->_session->addError($this->__('We are sorry but we have an error with payment module'));
                $this->_redirect('checkout/cart');
                return;
            }

            switch ($response['response_code']) {
                case '00':
                    $this->_session->setQuoteId($this->_session->getAtosQuoteId(true));
                    $this->_session->getQuote()->setIsActive(false)->save();

                    if ($this->_getQuote()->getIsMultiShipping()) {
                        $orderIds = array();

                        foreach ($realOrderIds as $realOrderId) {
                            $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderId);
                            $orderIds[$order->getId()] = $realOrderId;
                            unset($order);
                        }

                        Mage::getSingleton('checkout/type_multishipping')
                                ->getCheckoutSession()
                                ->setDisplaySuccess(true);

                        $this->_session->setCanRedirect(false);

                        Mage::getSingleton('core/session')->setOrderIds($orderIds);
                    }

                    $this->_redirect($this->_getSuccessRedirect(), array('_secure' => true));
                    break;

                default:
                    if (!$model->getConfigData('empty_cart')) {
                        Mage::helper('atos')->reorder($realOrderIds);
                    } else {
                        $this->_session->setQuoteId($this->_session->getAtosQuoteId(true));
                    }

                    $this->_session->setCanRedirect(false);
                    $this->_session->addError($this->__('(Response Code %s) Error with payment module', $response['response_code']));
                    $this->_redirect('checkout/cart');
                    break;
            }
        }
    }

    /**
     * Automatic action called by the Atos server
     */
    public function automaticAction() {
        if (!$this->getRequest()->isPost('DATA')) {
            $this->_redirect('');
            return;
        }

        $model    = $this->getAtosMethod();
        $response = $model->getApiResponse()
                          ->doResponse($_REQUEST['DATA'],array('bin_response' => $model->getBinResponse()));

        if ($response) {
            $this->_setAtosResponse($response);
            Mage::getModel('atos/log_response')->logResponse('automatic', $response);

            $realOrderIds = $this->_getRealOrderIds();
            if (count($realOrderIds) > 0) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderIds[0]);
                $model = $this->getAtosMethod($order->getPayment()->getMethod());
                unset($order);
            }

            if ($model->getCheckByIpAddress()) {
                $ipAdresses = $model->getApiParameters()->getAtosServerIpAddresses();
                $authorizedIps = $this->getConfig()->getAuthorizedIps();
                $isIpOk = false;

                foreach ($ipAdresses as $ipAdress) {
                    if (in_array(trim($ipAdress), $authorizedIps)) {
                        $isIpOk = true;
                        break;
                    }
                }

                if (!$isIpOk) {
                    Mage::log($model->getApiParameters()->getIpAddress() . ' tries to connect to our server' . "\n", null, 'atos.log');
                    return;
                }
            }

            if ($response['merchant_id'] != $model->getMerchantId()) {
                Mage::log(sprintf('Response Merchant ID (%s) is not valid with configuration value (%s)' . "\n", $response['merchant_id'], $model->getMerchantId()), null, 'atos.log');
                return;
            }

            foreach ($realOrderIds as $realOrderId) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderId);
                Mage::helper('atos')->updateOrderState($order, $response, $model);
            }
        }
    }

    /**
     * Setting response after returning from Atos
     *
     * @param array $response
     * @return object $this
     */
    protected function _setAtosResponse($response) {
        if (count($response)) {
            $this->_atosResponse = $response;
        }
        return $this;
    }

    /**
     * Get quote model
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote() {
        if (!$this->_quote) {
            $this->_quote = Mage::getModel('sales/quote')->load($this->_session->getAtosQuoteId());

            if (!$this->_quote->getId()) {
                $realOrderIds = $this->_getRealOrderIds();
                if (count($realOrderIds)) {
                    $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderIds[0]);
                    $this->_quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                }
            }
        }
        return $this->_quote;
    }

    /**
     * Get real order ids
     *
     * @return array
     */
    protected function _getRealOrderIds() {
        if (!$this->_realOrderIds) {
            if ($this->_atosResponse) {
                $this->_realOrderIds = explode(',', $this->_atosResponse['order_id']);
            } else {
                return array();
            }
        }
        return $this->_realOrderIds;
    }

    /**
     * Get success redirection
     *
     * @return string
     */
    protected function _getSuccessRedirect() {
        if ($this->_getQuote()->getIsMultiShipping())
            return 'checkout/multishipping/success';
        else
            return 'checkout/onepage/success';
    }

}
