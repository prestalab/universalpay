{capture name=path}{$paysistem->name}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='universalpay'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($nbProducts) && $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.'}</p>
{else}

<h3>{$paysistem->name}</h3>
<form action="{$this_path_ssl}validation.php" method="post">
	{$paysistem->description}
	<p>
		<b>{l s='Please confirm your order by clicking \'I confirm my order\'' mod='universalpay'}.</b>
	</p>
	<p class="cart_navigation">
		<input type="hidden" name="id_universalpay_system" value="{$paysistem->id}" />
		<input type="submit" name="submit" value="{l s='I confirm my order' mod='universalpay'}" class="exclusive_large" />
		<a href="{$link->getPageLink('order.php', true)}?step=3" class="button_large">{l s='Other payment methods' mod='universalpay'}</a>
	</p>
</form>
{/if}
