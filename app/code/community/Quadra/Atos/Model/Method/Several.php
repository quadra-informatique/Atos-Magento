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
class Quadra_Atos_Model_Method_Several extends Quadra_Atos_Model_Method_Abstract
{

    protected $_code = 'atos_several';
    protected $_formBlockType = 'atos/form_several';
    protected $_infoBlockType = 'atos/info_several';
    protected $_redirectBlockType = 'atos/redirect_several';

    /**
     * Payment Method features
     * @var bool
     */
    protected $_canAuthorize = true;
    protected $_isInitializeNeeded = true;
    protected $_canUseForMultishipping = false;

    /**
     * First call to the Atos server
     */
    public function callRequest()
    {
        // Affectation des paramètres obligatoires
        $parameters = "merchant_id=" . $this->getConfig()->getMerchantId();
        $parameters .= " merchant_country=" . $this->getConfig()->getMerchantCountry();
        $parameters .= " amount=" . $this->_getAmount();
        $parameters .= " currency_code=" . $this->getConfig()->getCurrencyCode($this->_getQuote()->getQuoteCurrencyCode());

        // Initialisation du chemin du fichier pathfile
        $parameters .= " pathfile=" . $this->getConfig()->getPathfile();

        // Affectation dynamique des autres paramètres
        $parameters .= " normal_return_url=" . $this->_getNormalReturnUrl();
        $parameters .= " cancel_return_url=" . $this->_getCancelReturnUrl();
        $parameters .= " automatic_response_url=" . $this->_getAutomaticResponseUrl();
        $parameters .= " language=" . $this->getConfig()->getLanguageCode();
        $parameters .= " payment_means=" . $this->_getPaymentMeans();
        $parameters .= " capture_day=5";
        $parameters .= " capture_mode=PAYMENT_N";
        $parameters .= " customer_id=" . $this->_getCustomerId();
        $parameters .= " customer_email=" . $this->_getCustomerEmail();
        $parameters .= " customer_ip_address=" . $this->_getCustomerIpAddress();
        $parameters .= " data=NB_PAYMENT=" . $this->_getNbPayment() . "\;PERIOD=30\;INITIAL_AMOUNT=" . $this->_getFirstAmount() . "\;" . str_replace(',', '\;', $this->getConfig()->getSelectedDataFieldKeys());
        $parameters .= " order_id=" . $this->_getOrderId();

        // Initialisation du chemin de l'executable request
        $binPath = $this->getConfig()->getBinRequest();

        // Debug
        if ($this->getConfigData('debug'))
            $this->debugRequest($parameters);

        $sips = $this->getApiRequest()->doRequest($parameters, $binPath);

        if (($sips['code'] == "") && ($sips['error'] == "")) {
            $this->_error = true;
            $this->_message = Mage::helper('atos')->__('<br /><center>Call request file error</center><br />Executable file request not found (%s)', $binPath);
        } elseif ($sips['code'] != 0) {
            $this->_error = true;
            $this->_message = Mage::helper('atos')->__('<br /><center>Call payment API error</center><br />Error message: %s', $sips['error']);
        } else {
            // Active debug
            $this->_message = $sips['error'] . '<br />';
            $this->_response = $sips['message'];
        }
    }

    /**
     * Get Payment Means
     *
     * @return string
     */
    protected function _getPaymentMeans()
    {
        return str_replace(',', ',2,', Mage::getStoreConfig('payment/atos_several/cctypes')) . ',2';
    }

    /**
     * Get normal return URL
     *
     * @return string
     */
    protected function _getNormalReturnUrl()
    {
        return Mage::getUrl('atos/payment_several/normal', array('_secure' => true));
    }

    /**
     * Get cancel return URL
     *
     * @return string
     */
    protected function _getCancelReturnUrl()
    {
        return Mage::getUrl('atos/payment_several/cancel', array('_secure' => true));
    }

    /**
     * Get automatic response URL
     *
     * @return string
     */
    protected function _getAutomaticResponseUrl()
    {
        return Mage::getUrl('atos/payment_several/automatic', array('_secure' => true));
    }

    /**
     * Return Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('atos/payment_several/redirect', array('_secure' => true));
    }

    protected function _getNbPayment()
    {
        return Mage::getStoreConfig('payment/atos_several/nb_payment');
    }

    /**
     * Get first amount to capture
     *
     * @return string
     */
    protected function _getFirstAmount()
    {
        return round($this->_getAmount() / $this->_getNbPayment());
    }

}
