<?php
/**
 * universalpay module main file.
 *
 * @author    0RS <admin@prestalab.ru>
 * @link http://prestalab.ru/
 * @copyright Copyright &copy; 2009-2016 PrestaLab.Ru
 * @license   http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version 2.0.1
 */
class Universalpay extends PaymentModule
{
    private $paysystems = false;

    public function __construct()
    {
        $this->name = 'universalpay';
        $this->tab = 'payments_gateways';
        $this->version = '2.4.0';
        $this->author = 'PrestaLab.Ru';
        $this->need_instance = 1;
        $this->module_key = 'a4e3c26ec6e4316dccd6d7da5ca30411';
        $this->controllers = array('payment', 'validation');
        $this->ps_versions_compliancy['min'] = '1.6.0';
        $this->author_uri = 'http://addons.prestashop.com/ru/payments-gateways/5507-universal-payment-module.html';

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Universal Payment Module');
        $this->description = $this->l('Payment methods creating.');
        Shop::addTableAssociation('universalpay_system', array('type' => 'shop'));
    }

    public function install()
    {
        Db::getInstance()->Execute('CREATE TABLE `' . _DB_PREFIX_ . 'universalpay_system` (
				`id_universalpay_system` INT(10) NOT NULL AUTO_INCREMENT,
				`id_order_state` INT( 10 ) NOT NULL DEFAULT \'' . Configuration::get('PS_OS_PREPARATION') . '\',
				`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\',
				`position` INT(10) UNSIGNED NOT NULL DEFAULT \'0\',
				`id_cart_rule` INT(10) UNSIGNED NOT NULL DEFAULT \'0\',
				`cart_type` tinyint(4) NOT NULL DEFAULT \'0\',
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL,
				PRIMARY KEY (`id_universalpay_system`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8');
        Db::getInstance()->Execute('CREATE TABLE `' . _DB_PREFIX_ . 'universalpay_system_lang` (
				`id_universalpay_system` INT(10) UNSIGNED NOT NULL,
				`id_lang` INT(10) UNSIGNED NOT NULL,
				`name` VARCHAR(128) NOT NULL,
				`description_short` VARCHAR(255) NOT NULL,
				`description` TEXT NULL,
				`description_success` TEXT NULL,
				UNIQUE INDEX `universalpay_system_lang_index` (`id_universalpay_system`, `id_lang`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8');
        Db::getInstance()->Execute('CREATE TABLE `' . _DB_PREFIX_ . 'universalpay_system_carrier` (
		  `id_universalpay_system` int(10) unsigned NOT NULL,
		  `id_carrier` int(10) unsigned NOT NULL,
		  UNIQUE KEY `id_universalpay_system` (`id_universalpay_system`,`id_carrier`)
		) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8');
        Db::getInstance()->Execute('CREATE TABLE `' . _DB_PREFIX_ . 'universalpay_system_group` (
		  `id_universalpay_system` int(10) unsigned NOT NULL,
		  `id_group` int(10) unsigned NOT NULL,
		  UNIQUE KEY `id_universalpay_system` (`id_universalpay_system`,`id_group`)
		) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8');
        Db::getInstance()->Execute('ALTER TABLE  `' . _DB_PREFIX_ . 'orders` ADD  `up_fields` VARCHAR( 255 ) NOT NULL DEFAULT ""');
        Db::getInstance()->Execute('CREATE TABLE `' . _DB_PREFIX_ . 'universalpay_system_shop` (
		  `id_universalpay_system` int(10) unsigned NOT NULL,
		  `id_shop` int(10) unsigned NOT NULL,
		  `date_add` datetime NOT NULL,
          `date_upd` datetime NOT NULL,
		  UNIQUE KEY `id_universalpay_system` (`id_universalpay_system`,`id_shop`)
		) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8');

        return parent::install()
        && $this->registerHook('displayPayment')
        && $this->registerHook('actionCarrierUpdate')
        && $this->registerHook('displayOrderDetail')
        && $this->registerHook('displayAdminOrderContentOrder')
        && $this->registerHook('displayAdminOrderTabOrder')
        && $this->registerHook('displayPaymentReturn')
        && $this->registerHook('advancedPaymentOptions')
        && mkdir(_PS_IMG_DIR_ . 'pay')
        && self::installModuleTab('AdminUniPaySystem',
            array('ru' => 'Платежные системы', 'default' => 'Pay Systems', 'it' => 'Metodi di pagamento', 'cs' => 'Platební metody'),
            'AdminParentModules');
    }

    public function uninstall()
    {
        Db::getInstance()->Execute('DROP TABLE `' . _DB_PREFIX_ . 'universalpay_system`');
        Db::getInstance()->Execute('DROP TABLE `' . _DB_PREFIX_ . 'universalpay_system_lang`');
        Db::getInstance()->Execute('DROP TABLE `' . _DB_PREFIX_ . 'universalpay_system_carrier`');
        Db::getInstance()->Execute('DROP TABLE `' . _DB_PREFIX_ . 'universalpay_system_group`');
        Db::getInstance()->Execute('ALTER TABLE  `' . _DB_PREFIX_ . 'orders` DROP  `up_fields`');
        Db::getInstance()->Execute('DROP TABLE `' . _DB_PREFIX_ . 'universalpay_system_shop`');

        self::uninstallModuleTab('AdminUniPaySystem');
        return self::rrmdir(_PS_IMG_DIR_ . 'pay')
        && parent::uninstall();
    }

    public function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir . '/' . $object) == 'dir') {
                        self::rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }

            }
            reset($objects);
            rmdir($dir);
        }
        return true;
    }

    private function installModuleTab($tab_class, $tab_name, $tab_parent)
    {
        if (!($id_tab_parent = Tab::getIdFromClassName($tab_parent))) {
            return false;
        }

        $tab = new Tab();
        $languages = Language::getLanguages(true);
        foreach ($languages as $language) {
            if (!isset($tab_name[$language['iso_code']])) {
                $tab->name[$language['id_lang']] = $tab_name['default'];
            } else {
                $tab->name[(int)$language['id_lang']] = $tab_name[$language['iso_code']];
            }
        }
        $tab->class_name = $tab_class;
        $tab->module = $this->name;
        $tab->id_parent = $id_tab_parent;
        $tab->active = 1;

        if (!$tab->save()) {
            return false;
        }
        return true;
    }

    private function uninstallModuleTab($tab_class)
    {
        $id_tab = Tab::getIdFromClassName($tab_class);
        if ($id_tab != 0) {
            $tab = new Tab($id_tab);
            $tab->delete();
            return true;
        }
        return false;
    }

    public function hookdisplayPaymentReturn($params)
    {
        require_once(dirname(__FILE__) . '/classes/UniPaySystem.php');
        $paysistem = new UniPaySystem((int)Tools::getValue('id_universalpay_system'), $this->context->cookie->id_lang);
        $description_success = str_replace(array('%total%', '%order_number%', '%order_id%'),
            array(
                Tools::DisplayPrice($params['objOrder']->total_paid),
                '#' . $params['objOrder']->reference,
                $params['objOrder']->id
            ),
            $paysistem->description_success);

        require_once(dirname(__FILE__) . '/classes/UpOrder.php');
        $up_order = new UpOrder($params['objOrder']->id);
        $fields = $up_order->getUpFields();
        foreach ($fields as $key => $field)
            $description_success = str_replace('%up_'.$key.'%', $field, $description_success);

        return '<div class="box">' . $description_success . '</div>';
    }

    public function hookdisplayAdminOrderTabOrder($params)
    {
        return $this->display(__FILE__, 'displayAdminOrderTabOrder.tpl');
    }

    public function hookdisplayAdminOrderContentOrder($params)
    {
        require_once(dirname(__FILE__) . '/classes/UpOrder.php');
        $order = new UpOrder($params['order']->id);
        $this->smarty->assign(array(
            'up_fields' => $order->getUpFields()
        ));
        return $this->display(__FILE__, 'displayAdminOrderContentOrder.tpl');
    }

    public function hookactionCarrierUpdate($params)
    {
        require_once(dirname(__FILE__) . '/classes/UniPaySystem.php');
        UniPaySystem::updateCarrier($params['id_carrier'], $params['carrier']->id);
    }

    public function hookdisplayOrderDetail($params)
    {
        if ($params['order']->module != $this->name) {
            return false;
        }

        require_once(dirname(__FILE__) . '/classes/UniPaySystem.php');

        if (!($id_paysystem = UniPaySystem::getIdByName($params['order']->payment))) {
            return false;
        }

        $paysystem = new UniPaySystem($id_paysystem, $this->context->cookie->id_lang);
        return str_replace(array('%total%', '%order_number%'),
            array(Tools::DisplayPrice($params['order']->total_paid), '#' . $params['order']->reference),
            $paysystem->description_success);
    }

    public function hookdisplayPayment($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $virtual = $this->context->cart->isVirtualCart();
        $paysystems = $this->getPaySystems($params);
        foreach ($paysystems as $key => $paysystem)
        {
            if (($paysystem['cart_type'] == UniPaySystem::CART_REAL) && $virtual)
                unset($paysystems[$key]);
            elseif (($paysystem['cart_type'] == UniPaySystem::CART_VIRTUAL) && !$virtual)
                unset($paysystems[$key]);
        }
        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'universalpay' => $paysystems,
            'universalpay_onepage' => Configuration::get('universalpay_onepage'),
        ));
        return $this->display(__FILE__, 'payment.tpl');
    }

    public function getPaySystems($params)
    {
        if ($this->paysystems) {
            return $this->paysystems;
        }

        require_once(dirname(__FILE__) . '/classes/UniPaySystem.php');

        $paysystems = UniPaySystem::getPaySystems($this->context->language->id, true,
            $this->context->cart->id_carrier, $this->context->customer->getGroups());

        foreach ($paysystems as &$paysystem) {
            $paysystem['description'] = str_replace(array('%total%'),
                array(Tools::DisplayPrice($params['cart']->getOrderTotal(true, Cart::BOTH))),
                $paysystem['description']);
        }
        unset($paysystem);
        $this->paysystems = $paysystems;
        return $paysystems;
    }

    public function hookAdvancedPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $options = array();
        $paysystems = $this->getPaySystems($params);
        foreach ($paysystems as $paysystem) {
            $po = new Core_Business_Payment_PaymentOption();
            $po->setCallToActionText($paysystem['name'])
                ->setAction($this->context->link->getModuleLink($this->name, 'payment',
                    array('id_universalpay_system' => $paysystem['id_universalpay_system']), true))
                ->setLogo(Media::getMediaPath(_PS_IMG_ . 'pay/' . $paysystem['id_universalpay_system'] . '.jpg'))
                ->setModuleName($this->name);
            $options[] = $po;
        }
        return $options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getContent()
    {
        $output = '';
        $output .= $this->postProcess();
        $output .= $this->renderSettingsForm();
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
                'description' => $this->l('Add payment methods on') . ' <a href="?tab=AdminUniPaySystem&token=' . Tools::getAdminToken('AdminUniPaySystem' .
                        Tab::getIdFromClassName('AdminUniPaySystem') . $this->context->cookie->id_employee) .
                    '" class="link">' . $this->l('Modules>Pay Systems tab') . '</a>',
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
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
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

    protected function postProcess()
    {
        if (Tools::isSubmit('submitSave')) {
            if (Configuration::updateValue('universalpay_onepage', (int)Tools::getValue('universalpay_onepage'))) {
                return $this->displayConfirmation($this->l('Settings updated'));
            } else {
                return $this->displayError($this->l('Confirmation button') . ': ' . $this->l('Invaild choice'));
            }
        }
    }
}
