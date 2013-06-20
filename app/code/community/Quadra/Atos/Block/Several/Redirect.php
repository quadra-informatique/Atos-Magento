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
class Quadra_Atos_Block_Several_Redirect extends Mage_Core_Block_Abstract {

    protected function _toHtml() {
        $several = Mage::getModel('atos/method_several');
        $several->callRequest();

        if ($several->getSystemError()) {
            return $several->getSystemMessage();
        }

        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n";
        $html .= '<html>' . "\n";
        $html .= '    <head>' . "\n";
        $html .= '        <style type="text/css">' . "\n";
        $html .= '            @import url("' . $this->getSkinUrl('css/stylesheet.css') . '");' . "\n";
        $html .= '            @import url("' . $this->getSkinUrl('css/checkout.css') . '");' . "\n";
        $html .= '        </style>' . "\n";
        $html .= '    </head>';
        $html .= '    <body>';
        $html .= '        <div id="atosButtons">' . "\n";
        $html .= '            <form action="' . $several->getSystemUrl() . '" method="post">' . "\n";
        $html .= '                ' . $several->getSystemMessage() . "\n";
        $html .= '            </form>' . "\n";
        $html .= '        </div>' . "\n";
        $html .= '    </body>' . "\n";
        $html .= '</html>';

        return $html;
    }

}