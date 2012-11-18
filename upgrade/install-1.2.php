<?php
// Sample file for module update
 
if (!defined('_PS_VERSION_'))
  exit;
 
// object module ($this) available
function upgrade_module_1_2($object)
{
  return (
	  Db::getInstance()->Execute("ALTER TABLE  `"._DB_PREFIX_."universalpay_system_lang` ADD  `description_success` TEXT NULL")
      &&Db::getInstance()->Execute("ALTER TABLE  `"._DB_PREFIX_."universalpay_system` ADD  `id_order_state` INT( 10 ) NOT NULL DEFAULT  '".Configuration::get('PS_OS_PREPARATION')."'")
  );
}
