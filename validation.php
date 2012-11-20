<?php

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/controllers/front/validation.php');
$controller = new universalpayvalidationModuleFrontController();
$controller->run();