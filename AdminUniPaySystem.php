<?php
//require_once(dirname(__FILE__).'/../../classes/AdminTab.php');
require_once (dirname(__FILE__).'/UniPaySystem.php');

class AdminUniPaySystem extends AdminTab
{
	public function __construct()
	{
	 	$this->table = 'universalpay_system';
	 	$this->className = 'UniPaySystem';
		$this->lang = true;
		$this->edit = true;
		$this->delete = true;

 		$this->fieldImageSettings = array('name' => 'logo', 'dir' => 'pay');

		$this->fieldsDisplay = array(
		'id_universalpay_system' => array('title' => $this->l('ID'), 'align' => 'center', 'width' => 30),
		'logo' => array('title' => $this->l('Logo'), 'align' => 'center', 'image' => 'pay', 'orderby' => false, 'search' => false),
		'name' => array('title' => $this->l('Name'), 'width' => 150),
		'description_short' => array('title' => $this->l('Short description'), 'width' => 450, 'maxlength' => 90, 'orderby' => false),
		'active' => array('title' => $this->l('Displayed'), 'active' => 'status', 'align' => 'center', 'type' => 'bool', 'orderby' => false)
		);

		parent::__construct();
	}

	public function displayForm($isMainTab = true)
	{
		global $currentIndex, $cookie;
		parent::displayForm();
		if (!($obj = $this->loadObject(true)))
			return;

		$divLangName = 'name¤cdescription¤cdescription_short¤cdescription_success';

		echo '
		<form action="'.$currentIndex.'&submitAdd'.$this->table.'=1&token='.$this->token.'" method="post" enctype="multipart/form-data">
			'.($obj->id ? '<input type="hidden" name="id_'.$this->table.'" value="'.$obj->id.'" />' : '').'
			<fieldset><legend><img src="../img/admin/tab-categories.gif" />'.$this->l('Payment Systems').'</legend>';

		// NAME
		echo '<label>'.$this->l('Name:').' </label>
				<div class="margin-form">';
		foreach ($this->_languages as $language)
			echo '	<div id="name_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $this->_defaultFormLanguage ? 'block' : 'none').'; float: left;">
						<input size="40" type="text" id="name_'.$language['id_lang'].'" name="name_'.$language['id_lang'].'" value="'.htmlentities($this->getFieldValue($obj, 'name', (int)($language['id_lang'])), ENT_COMPAT, 'UTF-8').'" /><sup> *</sup>
					</div>';
		$this->displayFlags($this->_languages, $this->_defaultFormLanguage, $divLangName, 'name');
		echo '<div class="clear">'.$this->l('Payment system name.').'</div></div>';

		// Enable
		echo '<label>'.$this->l('Enable:').' </label>
				<div class="margin-form">
					<input type="radio" name="active" id="active_on" value="1" '.($this->getFieldValue($obj, 'active') ? 'checked="checked" ' : '').'/>
					<label class="t" for="active_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Enabled').'" /></label>
					<input type="radio" name="active" id="active_off" onclick="toggleDraftWarning(true);" value="0" '.(!$this->getFieldValue($obj, 'active') ? 'checked="checked" ' : '').'/>
					<label class="t" for="active_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('Disabled').'" /></label>
				</div>';

		// DESCRIPTION SHORT
		echo '	<label>'.$this->l('Short description:').' </label>
				<div class="margin-form">';
		foreach ($this->_languages as $language)
			echo '	<div id="cdescription_short_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $this->_defaultFormLanguage ? 'block' : 'none').';float: left;">
						<textarea class="" cols="100" rows="10" id="description_short_'.$language['id_lang'].'" name="description_short_'.$language['id_lang'].'">'.htmlentities(stripslashes($this->getFieldValue($obj, 'description_short', $language['id_lang'])), ENT_COMPAT, 'UTF-8').'</textarea>
					</div>';
		$this->displayFlags($this->_languages, $this->_defaultFormLanguage, $divLangName, 'cdescription_short');
		echo '<div class="clear">'.$this->l('Displayed in payment selection page.').'</div></div>';

		// DESCRIPTION
		echo '	<label>'.$this->l('Description:').' </label>
				<div class="margin-form">';
		foreach ($this->_languages as $language)
			echo '	<div id="cdescription_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $this->_defaultFormLanguage ? 'block' : 'none').';float: left;">
						<textarea class="rte" cols="80" rows="30" id="description_'.$language['id_lang'].'" name="description_'.$language['id_lang'].'">'.htmlentities(stripslashes($this->getFieldValue($obj, 'description', $language['id_lang'])), ENT_COMPAT, 'UTF-8').'</textarea>
					</div>';
		$this->displayFlags($this->_languages, $this->_defaultFormLanguage, $divLangName, 'cdescription');
		echo '<div class="clear">'.$this->l('%total% will be replaced with total amount.').'</div></div>';

		// DESCRIPTION SUCCESS
		echo '	<label>'.$this->l('Description success:').' </label>
				<div class="margin-form">';
		foreach ($this->_languages as $language)
			echo '	<div id="cdescription_success_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $this->_defaultFormLanguage ? 'block' : 'none').';float: left;">
						<textarea class="rte" cols="80" rows="30" id="description_success_'.$language['id_lang'].'" name="description_success_'.$language['id_lang'].'">'.htmlentities(stripslashes($this->getFieldValue($obj, 'description_success', $language['id_lang'])), ENT_COMPAT, 'UTF-8').'</textarea>
					</div>';
		$this->displayFlags($this->_languages, $this->_defaultFormLanguage, $divLangName, 'cdescription_success');
		echo '<div class="clear">'.$this->l('%order_number% will be replaced with invoice number, %total% will be replaced with total amount.').'</div></div>';

		echo '<label>'.$this->l('Image:').' </label>
				<div class="margin-form">';
		echo 		$this->displayImage($obj->id, _PS_IMG_DIR_.'pay/'.$obj->id.'.jpg', 350, NULL, Tools::getAdminToken('AdminUniPaySystem'.(int)(Tab::getIdFromClassName('AdminUniPaySystem')).(int)($cookie->id_employee)), true);
		echo '	<br /><input type="file" name="logo" />
					<p>'.$this->l('Upload payment logo from your computer').'</p>
				</div>';

		//ORDER STATE
		echo '<label>'.$this->l('Order state:').' </label>
				<div class="margin-form">
					<select name="id_order_state">';
		$states = OrderState::getOrderStates($cookie->id_lang);
		foreach ($states as $state)
			echo '<option value="'.$state['id_order_state'].'"'.($this->getFieldValue($obj, 'id_order_state')==$state['id_order_state'] ? ' selected="selected" ' : '').'>'.$state['name'].'</option>';
		echo '
					</select>
					<div class="clear">'.$this->l('Order state after create.').'</div>
				</div>';

		// CARRIERS
		echo '<label>'.$this->l('Carriers:').' </label>
				<div class="margin-form">';
		$carriers = Carrier::getCarriers($cookie->id_lang, true, false, false, null, $modules_filters = Carrier::ALL_CARRIERS);
		$carriers_checked = $obj->getCarriers();
		foreach($carriers as $carrier)
					echo'<input type="checkbox" name="carrierBox_'.$carrier['id_carrier'].'" id="carrierBox_'.$carrier['id_carrier'].'" value="1" '.(in_array($carrier['id_carrier'], $carriers_checked) ? 'checked="checked" ' : '').'/>
					<label class="t" for="carrierBox_'.$carrier['id_carrier'].'"> '.$carrier['name'].'</label><br/>';
				echo'<div class="clear">'.$this->l('The carriers in which this paysystem is to be used').'</div>
				</div>';

		// SUBMIT
		echo '<div class="margin-form">
					<input type="submit" class="button" name="submitAdd'.$this->table.'" value="'.$this->l('   Save   ').'"/>
				</div>
			</fieldset>
		</form>';
		// TinyMCE
		global $cookie;
		$iso = Language::getIsoById((int)($cookie->id_lang));
		$isoTinyMCE = (file_exists(_PS_ROOT_DIR_.'/js/tiny_mce/langs/'.$iso.'.js') ? $iso : 'en');
		$ad = dirname($_SERVER["PHP_SELF"]);
		echo '
			<script type="text/javascript">
			var iso = \''.$isoTinyMCE.'\' ;
			var pathCSS = \''._THEME_CSS_DIR_.'\' ;
			var ad = \''.$ad.'\' ;
			</script>
			<script type="text/javascript" src="'.__PS_BASE_URI__.'js/tiny_mce/tiny_mce.js"></script>
			<script type="text/javascript" src="'.__PS_BASE_URI__.'js/tinymce.inc.js"></script>';
	}

	public function afterAdd($return)
	{
		global $cookie;
		$carriers=Carrier::getCarriers($cookie->id_lang);
		$carrierBox=array();
		foreach ($carriers as $carrier)
			if (isset($_POST['carrierBox_'.$carrier['id_carrier']]))
				$carrierBox[]=$carrier['id_carrier'];

		$return->updateCarriers($carrierBox);
		return $return;
	}

	public function afterUpdate($return)
	{
		return $this->afterAdd($return);
	}
}
