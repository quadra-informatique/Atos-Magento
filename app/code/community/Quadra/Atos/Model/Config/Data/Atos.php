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
class Quadra_Atos_Model_Config_Data_Atos extends Mage_Core_Model_Config_Data {

    public function _beforeSave() {
        $filename = BP . DS . 'app' . DS . 'code' . DS . 'local' . DS . 'Mage' . DS . 'Atos' . DS . 'etc' . DS . 'config.xml';

        if (file_exists($filename)) {
            Mage::getSingleton('adminhtml/session')->addError('Warning: the module Mage_Atos must be uninstalled.');
        }

        return $this;
    }

}
