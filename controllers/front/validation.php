<?php

class universalpayValidationModuleFrontController extends ModuleFrontController
{

	public function postProcess()
	{
		$cart = $this->context->cart;

		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');

		// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
			if ($module['name'] == 'universalpay')
			{
				$authorized = true;
				break;
			}

		if (!$authorized)
			die($this->module->l('This payment method is not available.', 'validation'));

		$customer = new Customer($cart->id_customer);

		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');

		$currency = $this->context->currency;
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);

		require_once(dirname(__FILE__). '/../../UniPaySystem.php');
		$paysistem=new UniPaySystem((int)Tools::getValue('id_universalpay_system'), $this->context->cookie->id_lang);
		if(!Validate::isLoadedObject($paysistem))
			return ;

		$mailVars = array(
			'{paysistem_name}' => $paysistem->name
		);
		$this->module->validateOrder((int)$cart->id, $paysistem->id_order_state, $total, $paysistem->name, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
		if($paysistem->description_success)
		{
			$order=new Order($this->module->currentOrder);
			$description_success=str_replace(
				array('%total%', '%order_number%'),
				array(Tools::DisplayPrice($total), '#'.$order->reference),
				$paysistem->description_success
			);

			if ($this->context->customer->is_guest)
			{
				$this->context->smarty->assign(array(
					'id_order' => $order->id,
					'reference_order' => $order->reference,
					'id_order_formatted' => '#'.$order->reference,
					'email' => $this->context->customer->email
				));
				/* If guest we clear the cookie for security reason */
				$this->context->customer->mylogout();
			}

			$currency = new Currency($order->id_currency);
			$params['total_to_pay'] = $order->getOrdersTotalPaid();
			$params['currency'] = $currency->sign;
			$params['objOrder'] = $order;
			$params['currencyObj'] = $currency;

			$this->context->smarty->assign(array(
				'is_guest' => $this->context->customer->is_guest,
				'HOOK_ORDER_CONFIRMATION' => Hook::exec('displayOrderConfirmation', $params),
				'HOOK_PAYMENT_RETURN' => $description_success
			));

			$this->setTemplate(_PS_THEME_DIR_.'order-confirmation.tpl');
		}
		else
			Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
	}

	public function setTemplate($default_template)
	{
		if ($this->context->getMobileDevice() != false)
			$this->setMobileTemplate($default_template);
		else
		{
			$template = $this->getOverrideTemplate();
			if ($template)
				$this->template=$template;
			else
				$this->template=$default_template;
		}
	}
}
