<style>
a.universalpay:after {
	color: #777777;
	content: "\f054";
	display: block;
	font-family: "FontAwesome";
	font-size: 25px;
	height: 22px;
	margin-top: -11px;
	position: absolute;
	right: 15px;
	top: 50%;
	width: 14px;
}
</style>
{foreach from=$universalpay item=ps}
<div class="row">
	<div class="col-xs-12 col-md-6">
		<p class="payment_module">
			<a style="background:url('{$img_ps_dir}pay/{$ps.id_universalpay_system}.jpg') no-repeat scroll 15px 0px #FBFBFB"
				id="universalpay"
				class = "universalpay"
				href="{if !$universalpay_onepage}{$link->getModuleLink('universalpay', 'payment', ['id_universalpay_system'=>$ps.id_universalpay_system], true)}{else}#{/if}"
				title="{$ps.name}">
				{$ps.description_short}
			</a>
			{if $universalpay_onepage}
			<br/>
			<form action="{$link->getModuleLink('universalpay', 'validation', [], true)|escape:'html':'UTF-8'}" method="post" id="universalpay_hidden" style="dispaly:none;" >
				<div class="box cheque-box">
					{$ps.description}
				</div>
				<p>
					<b>{l s='Please confirm your order by clicking "I confirm my order"' mod='universalpay'}.</b>
				</p>
				<p class="cart_navigation clearfix" id="cart_navigation">
					<input type="hidden" name="id_universalpay_system" value="{$ps.id_universalpay_system}" />
					<button class="button btn btn-default button-medium" type="submit">
						<span>{l s='I confirm my order' mod='universalpay'}<i class="icon-chevron-right right"></i></span>
					</button>
				</p>
			</form>
			{/if}
		</p>
	</div>
</div>
{/foreach}
{if $universalpay_onepage}
<script type="text/javascript">
	{literal}
	$(document).ready(function(){
		$('#universalpay_hidden').hide();
		$('#universalpay').click(function(){
			if ($('#universalpay_hidden').is(":hidden"))
				$('#universalpay_hidden').show();
			else
				$('#universalpay_hidden').hide();

			return false;
		});
	});
	{/literal}
</script>
{/if}