{*
* universalpay
*
* @author 0RS <admin@prestalab.ru>
* @link http://prestalab.ru/
* @copyright Copyright &copy; 2009-2015 PrestaLab.Ru
* @license    http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @version 1.7.2
*}
{extends file="helpers/form/form.tpl"}
{block name="leadin"}
		<fieldset style="width: 300px;float:right;margin-left:15px;margin-top:10px;">
			<legend><img src="../img/admin/manufacturers.gif"/> {l s='Information' mod='universalpay'}</legend>
			<div id="dev_div">
				<span><strong>{l s='Version' mod='universalpay'}: </strong>{$version|escape:'html':'UTF-8'}</span><br>
				<span><strong>{l s='License' mod='universalpay'}:</strong> <a class="link" href="http://www.opensource.org/licenses/osl-3.0.php" target="_blank">OSL 3.0</a></span><br>
				<span><strong>{l s='Developer' mod='universalpay'}:</strong> <a class="link" href="mailto:admin@prestalab.ru" target="_blank">{$author|escape:'html':'UTF-8'}</a><br>
				<span><strong>{l s='Description' mod='universalpay'}:</strong> <a class="link" href="http://prestalab.ru/moduli-oplaty/46-universalnyj-modul-oplaty.html" target="_blank">PrestaLab.ru</a><br>
				<p style="text-align:center"><a href="http://prestalab.ru/"><img src="{$this_path|escape:'html':'UTF-8'}views/img/banner.png" alt="{l s='Modules and Templates for PrestaShop' mod='universalpay'}"/></a></p>
			</div>
		</fieldset>
{/block}