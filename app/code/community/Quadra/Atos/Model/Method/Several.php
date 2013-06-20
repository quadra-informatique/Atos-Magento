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
class Quadra_Atos_Model_Method_Several extends Quadra_Atos_Model_Method_Default {

    private $_url       = null;
    private $_message   = null;
    private $_error     = false;

    protected $_code          = 'atos_several';
    protected $_formBlockType = 'atos/several_form';
    protected $_infoBlockType = 'atos/several_info';

    public function callRequest() {
        $first_amount = round(Mage::getSingleton('atos/api_parameters')->getGrandTotal() / $this->getNbPayment());

        $command = " capture_day=5";
        $command.= " capture_mode=PAYMENT_N";
        $command.= " data=NB_PAYMENT=" . $this->getNbPayment() . "\;PERIOD=30\;INITIAL_AMOUNT=" . $first_amount;

        $parameters = array(
            'command' => $command,
            'bin_request' => $this->getBinRequest(),
            'bank' => $this->getBank(),
            'merchant_id' => $this->getMerchantId(),
            'payment_means' => $this->getPaymentMeans(),
            'url' => array(
                'cancel' => $this->getCancelReturnUrl(),
                'normal' => $this->getNormalReturnUrl(),
                'automatic' => $this->getAutomaticReturnUrl()
            )
        );

        $sips = $this->getApiRequest()->doRequest($parameters);

        if (($sips['code'] == "") && ($sips['error'] == "")) {
            $this->_error = true;
            $this->_message = Mage::helper('atos')->__('Call Bin Request Error - Check path to the file or command line for debug');
        } elseif ($sips['code'] != 0) {
            $this->_error = true;
            $this->_message = Mage::helper('atos')->__($sips['error']);
        } else {
            $regs = array();

            if (preg_match('/<form [^>]*action="([^"]*)"[^>]*>(.*)<\/form>/i', $sips['message'], $regs)) {
                $this->_url = $regs[1];
                $this->_message = $regs[2];
            } else {
                $this->_error = true;
                $this->_message = 'Call Bin Request Error - Check path to the file or command line for debug';
            }
        }

        if (array_key_exists('command', $sips))
            Mage::getModel('atos/log_request')->logRequest($sips['command']);
    }

    /**
     * Return a minimum amount to activate the module
     *
     * @return number
     */
    public function getMinimumAmount() {
        return $this->getConfigData('min_order_total');
    }

    public function getNbPayment() {
        return (int) $this->getConfigData('nb_payment');
    }

    public function getSystemUrl() {
        return $this->_url;
    }

    public function getSystemMessage() {
        return $this->_message;
    }

    public function getSystemError() {
        return $this->_error;
    }

}
