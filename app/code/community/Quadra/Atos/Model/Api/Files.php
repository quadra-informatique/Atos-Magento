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
class Quadra_Atos_Model_Api_Files extends Quadra_Atos_Model_Abstract {

    public function getPathfileName($bank = null, $merchand_id = null) {
        $path = DS . 'lib' . DS . 'atos' . DS;
        $fullPath = Mage::getBaseDir('base') . $path;

        if (is_dir($fullPath)) {
            if (isset($merchand_id)) {
                $pathfileName = $path . 'parmcom.' . $bank;
                $pathfile = Mage::getBaseDir('base') . $path . 'pathfile.' . $merchand_id;
            } else {
                $parmcom = self::getInstalledParmcom();
                $pathfileName = $path . 'pathfile.' . $parmcom[0]['value'];
                $pathfile = Mage::getBaseDir('base') . $pathfileName;
            }

            if (!file_exists($pathfile)) {
                $content = '#########################################################################' . "\n";
                $content .= '#' . "\n";
                $content .= '# Pathfile' . "\n";
                $content .= '#' . "\n";
                $content .= '# Liste fichiers parametres utilises par le module de paiement' . "\n";
                $content .= '#' . "\n";
                $content .= '#########################################################################' . "\n";
                $content .= "\n";
                $content .= '# ------------------------------------------------------------------------' . "\n";
                $content .= '# Recuperation des logos' . "\n";
                $content .= '# ------------------------------------------------------------------------' . "\n";
                $content .= '#' . "\n";
                $content .= 'D_LOGO!' . Mage::getBaseUrl() . 'atos/i/m/g/!' . "\n";
                $content .= '#' . "\n";
                $content .= '#------------------------------------------------------------------------' . "\n";
                $content .= '# Fichiers parametres de l\'api' . "\n";
                $content .= '#------------------------------------------------------------------------' . "\n";
                $content .= '#' . "\n";
                $content .= '# Repertoire des fichiers de parametres' . "\n";
                $content .= '#' . "\n";
                $content .= 'D_PARM!' . Mage::getBaseDir('base') . '!' . "\n";
                $content .= '#' . "\n";
                $content .= '# Certificat du commercant' . "\n";
                $content .= '#' . "\n";
                $content .= 'F_CERTIFICATE!D_PARM!' . $path . 'certif!' . "\n";
                $content .= '#' . "\n";
                $content .= '# Fichier parametre commercant' . "\n";
                $content .= '#' . "\n";
                $content .= 'F_PARAM!D_PARM!' . $path . 'parmcom!' . "\n";
                $content .= '#' . "\n";
                $content .= '# Fichier des parametres communs' . "\n";
                $content .= '#' . "\n";
                $content .= 'F_DEFAULT!D_PARM!' . $pathfileName . '!' . "\n";
                $content .= '#' . "\n";
                $content .= '# --------------------------------------------------------------------------' . "\n";
                $content .= '# End of file' . "\n";
                $content .= '# --------------------------------------------------------------------------' . "\n";

                if (($fp = fopen($pathfile, 'w'))) {
                    fputs($fp, $content);
                    fclose($fp);
                }
            }
        }

        return $pathfile;
    }

    public function getCertificate() {
        $certificate = null;

        foreach (self::getInstalledCertificates() as $current) {
            if (!isset($current['test'])) {
                return $current;
            }

            if (!isset($certificate)) {
                $certificate = $current;
            }
        }

        return $certificate;
    }

    public function getInstalledCertificates() {
        $certificates = array();
        $path = Mage::getBaseDir('base') . DS . 'lib' . DS . 'atos';

        if (is_dir($path)) {
            $dir = dir($path);
            while ($file = $dir->read()) {
                $data = explode('.', $file);
                $n = sizeof($data) - 1;

                if ($data[0] == 'certif') {
                    $certificates[] = self::getCertificateInfo($data[$n]);
                }
            }

            sort($certificates);
            $dir->close();
        }

        return $certificates;
    }

    public function getCertificateInfo($id) {
        $certificates = self::getPredefinedCertificates();
        return (isset($certificates[$id]))? $certificates[$id] : array('value' => $id, 'label' => $id);
    }

    public function getPredefinedCertificates() {
        $predefined = array(
            '013044876511111' => array(
                'value' => '013044876511111',
                'label' => Mage::helper('atos')->__('Test account eTransaction')
            ),
            '014213245611111' => array(
                'value' => '014213245611111',
                'label' => Mage::helper('atos')->__('Test account Sogenactif')
            ),
            '038862749811111' => array(
                'value' => '038862749811111',
                'label' => Mage::helper('atos')->__('Test account CyberPlus')
            ),
            '082584341411111' => array(
                'value' => '082584341411111',
                'label' => Mage::helper('atos')->__('Test account Mercanet')
            ),
            '014141675911111' => array(
                'value' => '014141675911111',
                'label' => Mage::helper('atos')->__('Test account Scelluis')
            ),
            '014295303911111' => array(
                'value' => '014295303911111',
                'label' => Mage::helper('atos')->__('Test account Sherlocks')
            ),
            '000000014005555' => array(
                'value' => '000000014005555',
                'label' => Mage::helper('atos')->__('Test account Aurore Cetelem')
            )
        );

        return $predefined;
    }

    public function getInstalledParmcom() {
        $parmcom = array();
        $path = Mage::getBaseDir('base') . DS . 'lib' . DS . 'atos'. DS;

        if (is_dir($path)) {
            $dir = dir($path);

            while ($file = $dir->read()) {

                $data = explode('.', $file);

                if (($data[0] == 'parmcom') && file_exists($path . 'certif.fr.' . $data[1])) {
                    $parmcom[] = array(
                        'value' => $file,
                        'label' => $file
                    );
                }
            }

            sort($parmcom);
            $dir->close();
        }

        if (empty($parmcom)) {
            Mage::throwException('Parcom files doesn\'t exist !');
        }

        return $parmcom;
    }

    public function getBankParmcom() {
        $parmcom = array();
        $path = Mage::getBaseDir('base') . DS . 'lib' . DS . 'atos';

        if (is_dir($path)) {
            $dir = dir($path);

            $certificates = array();
            foreach (self::getInstalledCertificates() as $certif)
                $certificates[] = $certif['value'];

            while ($file = $dir->read()) {
                $data = explode('.', $file);
                $n = sizeof($data) - 1;

                if ($data[0] == 'parmcom' && !in_array($data[$n], $certificates)) {
                    $parmcom[] = self::getBankInfo($data[$n]);
                }
            }

            sort($parmcom);
            $dir->close();
        }

        return $parmcom;
    }

    public function getBankInfo($id) {
        $bank = self::getPredefinedBanks();
        return (isset($bank[$id])) ? $bank[$id] : array('value' => $id, 'label' => $id);
    }

    public function getPredefinedBanks() {
        return array(
            'citelis' => array(
                'value' => 'citelis',
                'label' => Mage::helper('atos')->__('Citélis')
            ),
            'cyberplus' => array(
                'value' => 'cyberplus',
                'label' => Mage::helper('atos')->__('Cyberplus')
            ),
            'elysnet' => array(
                'value' => 'elysnet',
                'label' => Mage::helper('atos')->__('Elys Net')
            ),
            'etransactions' => array(
                'value' => 'etransactions',
                'label' => Mage::helper('atos')->__('e-transactions')
            ),
            'mercanet' => array(
                'value' => 'mercanet',
                'label' => Mage::helper('atos')->__('Merc@net')
            ),
            'sherlocks' => array(
                'value' => 'sherlocks',
                'label' => Mage::helper('atos')->__('Sherlock\'s')
            ),
            'scelliusnet' => array(
                'value' => 'scelliusnet',
                'label' => Mage::helper('atos')->__('Scellius Net')
            ),
            'sogenactif' => array(
                'value' => 'sogenactif',
                'label' => Mage::helper('atos')->__('Sogenactif')
            ),
            'webaffaires' => array(
                'value' => 'webaffaires',
                'label' => Mage::helper('atos')->__('Webaffaires')
            )
        );
    }

}
