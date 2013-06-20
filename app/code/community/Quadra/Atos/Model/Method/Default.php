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
class Quadra_Atos_Model_Method_Default extends Quadra_Atos_Model_Abstract {

    /**
     * Availability options
     */
    protected $_isGateway               = false;
    protected $_canAuthorize            = false;
    protected $_canCapture              = false;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;

    public function getCode() {
        return $this->_code;
    }

    /**
     *  Form block description
     *
     *  @return	 object
     */
    public function createFormBlock($name) {
        $block = $this->getLayout();
        $block->createBlock($this->_formBlockType, $name);
        $block->setMethod($this->_code);
        $block->setPayment($this->getPayment());
        return $block;
    }

    /**
     * Retrieve information from atos configuration
     *
     * @param string $field
     * @param int|string|null|Mage_Core_Model_Store $storeId
     *
     * @return mixed
     */
    public function getAtosConfigData($field, $storeId = null) {
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $code = explode('_', $this->getCode());
        $path = 'atos/config_' . $code['1'] . '/' . $field;
        return Mage::getStoreConfig($path, $storeId);
    }

    public function getBank() {
        return $this->getAtosConfigData('bank');
    }

    /**
     * Return merchant ID
     *
     * @return string
     */
    public function getMerchantId() {
        return $this->getAtosConfigData('merchant_id');
    }

    public function getPathfile() {
        return $this->getAtosConfigData('pathfile');
    }

    /**
     *  Return Atos bin file for request
     *
     *  @return	  string
     */
    public function getBinRequest() {
        return Mage::getStoreConfig('atos/config_bin_files/bin_request', $this->getStore());
    }

    /**
     *  Return Atos bin file for response
     *
     *  @return	  string
     */
    public function getBinResponse() {
        return Mage::getStoreConfig('atos/config_bin_files/bin_response', $this->getStore());
    }

    public function getCheckByIpAddress() {
        return $this->getAtosConfigData('check_ip_address');
    }

    public function getCctypes() {
        return $this->getConfigData('cctypes');
    }

    /**
     *  Return credit card type accepted
     *
     *  @return	  string
     */
    protected function getPaymentMeans() {
        $payment = $this->getQuote()->getPayment();

        if (!$card = $payment->getData('cc_type')) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
            $payment = $order->getPayment();
            $card = $payment->getData('cc_type');
        }

        if ($card) {
            return $card . ',1';
        } else {
            $cc = $this->getCctypes();

            if (!empty($cc)) {
                if (strstr($cc, ',')) {
                    $return = '';
                    foreach (explode(',', $cc) as $card) {
                        $return .= $card . ',1,';
                    }

                    return substr($return, 0, -1);
                } else {
                    return $cc . ',1';
                }
            } else {
                return 'CB,1,VISA,1,MASTERCARD,1';
            }
        }
    }

    public function getCaptureMode() {
        return $this->getAtosConfigData('capture_mode');
    }

    /**
     *  Return capture_day (associated with capture_mode.)
     *
     *  @return      string logo_id
     */
    public function getCaptureDay() {
        return $this->getAtosConfigData('capture_days');
    }

    public function getDataFieldKeys() {
        return $this->getAtosConfigData('data_field');
    }

    /**
     *  Return new order status
     *
     *  @return	  string New order status
     */
    public function getNewOrderStatus() {
        return $this->getConfigData('order_status');
    }

    /**
     *  Return template file name (used only in prod env to display payment pages with a template chosen by user)
     *
     *  @return      string templatefile
     */
    public function getTemplatefile() {
        return $this->getAtosConfigData('templatefile');
    }

    /**
     * Website return URL for cancel action
     *
     * @return string
     */
    public function getCancelReturnUrl() {
        return Mage::getUrl('atos/payment/cancel');
    }

    /**
     * Website return URL for normal action
     *
     * @return string
     */
    public function getNormalReturnUrl() {
        return Mage::getUrl('atos/payment/normal');
    }

    /**
     * Website return URL for automatic action
     *
     * @return string
     */
    public function getAutomaticReturnUrl() {
        return Mage::getUrl('atos/payment/automatic');
    }

    /**
     * URL for redirect action
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl() {
        Mage::getSingleton('checkout/session')->setIsMultishipping(false);
        return Mage::getUrl('atos/payment/redirect', array('method' => $this->getCode()));
    }

}