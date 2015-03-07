{*
* universalpay
*
* @author 0RS <admin@prestalab.ru>
* @link http://prestalab.ru/
* @copyright Copyright &copy; 2009-2015 PrestaLab.Ru
* @license    http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @version 1.7.2
*}

{capture name=path}{$paysistem->name}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='universalpay'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($nbProducts) && $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='universalpay'}</p>
{else}

<h3>{$paysistem->name|escape:'html':'UTF-8'}</h3>
<form action="{$link->getModuleLink('universalpay', 'validation', [], true)|escape:'html':'UTF-8'}" method="post">
	{$paysistem->description}
	<p>
		<b>{l s='Please confirm your order by clicking \'I confirm my order\'' mod='universalpay'}.</b>
	</p>
	<p class="cart_navigation">
		<input type="hidden" name="id_universalpay_system" value="{$paysistem->id|intval}" />
		<input type="submit" name="submit" value="{l s='I confirm my order' mod='universalpay'}" class="exclusive_large" />
		<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" class="button_large">{l s='Other payment methods' mod='universalpay'}</a>
	</p>
</form>
{/if}
