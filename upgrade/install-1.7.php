<?php
// Sample file for module update
 
if (!defined('_PS_VERSION_'))
  exit;
 
// object module ($this) available
function upgrade_module_1_7($object)
{
  return $object->registerHook('displayOrderDetail');
}
