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

function upgrade_module_1_7($object)
{
    return ($object->registerHook('displayOrderDetail')
        && Db::getInstance()->Execute('CREATE TABLE `' . _DB_PREFIX_ . 'universalpay_system_group` (
			`id_universalpay_system` int(10) unsigned NOT NULL,
			`id_group` int(10) unsigned NOT NULL,
			UNIQUE KEY `id_universalpay_system` (`id_universalpay_system`,`id_group`)
			) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8'));
}
