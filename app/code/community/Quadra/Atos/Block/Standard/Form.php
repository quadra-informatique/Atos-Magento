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
class Quadra_Atos_Block_Standard_Form extends Mage_Payment_Block_Form {

    protected function _construct() {
        $this->setTemplate('atos/form/standard.phtml');
        parent::_construct();
    }

    public function getCreditCardsAccepted() {
        $cards = Mage::getSingleton('atos/config')->getCreditCardTypes();

        $array = array();
        foreach (explode(',', Mage::getSingleton('atos/method_standard')->getCctypes()) as $value) {
            if (array_key_exists($value, $cards)) {
                $array[$value] = $cards[$value];
            }
        }

        return $array;
    }

    public function getAtosLogoSrc() {
        return $this->getUrl() . Mage::getStoreConfig('logo/atos_standard');
    }

}
