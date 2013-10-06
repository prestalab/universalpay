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
	public  $groupBox;

	protected 	$fieldsValidate = array('active' => 'isBool', 'id_order_state' => 'isUnsignedId');
	protected 	$fieldsRequiredLang = array('name', 'description_short');
	protected 	$fieldsSizeLang = array('name' => 128, 'description_short' => 255);
	protected 	$fieldsValidateLang = array('name' => 'isCatalogName', 'description_short' => 'isCatalogName', 'description' => 'isCleanHtml', 'description_success' => 'isCleanHtml');

	protected 	$table = 'universalpay_system';
	protected 	$identifier = 'id_universalpay_system';
	public		$id_image = 'default';

	public function __construct($id = NULL, $id_lang = NULL){
		$this->image_dir=_PS_IMG_DIR_.'pay/';
		return parent::__construct($id, $id_lang);
	}

	public function getFields()
	{
		parent::validateFields();
		$fields['id_universalpay_system'] = (int)($this->id);
		$fields['active'] = (int)($this->active);
		$fields['position'] = (int)($this->position);
		$fields['id_order_state'] = (int)($this->id_order_state);
		$fields['date_add'] = pSQL($this->date_add);
		$fields['date_upd'] = pSQL($this->date_upd);
		return $fields;
	}

	public function getTranslationsFieldsChild()
	{
		self::validateFieldsLang();

		$fieldsArray = array('name');
		$fields = array();
		$languages = Language::getLanguages(false);
		$defaultLanguage = Configuration::get('PS_LANG_DEFAULT');
		foreach ($languages as $language)
		{
			$fields[$language['id_lang']]['id_lang'] = $language['id_lang'];
			$fields[$language['id_lang']][$this->identifier] = (int)($this->id);
			$fields[$language['id_lang']]['description_short'] = (isset($this->description_short[$language['id_lang']])) ? pSQL($this->description_short[$language['id_lang']], true) : '';
			$fields[$language['id_lang']]['description'] = (isset($this->description[$language['id_lang']])) ? pSQL($this->description[$language['id_lang']], true) : '';
			$fields[$language['id_lang']]['description_success'] = (isset($this->description_success[$language['id_lang']])) ? pSQL($this->description_success[$language['id_lang']], true) : '';
			foreach ($fieldsArray as $field)
			{
				if (!Validate::isTableOrIdentifier($field))
					die(Tools::displayError());

				/* Check fields validity */
				if (isset($this->{$field}[$language['id_lang']]) AND !empty($this->{$field}[$language['id_lang']]))
					$fields[$language['id_lang']][$field] = pSQL($this->{$field}[$language['id_lang']]);
				elseif (in_array($field, $this->fieldsRequiredLang))
				{
					if ($this->{$field} != '')
						$fields[$language['id_lang']][$field] = pSQL($this->{$field}[$defaultLanguage]);
				}
				else
					$fields[$language['id_lang']][$field] = '';
			}
		}
		return $fields;
	}
	
	public static function getPaySystems($id_lang, $active = true, $id_carrier=false, $id_groups=false)
	{
	 	if (!Validate::isBool($active))
	 		die(Tools::displayError());

		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
			SELECT *
			FROM `'._DB_PREFIX_.'universalpay_system` us
			LEFT JOIN `'._DB_PREFIX_.'universalpay_system_lang` usl ON us.`id_universalpay_system` = usl.`id_universalpay_system`
			'.($id_carrier?'JOIN `'._DB_PREFIX_.'universalpay_system_carrier` usc ON (us.`id_universalpay_system` = usc.`id_universalpay_system` AND usc.`id_carrier`='.(int)$id_carrier.')':'').'
			'.($id_groups?'JOIN `'._DB_PREFIX_.'universalpay_system_group` usg ON (us.`id_universalpay_system` = usg.`id_universalpay_system` AND usg.`id_group` IN ('.implode(',', array_map('intval', $id_groups)).'))':'').'
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
			Db::getInstance()->autoExecute(_DB_PREFIX_.'universalpay_system_carrier', $row, 'INSERT');
		}
	}

	/**
	 * Update Carrier
	 */
	public function updateCarrier($old_carrier_id, $new_carrier_id)
	{
		Db::getInstance()->autoExecute(_DB_PREFIX_.'universalpay_system_carrier', array('id_carrier'=>(int)$new_carrier_id), 'UPDATE', 'id_carrier='.(int)$old_carrier_id);
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

	public function getGroups()
	{
		$groups = array();
		$result = Db::getInstance()->executeS('
			SELECT usg.`id_group`
			FROM '._DB_PREFIX_.'universalpay_system_group usg
			WHERE usg.`id_universalpay_system` = '.(int)$this->id
		);
		foreach ($result as $group)
			$groups[] = $group['id_group'];
		return $groups;
	}

	public function addGroups($groups)
	{
		foreach ($groups as $group)
		{
			$row = array('id_universalpay_system' => (int)$this->id, 'id_group' => (int)$group);
			Db::getInstance()->autoExecute(_DB_PREFIX_.'universalpay_system_group', $row, 'INSERT');
		}
	}

	public function updateGroup($old_group_id, $new_group_id)
	{
		Db::getInstance()->autoExecute(_DB_PREFIX_.'universalpay_system_group', array('id_group'=>(int)$new_group_id), 'UPDATE', 'id_group='.(int)$old_group_id);
	}

	/**
	 * Delete Carrier
	 */
	public function deleteGroup($id_group=false)
	{
		return Db::getInstance()->execute('
			DELETE FROM `'._DB_PREFIX_.'universalpay_system_group`
			WHERE `id_universalpay_system` = '.(int)$this->id.'
			'.($id_group?'AND `id_group` = '.(int)$id_group.' LIMIT 1':'')
		);
	}

	public function delete()
	{
		return ($this->deleteCarrier()
			&&$this->deleteGroup()
			&&parent::delete()
			);
	}

	public function updateCarriers($list)
	{
		$this->deleteCarrier();
		if ($list && !empty($list))
			$this->addCarriers($list);
	}

	public function updateGroups($list)
	{
		$this->deleteGroup();
		if ($list && !empty($list))
			$this->addGroups($list);
	}

	public function add($autodate = true, $null_values = false)
	{
		$ret = parent::add($autodate, $null_values);
		$this->updateCarriers($this->carrierBox);
		$this->updateGroups($this->groupBox);
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


