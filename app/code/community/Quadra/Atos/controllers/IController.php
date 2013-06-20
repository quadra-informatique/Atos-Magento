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
class Quadra_Atos_IController extends Mage_Core_Controller_Front_Action {

    public function mAction() {

        $file = Mage::getDesign()->getSkinUrl() . DS . 'images' . DS . 'media' . DS . 'atos' . DS . $this->getRequest()->getParam('g');
        $file = str_replace(Mage::getBaseUrl(), Mage::getBaseDir() . '/', $file);
        if (!file_exists($file)) {
            die;
        }

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (($ext != 'gif') && ($ext != 'png') && ($ext != 'jpg')) {
            Mage::throwException('Invalid parameter');
        }

        /* Detect mime content type */
        if (function_exists('mime_content_type'))
            $mimeType = @mime_content_type($file);
        else
            $mimeType = 'image/gif';

        /* Set headers for download */
        header('Content-Type: ' . $mimeType);
        //ob_end_flush();
        $fp = fopen($file, 'rb');
        while (!feof($fp))
            echo fgets($fp, 16384);

        exit;
    }

}
