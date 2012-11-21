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
		       && $this->registerHook('payment')
		       && $this->registerHook('updateCarrier')
		       && mkdir(_PS_IMG_DIR_.'pay')
		       && self::installModuleTab('AdminUniPaySystem', array('ru' => 'Платежные системы', 'default' => 'Pay Systems'), 'AdminModules');
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

	public function hookupdateCarrier($params)
	{
		require_once(dirname(__FILE__). '/UniPaySystem.php');
		UniPaySystem::updateCarrier($params['id_carrier'], $params['carrier']->id);
	}

	public function hookPayment($params)
	{
		if (!$this->active)
			return ;
		if (!$this->_checkCurrency($params['cart']))
			return ;

		require_once(dirname(__FILE__). '/UniPaySystem.php');
		global $smarty, $cookie, $cart;

		$paysystems=UniPaySystem::getPaySystems($cookie->id_lang, true, $cart->id_carrier);
		foreach($paysystems as &$paysystem)
			$paysystem['description']=str_replace(
				array('%total%'),
				array(Tools::DisplayPrice($params['cart']->getOrderTotal(true, Cart::BOTH))),
				$paysystem['description']
			);
		unset($paysystem);

		$smarty->assign(array(
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

	public function getPathFile()
	{
		return __FILE__;
	}

	private function _displayForm()
	{
		$this->_html .= '
		<fieldset style="width: 300px;float:right;margin-left:15px;">
			<legend><img src="../img/admin/manufacturers.gif"/> ' . $this->l('Information') . '</legend>
			<div id="dev_div">
				<span><strong>' . $this->l('Version') . ':</strong> ' . $this->version . '</span><br>
				<span><strong>' . $this->l('License') . ':</strong> <a class="link" href="http://www.opensource.org/licenses/osl-3.0.php" target="_blank">OSL 3.0</a></span><br>
				<span><strong>' . $this->l('Developer') . ':</strong> <a class="link" href="mailto:admin@prestalab.ru" target="_blank">' . $this->author . '</a><br>
				<span><strong>' . $this->l('Description') . ':</strong> <a class="link" href="http://prestalab.ru/" target="_blank">PrestaLab.ru</a><br>
				<p style="text-align:center"><a href="http://prestalab.ru/"><img src="http://prestalab.ru/upload/banner.png" alt="' . $this->l('Modules and Templates for PrestaShop') . '"/></a></p>
			</div>
		</fieldset>
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
			<fieldset>
				<legend><img src="../img/admin/contact.gif" alt=""/>' . $this->l('Settings') . '</legend>
					<label for="universalpay_onepage">' . $this->l('Confirmation button') . '</label>
					<div class="margin-form">
						<input type="radio" name="universalpay_onepage" id="universalpay_onepage_on" value="1" '.(Tools::getValue('universalpay_onepage', Configuration::get('universalpay_onepage')) ? 'checked="checked" ' : '').'/>
						<label class="t" for="universalpay_onepage_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Enabled').'" /></label>
						<input type="radio" name="universalpay_onepage" id="universalpay_onepage_off" value="0" '.(!Tools::getValue('universalpay_onepage', Configuration::get('universalpay_onepage')) ? 'checked="checked" ' : '').'/>
						<label class="t" for="universalpay_onepage_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('Disabled').'" /></label>
						<p class="clear">' . $this->l('Confirmation button on paysystems page') . '</p>
					</div>

				<p class="center">
					<input class="button" type="submit" name="submitSave" value="' . $this->l('Save') . '"/>
				</p>
			</fieldset>
		</form>
		';
	}

	protected function _postProcess()
	{
		if (Tools::isSubmit('submitSave'))
		{
			Configuration::updateValue('universalpay_onepage', (int)Tools::getValue('universalpay_onepage'));
			$this->_html .= $this->displayConfirmation($this->l('Settings updated.'));
		}
	}
}