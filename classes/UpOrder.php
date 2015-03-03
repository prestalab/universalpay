<?php
/**
 * universalpay
 *
 * @author    0RS <admin@prestalab.ru>
 * @link http://prestalab.ru/
 * @copyright Copyright &copy; 2009-2015 PrestaLab.Ru
 * @license   http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version 2.0.0
 */

class UpOrder extends OrderCore
{
	public $up_fields;

	public function __construct($id = null, $id_lang = null)
	{
		self::$definition['fields']['up_fields'] = array('type' => self::TYPE_STRING, 'validate' => 'isString');
		parent::__construct($id, $id_lang);
	}

	public function getUpFields()
	{
		if (!$this->up_fields)
			return array();
		if (!is_array($up_fields = unserialize($this->up_fields)))
			return array();
		return $up_fields;
	}

	public function setUpFields($fields)
	{
		$this->up_fields = serialize($fields);
	}
}