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
class Quadra_Atos_Model_Api_Parameters extends Quadra_Atos_Model_Abstract {

    protected $_order;
    protected $_quote;
    protected $_allowedCountryCode = array('be', 'fr', 'de', 'it', 'es', 'en');

    /**
     *  Return current order object
     *
     *  @return	  object
     */
    public function getOrder() {
        if (empty($this->_order)) {
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
            $this->_order = $order;
        }
        return $this->_order;
    }

    /**
     * Return current quote object
     * @return Mage_Sales_Model_Quote $quote
     */
    public function getQuote() {
        if (!$this->_quote) {
            $quoteId = Mage::getSingleton('checkout/session')->getLastQuoteId();
            $this->_quote = Mage::getModel('sales/quote')->load($quoteId);
        }
        return $this->_quote;
    }

    /**
     * Get real order ids
     *
     * @return string
     */
    public function getOrderList() {
        if ($this->getQuote()->getIsMultiShipping())
            return Mage::getSingleton('checkout/session')->getRealOrderIds();
        else
            return $this->getOrder()->getRealOrderId();
    }

    /**
     *  Return Language Code
     *
     *  @return	  string
     */
    protected function getLanguageCode() {
        $language = substr(Mage::getStoreConfig('general/locale/code'), 0, 2);

        $Alanguages = $this->getConfig()->getLanguages();

        if (count($Alanguages) === 1) {
            return strtolower($Alanguages[0]);
        }

        if (array_key_exists($language, $Alanguages)) {
            $Acode = array_keys($Alanguages);
            $key = array_search($language, $Acode);

            return strtolower($Acode[$key]);
        }

        return 'fr';
    }

    /**
     *  Return Currency Code
     *
     *  @return	  string
     */
    public function getCurrencyCode() {
        $currencies = $this->getConfig()->getCurrencies();
        $currency_code = $this->getQuote()->getQuoteCurrencyCode();

        if (array_key_exists($currency_code, $currencies)) {
            return $currencies[$currency_code];
        } else {
            return false;
        }
    }

    /**
     *  Return total orders
     *
     *  @return  numeric
     */
    public function getGrandTotal() {
        if ($this->getQuote()->getIsMultiShipping()) {
            $total = $this->getQuote()->getGrandTotal();
        } else {
            $total = $this->getOrder()->getTotalDue();
        }

        return number_format($total, 2, '', '');
    }

    /**
     *  Return merchant country
     *
     *  @return  string
     */
    public function getMerchantCountry() {
        $Acountry = Mage::getStoreConfig('general/country');
        $current_country_code = strtolower($Acountry['default']);

        if (in_array($current_country_code, $this->_allowedCountryCode)) {
            return $current_country_code;
        } else {
            return 'en';
        }
    }

    /**
     *  Return Atos payment server IP addresses
     *
     *  @return array
     */
    public function getAtosServerIpAddresses() {
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $ip = getenv('HTTP_CLIENT_IP');
            } else {
                $ip = getenv('REMOTE_ADDR');
            }
        }

        return explode(',', $ip);
    }

}