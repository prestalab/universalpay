<?php
class UniPaySystem extends ObjectModel
{
	public  $id;
	public  $active = 1;
	public  $id_order_state=3;
	public  $position;
	public  $date_add;
	public  $date_upd;

	public  $name;
	public  $description_short;
	public  $description;
	public  $description_success;

	public  $image_dir;

	public  $carrierBox;

	public static $definition = array(
		'table' => 'universalpay_system',
		'primary' => 'id_universalpay_system',
		'multilang' => true,
		'fields' => array(
			/* Classic fields */
			'active' => 				array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
			'date_add' => 				array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat'),
			'date_upd' => 				array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat'),
			'id_order_state' => 		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),

			/* Lang fields */
			'name' => 					array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128),
			'description' => 			array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isString'),
			'description_success' => 	array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isString'),
			'description_short' => 		array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 256),
		),
	);

	public function __construct($id = NULL, $id_lang = NULL){
		$this->image_dir=_PS_IMG_DIR_.'pay/';
		return parent::__construct($id, $id_lang);
	}
	
	public static function getPaySystems($id_lang, $active = true, $id_carrier=false)
	{
	 	if (!Validate::isBool($active))
	 		die(Tools::displayError());

		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
			SELECT *
			FROM `'._DB_PREFIX_.'universalpay_system` us
			LEFT JOIN `'._DB_PREFIX_.'universalpay_system_lang` usl ON us.`id_universalpay_system` = usl.`id_universalpay_system`
			'.($id_carrier?'JOIN `'._DB_PREFIX_.'universalpay_system_carrier` usc ON (us.`id_universalpay_system` = usc.`id_universalpay_system` AND usc.`id_carrier`='.(int)$id_carrier.')':'').'
			WHERE `id_lang` = '.(int)($id_lang).
			($active ? ' AND `active` = 1' : '').'
			ORDER BY us.`position` ASC'
		);

		return $result;
	}

	public function getCarriers()
	{
		$carriers = array();
		$result = Db::getInstance()->executeS('
			SELECT usc.`id_carrier`
			FROM '._DB_PREFIX_.'universalpay_system_carrier usc
			WHERE usc.`id_universalpay_system` = '.(int)$this->id
		);
		foreach ($result as $carrier)
			$carriers[] = $carrier['id_carrier'];
		return $carriers;
	}

	/**
	 * Add Carrier
	 */
	public function addCarriers($carriers)
	{
		foreach ($carriers as $carrier)
		{
			$row = array('id_universalpay_system' => (int)$this->id, 'id_carrier' => (int)$carrier);
			Db::getInstance()->insert('universalpay_system_carrier', $row);
		}
	}
	/**
	 * Delete Carrier
	 */
	public function deleteCarrier($id_carrier=false)
	{
		return Db::getInstance()->execute('
			DELETE FROM `'._DB_PREFIX_.'universalpay_system_carrier`
			WHERE `id_universalpay_system` = '.(int)$this->id.'
			'.($id_carrier?'AND `id_carrier` = '.(int)$id_carrier.' LIMIT 1':'')
		);
	}

	public function delete()
	{
		return ($this->deleteCarrier()
			&&parent::delete()
			);
	}

	public function updateCarriers($list)
	{
		$this->deleteCarrier();
		if ($list && !empty($list))
			$this->addCarriers($list);
	}

	public function add($autodate = true, $null_values = false)
	{
		$ret = parent::add($autodate, $null_values);
		$this->updateCarriers($this->carrierBox);
		return $ret;
	}

/*	public function update($null_values = false)
	{
		$ret = parent::update($null_values);
		$this->updateCarriers($this->carrierBox);
		return $ret;
	}
*/
}


