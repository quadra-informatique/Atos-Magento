<?php

/**
 * 1997-2013 Quadra Informatique
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to modules@quadra-informatique.fr so we can send you a copy immediately.
 *
 * @author Quadra Informatique <modules@quadra-informatique.fr>
 * @copyright 1997-2013 Quadra Informatique
 * @license http://www.opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
$installer = $this;

$installer->startSetup();

$methods = array(
    'atos_standard' => 'config_standard',
    'atos_several' => 'config_several',
    'atos_aurore' => 'config_aurore',
    'atos_euro' => 'config_euro'
);

foreach ($methods as $key => $value) {

    $installer->run("
        UPDATE `{$installer->getTable('core_config_data')}`
        SET `path` = 'atos/{$value}/merchant_id'
        WHERE `path` = 'payment/{$key}/merchant_id';

        UPDATE `{$installer->getTable('core_config_data')}`
        SET `path` = 'atos/{$value}/pathfile'
        WHERE `path` = 'payment/{$key}/pathfile';

        UPDATE `{$installer->getTable('core_config_data')}`
        SET `path` = 'atos/{$value}/check_ip_address'
        WHERE `path` = 'payment/{$key}/check_ip_address';

        UPDATE `{$installer->getTable('core_config_data')}`
        SET `path` = 'atos/{$value}/capture_mode'
        WHERE `path` = 'payment/{$key}/capture_mode';

        UPDATE `{$installer->getTable('core_config_data')}`
        SET `path` = 'atos/{$value}/capture_days'
        WHERE `path` = 'payment/{$key}/capture_days';

        UPDATE `{$installer->getTable('core_config_data')}`
        SET `path` = 'atos/{$value}/templatefile'
        WHERE `path` = 'payment/{$key}/templatefile';

        UPDATE `{$installer->getTable('core_config_data')}`
        SET `path` = 'atos/{$value}/data_field'
        WHERE `path` = 'payment/{$key}/data_field';
    ");
}

$installer->run("
    UPDATE `{$installer->getTable('core_config_data')}`
    SET `path` = 'atos/config_bin_files/bin_request'
    WHERE `path` = 'payment/atos_standard/bin_request';

    UPDATE `{$installer->getTable('core_config_data')}`
    SET `path` = 'atos/config_bin_files/bin_response'
    WHERE `path` = 'payment/atos_standard/bin_response';
");

$installer->endSetup();