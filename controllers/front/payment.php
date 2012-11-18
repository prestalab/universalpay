<?php
class universalpaypaymentModuleFrontController extends ModuleFrontController
{
	public $display_column_left = false;
	public $ssl = true;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

		$cart = $this->context->cart;

		require_once(dirname(__FILE__). '/../../UniPaySystem.php');
		$paysistem=new UniPaySystem((int)Tools::getValue('id_universalpay_system'), $this->context->cookie->id_lang);

		if(!Validate::isLoadedObject($paysistem))
			return ;

		$paysistem->description=str_replace(
			array('%total%'),
			array(Tools::DisplayPrice($cart->getOrderTotal(true, Cart::BOTH))),
			$paysistem->description
		);

		$this->context->smarty->assign(array(
			'nbProducts' => $cart->nbProducts(),
			'paysistem' => $paysistem,
			'this_path' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));

		$this->setTemplate('payment_execution.tpl');
	}
}
