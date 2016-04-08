<?php
/**
 * universalpay
 *
 * @author    0RS <admin@prestalab.ru>
 * @link http://prestalab.ru/
 * @copyright Copyright &copy; 2009-2016 PrestaLab.Ru
 * @license   http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version 2.0.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_0($object)
{
    return ($object->registerHook('displayAdminOrderContentOrder')
        && $object->registerHook('displayAdminOrderTabOrder')
        && $object->registerHook('displayPaymentReturn')
        && Db::getInstance()->Execute('ALTER TABLE  `' . _DB_PREFIX_ . 'orders` ADD  `up_fields` VARCHAR( 255 ) NOT NULL'));
}
