<?php

class universalpayvalidationModuleFrontController extends FrontController
{

	public function displayContent()
	{
		$cart = self::$cart;
		$this->module = Module::getInstanceByName('universalpay');

		$customer = new Customer($cart->id_customer);

		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);

		require_once(dirname(__FILE__). '/../../UniPaySystem.php');
		$paysistem=new UniPaySystem((int)Tools::getValue('id_universalpay_system'), self::$cookie->id_lang);
		if(!Validate::isLoadedObject($paysistem))
			return ;

		$mailVars =	array(
			'{paysistem_name}' => $paysistem->name
		);

		$this->module->validateOrder((int)$cart->id, $paysistem->id_order_state, $total, $paysistem->name, NULL, $mailVars, (int)$cart->id_currency, false, $customer->secure_key);
		if($paysistem->description_success)
		{
			$order=new Order($this->module->currentOrder);
			$description_success=str_replace(
				array('%total%', '%order_number%'),
				array(Tools::DisplayPrice($total), sprintf('#%06d', $order->id)),
				$paysistem->description_success
			);

			self::$smarty->assign(array(
				'is_guest' => self::$cookie->is_guest,
				'HOOK_ORDER_CONFIRMATION' => Hook::orderConfirmation((int)($this->id_order)),
				'HOOK_PAYMENT_RETURN' => $description_success
			));

			if (self::$cookie->is_guest)
			{
				self::$smarty->assign(array(
					'id_order' => $this->module->currentOrder,
					'id_order_formatted' => sprintf('#%06d', $this->module->currentOrder)
				));
				/* If guest we clear the cookie for security reason */
				self::$cookie->mylogout();
			}

			self::$smarty->display(_PS_THEME_DIR_.'order-confirmation.tpl');
		}
		else
			Tools::redirect('order-confirmation.php?&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
	}
}
