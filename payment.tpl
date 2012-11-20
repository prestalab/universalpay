{foreach from=$universalpay item=ps}
	<div class="payment_module">
		<a href="{if !$universalpay_onepage}{$this_path_ssl}payment.php?id_universalpay_system={$ps.id_universalpay_system}{else}#{/if}" title="{$ps.name}" class="universalpay">
			<img src="{$img_ps_dir}pay/{$ps.id_universalpay_system}.jpg" alt="{$ps.name}"/>
			{$ps.description_short}
		</a>
		{if $universalpay_onepage}
		<br/>
		<form action="{$this_path_ssl}validation.php" method="post" class="universalpay_hidden">
		<fieldset>
			{$ps.description}
			<p>
				<b>{l s='Please confirm your order by clicking \'I confirm my order\'' mod='universalpay'}.</b>
			</p>
			<p class="cart_navigation" style="text-align:center;">
				<input type="hidden" name="id_universalpay_system" value="{$ps.id_universalpay_system}" />
				<input type="submit" name="submit" value="{l s='I confirm my order' mod='universalpay'}" class="button_large" style="float:none;" />
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