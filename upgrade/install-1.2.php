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

function upgrade_module_1_2($object)
{
    return (Db::getInstance()->Execute('ALTER TABLE  `' . _DB_PREFIX_ . 'universalpay_system_lang` ADD  `description_success` TEXT NULL')
        && Db::getInstance()->Execute('ALTER TABLE  `' . _DB_PREFIX_ . 'universalpay_system` ADD  `id_order_state` INT( 10 ) NOT NULL DEFAULT \'' .
            Configuration::get('PS_OS_PREPARATION') . '\''));
}
