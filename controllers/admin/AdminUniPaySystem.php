<?php
/**
 * universalpay
 *
 * @author    0RS <admin@prestalab.ru>
 * @link http://prestalab.ru/
 * @copyright Copyright &copy; 2009-2016 PrestaLab.Ru
 * @license   http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version 2.2.1
 */

require_once(dirname(__FILE__) . '/../../classes/UniPaySystem.php');

class AdminUniPaySystemController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'universalpay_system';
        $this->className = 'UniPaySystem';
        $this->lang = true;

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->fieldImageSettings = array('name' => 'logo', 'dir' => 'pay');

        $this->fields_list = array(
            'id_universalpay_system' => array('title' => $this->l('ID'), 'align' => 'center', 'width' => 30),
            'logo' => array(
                'title' => $this->l('Logo'),
                'align' => 'center',
                'image' => 'pay',
                'orderby' => false,
                'search' => false
            ),
            'name' => array('title' => $this->l('Name'), 'width' => 150),
            'description_short' => array(
                'title' => $this->l('Short description'),
                'width' => 450,
                'maxlength' => 90,
                'orderby' => false
            ),
            'active' => array(
                'title' => $this->l('Displayed'),
                'active' => 'status',
                'align' => 'center',
                'type' => 'bool',
                'orderby' => false
            )
        );

        parent::__construct();
    }


    public function renderForm()
    {
        $this->display = 'edit';
        $this->initToolbar();

        if (!$this->loadObject(true)) {
            return;
        }

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Payment Systems'),
                'icon' => 'icon-money'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Name:'),
                    'name' => 'name',
                    'required' => true,
                    'lang' => true,
                    'class' => 'copy2friendlyUrl',
                    'hint' => $this->l('Invalid characters:') . ' <>;=#{}'
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Displayed:'),
                    'name' => 'active',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Cart type:'),
                    'name' => 'cart_type',
                    'required' => false,
                    'options' => array(
                        'query' => array(
                            array('id' => UniPaySystem::CART_BOTH, 'name' => $this->l('Real and virtual')),
                            array('id' => UniPaySystem::CART_REAL, 'name' => $this->l('Real')),
                            array('id' => UniPaySystem::CART_VIRTUAL, 'name' => $this->l('Virtual')),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Short description:'),
                    'name' => 'description_short',
                    'lang' => true,
                    'rows' => 5,
                    'cols' => 40,
                    'hint' => $this->l('Invalid characters:') . ' <>;=#{}',
                    'desc' => $this->l('Displayed in payment selection page.')
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Description:'),
                    'name' => 'description',
                    'autoload_rte' => true,
                    'lang' => true,
                    'rows' => 5,
                    'cols' => 40,
                    'hint' => $this->l('Invalid characters:') . ' <>;=#{}',
                    'desc' => $this->l('%total% will be replaced with total amount.').' '.$this->l('You can use additional input field with name prefixed up_')
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Description success:'),
                    'name' => 'description_success',
                    'autoload_rte' => true,
                    'lang' => true,
                    'rows' => 5,
                    'cols' => 40,
                    'hint' => $this->l('Invalid characters:') . ' <>;=#{}',
                    'desc' => $this->l('%order_number% will be replaced with order reference, %order_id% - order id, %total% - total amount, %up_field_name - value of input field')
                ),
                array(
                    'type' => 'file',
                    'label' => $this->l('Image:'),
                    'name' => 'logo',
                    'display_image' => true,
                    'desc' => $this->l('Upload payment logo from your computer')
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Order state:'),
                    'name' => 'id_order_state',
                    'desc' => $this->l('Order state after create.'),
                    'options' => array(
                        'query' => OrderState::getOrderStates($this->context->language->id),
                        'name' => 'name',
                        'id' => 'id_order_state'
                    )
                ),
                array(
                    'type' => 'checkbox',
                    'label' => $this->l('Carriers:'),
                    'name' => 'carrierBox',
                    'values' => array(
                        'query' => Carrier::getCarriers($this->context->language->id,
                            true,
                            false,
                            false,
                            null,
                            Carrier::ALL_CARRIERS),
                        'id' => 'id_carrier',
                        'name' => 'name'
                    ),
                    'desc' => $this->l('The carriers in which this paysystem is to be used')
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        );

        $cart_rules = array(0 => array('id_cart_rule' => 0, 'name' => $this->l('No discount')));

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
			SELECT cr.id_cart_rule, crl.name, cr.reduction_percent, cr.reduction_amount
			FROM `' . _DB_PREFIX_ . 'cart_rule` cr, `' . _DB_PREFIX_ . 'cart_rule_lang` crl
			WHERE
			cr.id_cart_rule = crl.id_cart_rule
			AND `id_lang` = ' . (int)$this->context->language->id .
            ' AND `active` = 1');

        foreach ($result as $discount) {
            $cart_rules[$discount['id_cart_rule']] = array(
                'id_cart_rule' => $discount['id_cart_rule'],
                'name' => $discount['name'] . ' - ' . ($discount['reduction_percent'] > 0 ? $discount['reduction_percent'] . '%' : $discount['reduction_amount'])
            );
        }

        if (CartRule::isFeatureActive()) {
            $this->fields_form['input'][] = array(
                'type' => 'select',
                'label' => $this->l('Order discount:'),
                'name' => 'id_cart_rule',
                'desc' => $this->l('Select cart rule'),
                'options' => array(
                    'query' => $cart_rules,
                    'name' => 'name',
                    'id' => 'id_cart_rule'
                )
            );
        }

        if (Group::isFeatureActive()) {
            $this->fields_form['input'][] = array(
                'type' => 'group',
                'label' => $this->l('Groups:'),
                'name' => 'groupBox',
                'values' => Group::getGroups($this->context->language->id),
                'desc' => $this->l('The customer groups in which this paysystem is to be used')
            );
        }

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = array(
                'type' => 'shop',
                'label' => $this->l('Shop association'),
                'name' => 'checkBoxShopAsso',
            );
        }

        if (!($obj = $this->loadObject(true))) {
            return;
        }

        // Added values of object Group
        $universalpay_system_carrier_ids = $obj->getCarriers();

        $carriers = Carrier::getCarriers($this->context->language->id, false, false, false, null,
            Carrier::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);

        foreach ($carriers as $carrier) {
            $this->fields_value['carrierBox_' . $carrier['id_carrier']] = Tools::getValue('carrierBox_' . $carrier['id_carrier'],
                (in_array($carrier['id_carrier'], $universalpay_system_carrier_ids)));
        }

        $universalpay_system_group_ids = $obj->getGroups();

        if (Group::isFeatureActive()) {
            $groups = Group::getGroups($this->context->language->id);

            foreach ($groups as $group) {
                $this->fields_value['groupBox_' . $group['id_group']] = Tools::getValue('groupBox_' . $group['id_group'],
                    (in_array($group['id_group'], $universalpay_system_group_ids)));
            }
        }

        return parent::renderForm();
    }

    public function postProcess()
    {
        $return = parent::postProcess();

        if (Tools::getValue('submitAdd' . $this->table) && Validate::isLoadedObject($return)) {
            $carriers = Carrier::getCarriers($this->context->language->iso_code, false, false, false, null,
                Carrier::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
            $carrier_box = array();
            foreach ($carriers as $carrier) {
                if (Tools::getIsset('carrierBox_' . $carrier['id_carrier'])) {
                    $carrier_box[] = $carrier['id_carrier'];
                }
            }
            $return->updateCarriers($carrier_box);
            if (Group::isFeatureActive()) {
                $return->updateGroups(Tools::getValue('groupBox'));
            }
            if (Shop::isFeatureActive()) {
                $this->updateAssoShop($return->id);
            }
        }
        return $return;
    }
}
