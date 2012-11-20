<?php
class universalpaypaymentModuleFrontController extends FrontController
{
	public $display_column_left = false;
	public $ssl = true;

	public function displayContent()
	{
		parent::displayContent();

		global $cart;

		require_once(dirname(__FILE__). '/../../UniPaySystem.php');
		$paysistem=new UniPaySystem((int)Tools::getValue('id_universalpay_system'), self::$cookie->id_lang);

		if(!Validate::isLoadedObject($paysistem))
			return ;

		$paysistem->description=str_replace(
			array('%total%'),
			array(Tools::DisplayPrice($cart->getOrderTotal(true, Cart::BOTH))),
			$paysistem->description
		);
		$this->module = Module::getInstanceByName('universalpay');

		self::$smarty->assign(array(
			'nbProducts' => $cart->nbProducts(),
			'paysistem' => $paysistem,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));

		echo $this->module->display($this->module->getPathFile(), 'payment_execution.tpl');
	}
}
