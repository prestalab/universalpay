<?php
/**
 * universalpay
 *
 * @author    0RS <admin@prestalab.ru>
 * @link http://prestalab.ru/
 * @copyright Copyright &copy; 2009-2015 PrestaLab.Ru
 * @license   http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version 2.0.0
 */

class UniversalPaypaymentModuleFrontController extends ModuleFrontController
{
	public $display_column_left = false;
    public $display_column_right = false;
	public $ssl = true;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

		$cart = $this->context->cart;

		require_once(dirname(__FILE__).'/../../classes/UniPaySystem.php');
		$paysistem = new UniPaySystem((int)Tools::getValue('id_universalpay_system'), $this->context->cookie->id_lang);

		if (!Validate::isLoadedObject($paysistem))
			return;

		$paysistem->description = str_replace(array('%total%'),
			array(Tools::DisplayPrice($cart->getOrderTotal(true, Cart::BOTH))),
			$paysistem->description);

		$this->context->smarty->assign(array(
			'nbProducts' => $cart->nbProducts(),
			'paysistem' => $paysistem,
			'this_path' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));

		$this->setTemplate('payment_execution.tpl');
	}
}
