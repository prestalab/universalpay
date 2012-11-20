<?php

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/controllers/front/payment.php');
$controller = new universalpaypaymentModuleFrontController();
$controller->run();