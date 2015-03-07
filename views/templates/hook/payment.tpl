{*
* universalpay
*
* @author 0RS <admin@prestalab.ru>
* @link http://prestalab.ru/
* @copyright Copyright &copy; 2009-2015 PrestaLab.Ru
* @license    http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @version 1.7.2
*}
{foreach from=$universalpay item=ps}
	<div class="payment_module">
		<a href="{if !$universalpay_onepage}{$link->getModuleLink('universalpay', 'payment', ['id_universalpay_system'=>$ps.id_universalpay_system], true)|escape:'html':'UTF-8'}{else}#{/if}" title="{$ps.name|escape:'html':'UTF-8'}" class="universalpay">
			<img src="{$img_ps_dir|escape:'html':'UTF-8'}pay/{$ps.id_universalpay_system|intval}.jpg" alt="{$ps.name|escape:'html':'UTF-8'}"/>
			{$ps.description_short|escape:'html':'UTF-8'}
		</a>
		{if $universalpay_onepage}
		<br/>
		<form action="{$link->getModuleLink('universalpay', 'validation', [], true)|escape:'html':'UTF-8'}" method="post" class="universalpay_hidden">
		<fieldset>
			{$ps.description}
			<p>
				<b>{l s='Please confirm your order by clicking \'I confirm my order\'' mod='universalpay'}.</b>
			</p>
			<p class="cart_navigation" style="text-align:center;">
				<input type="hidden" name="id_universalpay_system" value="{$ps.id_universalpay_system|intval}" />
				<input type="submit" name="submit" value="{l s='I confirm my order' mod='universalpay'}" class="button_large" />
			</p>
		</fieldset>
		</form>
		{/if}
	</div>
{/foreach}
{if $universalpay_onepage}
<script type="text/javascript">
	{literal}
	$(document).ready(function(){
		$('.universalpay_hidden').hide();
		$('.universalpay').click(function(){
			$('.universalpay_hidden').hide();
			$(this).parent().find('.universalpay_hidden').show();
			return false;
		});
	});
	{/literal}
</script>
{/if}