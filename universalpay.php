<?php
class universalpay extends PaymentModule
{
	public function __construct()
	{
		$this->name = 'universalpay';
		$this->tab = 'payments_gateways';
		$this->version = '1.9.1';
		$this->author = 'PrestaLab.Ru';
		$this->need_instance = 1;
		$this->module_key='a4e3c26ec6e4316dccd6d7da5ca30411';

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		$this->bootstrap = true;
 
		parent::__construct();

		$this->displayName = $this->l('Universal Payment Module');
		$this->description = $this->l('Payment methods creating.');
	}

	public function install()
	{
		Db::getInstance()->Execute("CREATE TABLE `"._DB_PREFIX_."universalpay_system` (
				`id_universalpay_system` INT(10) NOT NULL AUTO_INCREMENT,
				`id_order_state` INT( 10 ) NOT NULL DEFAULT  '".Configuration::get('PS_OS_PREPARATION')."',
				`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
				`position` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`date_add` DATETIME NOT NULL,
				`date_upd` DATETIME NOT NULL,
				PRIMARY KEY (`id_universalpay_system`)
			) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8");
		Db::getInstance()->Execute("CREATE TABLE `"._DB_PREFIX_."universalpay_system_lang` (
				`id_universalpay_system` INT(10) UNSIGNED NOT NULL,
				`id_lang` INT(10) UNSIGNED NOT NULL,
				`name` VARCHAR(128) NOT NULL,
				`description_short` VARCHAR(255) NOT NULL,
				`description` TEXT NULL,
				`description_success` TEXT NULL,
				UNIQUE INDEX `universalpay_system_lang_index` (`id_universalpay_system`, `id_lang`)
			) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8");
		Db::getInstance()->Execute("CREATE TABLE `"._DB_PREFIX_."universalpay_system_carrier` (
		  `id_universalpay_system` int(10) unsigned NOT NULL,
		  `id_carrier` int(10) unsigned NOT NULL,
		  UNIQUE KEY `id_universalpay_system` (`id_universalpay_system`,`id_carrier`)
		) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8");
		Db::getInstance()->Execute("CREATE TABLE `"._DB_PREFIX_."universalpay_system_group` (
		  `id_universalpay_system` int(10) unsigned NOT NULL,
		  `id_group` int(10) unsigned NOT NULL,
		  UNIQUE KEY `id_universalpay_system` (`id_universalpay_system`,`id_group`)
		) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8");

		return parent::install()
		       && $this->registerHook('displayPayment')
		       && $this->registerHook('actionCarrierUpdate')
		       && $this->registerHook('displayOrderDetail')
		       && mkdir(_PS_IMG_DIR_.'pay')
		       && self::installModuleTab('AdminUniPaySystem', array('ru' => 'Платежные системы', 'default' => 'Pay Systems', 'it' =>'Metodi di pagamento'), 'AdminParentModules');
	}

	public function uninstall()
	{
		Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.'universalpay_system`');
		Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.'universalpay_system_lang`');
		Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.'universalpay_system_carrier`');

		self::uninstallModuleTab('AdminUniPaySystem');
		return self::rrmdir(_PS_IMG_DIR_.'pay')
		       && parent::uninstall();
	}

	function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
		return true;
	}

	private function installModuleTab($tabClass, $tabName, $TabParent)
	{
		if(!($idTabParent = Tab::getIdFromClassName($TabParent)))
			return false;

		@copy(_PS_MODULE_DIR_.$this->name.'/logo.gif', _PS_IMG_DIR_.'t/'.$tabClass.'.gif');
		$tab = new Tab();
		$languages = Language::getLanguages(true);
		foreach ($languages as $language)
		{
			if (!isset($tabName[$language['iso_code']]))
				$tab->name[$language['id_lang']] = $tabName['default'];
			else
				$tab->name[(int)$language['id_lang']] = $tabName[$language['iso_code']];
		}
		$tab->class_name = $tabClass;
		$tab->module = $this->name;
		$tab->id_parent = $idTabParent;
		$tab->active= 1;

		if(!$tab->save())
			return false;
		return true;
	}

	private function uninstallModuleTab($tabClass)
	{
		$idTab = Tab::getIdFromClassName($tabClass);
		if($idTab != 0)
		{
			$tab = new Tab($idTab);
			$tab->delete();
			return true;
		}
		return false;
	}

	public function hookactionCarrierUpdate($params)
	{
		require_once(dirname(__FILE__). '/UniPaySystem.php');
		UniPaySystem::updateCarrier($params['id_carrier'], $params['carrier']->id);
	}

	public function hookdisplayOrderDetail($params)
	{
		if($params['order']->module!=$this->name)
			return false;

		require_once(dirname(__FILE__). '/UniPaySystem.php');

		if(!($id_paysystem=UniPaySystem::getIdByName($params['order']->payment)))
			return false;

		$paysystem = new UniPaySystem($id_paysystem, $this->context->cookie->id_lang);
		return str_replace(
				array('%total%', '%order_number%'),
				array(Tools::DisplayPrice($params['order']->total_paid), '#'.$params['order']->reference),
				$paysystem->description_success
			);
	}

	public function hookdisplayPayment($params)
	{
		if (!$this->active)
			return ;
		if (!$this->_checkCurrency($params['cart']))
			return ;

		require_once(dirname(__FILE__). '/UniPaySystem.php');

		$paysystems=UniPaySystem::getPaySystems($this->context->language->id, true, $this->context->cart->id_carrier, $this->context->customer->getGroups());
		foreach($paysystems as &$paysystem)
			$paysystem['description']=str_replace(
				array('%total%'),
				array(Tools::DisplayPrice($params['cart']->getOrderTotal(true, Cart::BOTH))),
				$paysystem['description']
			);
		unset($paysystem);
		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
			'universalpay' => $paysystems,
			'universalpay_onepage' => Configuration::get('universalpay_onepage'),
		));
		return $this->display(__FILE__, 'payment.tpl');
	}
	
	public function _checkCurrency($cart)
	{
		$currency_order = new Currency((int)($cart->id_currency));
		$currencies_module = $this->getCurrency((int)$cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}

	public function getContent()
	{
		$output = '';
		$output .= $this->_postProcess();
		$output .= $this->renderSettingsForm();
		$output .='<div id="dev_div" style="width:300px;margin-left:15px;margin-top:10px"><br>
				<span><strong>'.$this->l('Version').': </strong>'.$this->version.'</span><br>
				<span><strong>'.$this->l('License').':</strong> <a class="link" href="http://www.opensource.org/licenses/osl-3.0.php" target="_blank">OSL 3.0</a></span><br>
				<span><strong>'.$this->l('Developer').':</strong> <a class="link" href="mailto:admin@prestalab.ru" target="_blank">'.$this->author.'</a><br>
				<span><strong>'.$this->l('Description').':</strong> <a class="link" href="http://prestalab.ru/moduli-oplaty/46-universalnyj-modul-oplaty.html" target="_blank">PrestaLab.ru</a><br>
				<p style="text-align:center"><a href="http://prestalab.ru/"><img src="'.$this->_path.'banner.png" alt="'.$this->l('Modules and Templates for PrestaShop').'"/></a></p>
			</div>';
		return $output;
	}

	private function initToolbar()
	{
		$this->toolbar_btn['save'] = array(
			'href' => '#',
			'desc' => $this->l('Save')
		);
		return $this->toolbar_btn;
	}

	private function renderSettingsForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs',
				),
				'description' => $this->l('Add payment methods on').' <a href="?tab=AdminUniPaySystem&token='.Tools::getAdminToken('AdminUniPaySystem'.(int)(Tab::getIdFromClassName('AdminUniPaySystem')).(int)($this->context->cookie->id_employee)).'" class="link">'.$this->l('Modules>Pay Systems tab').'</a>',
				'input' => array(
					array(
						'type' => 'switch',
						'label' => $this->l('Confirmation button'),
						'hint' => $this->l('Confirmation button directly in the checkout page'),
						'name' => 'universalpay_onepage',
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'universalpay_onepage_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'universalpay_onepage_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						)
					),
				),
				'submit' => array(
					'name' => 'submitSave',
					'title' => $this->l('Save'),
				),
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitSave';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
			.'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id,
		);

		return $helper->generateForm(array($fields_form));

	}

	public function getConfigFieldsValues()
	{
		$fields_value = array();
		$fields_value['universalpay_onepage'] = Configuration::get('universalpay_onepage');

		return $fields_value;
	}

	protected function _postProcess()
	{
		if (Tools::isSubmit('submitSave'))
			if (Configuration::updateValue('universalpay_onepage', (int)Tools::getValue('universalpay_onepage')))
				return $this->displayConfirmation($this->l('Settings updated'));
			else
				return $this->displayError($this->l('Confirmation button').': '.$this->l('Invaild choice'));
	}
}
