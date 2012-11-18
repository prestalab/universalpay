{foreach from=$universalpay item=ps}
	<p class="payment_module">
		<a href="{$link->getModuleLink('universalpay', 'payment', ['id_universalpay_system'=>$ps.id_universalpay_system], true)}" title="{$ps.name}">
			<img src="{$img_ps_dir}pay/{$ps.id_universalpay_system}.jpg" alt="{$ps.name}"/>
			{$ps.description_short}
		</a>
	</p>
{/foreach}