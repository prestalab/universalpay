<?php
class universalpay extends PaymentModule
{
	public function __construct()
	{
		$this->name = 'universalpay';
		$this->tab = 'payments_gateways';
		$this->version = '1.4';
		$this->author = 'PrestaLab.Ru';
		$this->need_instance = 1;
		$this->module_key='a4e3c26ec6e4316dccd6d7da5ca30411';

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
 
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

		return parent::install()
		       && $this->registerHook('displayPayment')
		       && $this->registerHook('actionCarrierUpdate')
		       && mkdir(_PS_IMG_DIR_.'pay')
		       && self::installModuleTab('AdminUniPaySystem', array('ru' => 'Платежные системы', 'default' => 'Pay Systems'), 'AdminParentModules');
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


	public function hookdisplayPayment($params)
	{
		if (!$this->active)
			return ;
		if (!$this->_checkCurrency($params['cart']))
			return ;

		require_once(dirname(__FILE__). '/UniPaySystem.php');

		$paysystems=UniPaySystem::getPaySystems($this->context->cookie->id_lang, true, $this->context->cart->id_carrier);
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
		$this->_postProcess();
		$this->_displayForm();
		return $this->_html;
	}

	private function initToolbar()
	{
		$this->toolbar_btn['save'] = array(
			'href' => '#',
			'desc' => $this->l('Save')
		);
		return $this->toolbar_btn;
	}

	protected function _displayForm()
	{
		$this->_display = 'index';


		$this->fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Settings'),
				'image' => _PS_ADMIN_IMG_.'information.png'
			),
			'description'=>$this->l('Add payment methods on').' <a href="?tab=AdminUniPaySystem&token='.Tools::getAdminToken('AdminUniPaySystem'.(int)(Tab::getIdFromClassName('AdminUniPaySystem')).(int)($this->context->cookie->id_employee)).'" class="link">'.$this->l('Payments>Pay Systems tab').'</a>',
			'input' => array(
				array(
					'type' => 'radio',
					'label' => $this->l('Confirmation button'),
					'desc' => $this->l('Confirmation button on paysystems page'),
					'name' => 'universalpay_onepage',
					'class' => 't',
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
				'class' => 'button'
			)
		);

		$this->fields_value['universalpay_onepage'] = Configuration::get('universalpay_onepage');


		$helper = $this->initForm();
		$helper->submit_action = '';

		$helper->title = $this->displayName;

		$helper->fields_value = $this->fields_value;
		$this->_html .= $helper->generateForm($this->fields_form);
		return;
	}

	private function initForm()
	{
		$helper = new HelperForm();

		$helper->module = $this;
		$helper->name_controller = 'universalpay';
		$helper->identifier = $this->identifier;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		$helper->toolbar_scroll = true;
		$helper->tpl_vars['version'] = $this->version;
		$helper->tpl_vars['author'] = $this->author;
		$helper->tpl_vars['this_path'] = $this->_path;
		$helper->toolbar_btn = $this->initToolbar();

		return $helper;
	}

	protected function _postProcess()
	{
		if (Tools::isSubmit('submitSave'))
			Configuration::updateValue('universalpay_onepage', (int)Tools::getValue('universalpay_onepage'));
	}
}