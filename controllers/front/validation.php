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
class UniversalpayValidationModuleFrontController extends ModuleFrontController
{

    public function postProcess()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'universalpay') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        require_once(dirname(__FILE__) . '/../../classes/UniPaySystem.php');
        $paysistem = new UniPaySystem((int)Tools::getValue('id_universalpay_system'), $this->context->cookie->id_lang);
        if (!Validate::isLoadedObject($paysistem)) {
            return;
        }

        if ($paysistem->id_cart_rule) {
            $cart->addCartRule($paysistem->id_cart_rule);
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        $up_fields = array();
        foreach ($_POST as $key => $val) {
            $key_parts = explode('_', $key);
            if ($key_parts[0] == 'up') {
                $up_fields[$key_parts[1]] = $val;
            }
        }

        $mail_vars = array(
            '{paysistem_name}' => $paysistem->name
        );

        foreach ($up_fields as $key => $val) {
            $mail_vars['{up_' . $key . '}'] = $val;
        }

        if ($paysistem->id_cart_rule) {
            $cart->addCartRule($paysistem->id_cart_rule);
        }

        $this->module->validateOrder((int)$cart->id, $paysistem->id_order_state, $total, $paysistem->name,
            null, $mail_vars, (int)$currency->id, false, $customer->secure_key);

        require_once(dirname(__FILE__) . '/../../classes/UpOrder.php');
        $order = new UpOrder($this->module->currentOrder);

        if (count($up_fields)) {
            $order->setUpFields($up_fields);
            $order->save();
        }
        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int)$cart->id .
            '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder .
            '&key=' . $customer->secure_key . '&id_universalpay_system=' . $paysistem->id);
    }
}
