<?php
/*
* 2017 PayZQ
*
*	@author PayZQ
*	@copyright	2017 PayZQ
*	@license		http://payzq.net/
*/

if (!file_exists(dirname(__FILE__).'/../../config/config.inc.php')
    || !file_exists(dirname(__FILE__).'/../../init.php')
) {
    die('ko');
}

require dirname(__FILE__).'/../../config/config.inc.php';
require dirname(__FILE__).'/../../init.php';
if (!defined('_PS_VERSION_')) {
    exit;
}

$payzq = Module::getInstanceByName('payzq_ps');

if (Tools::getValue('token_payzq')) {
    /* Refresh Button Back Office on Transaction */
    if ($payzq && $payzq->active) {
        echo $payzq->displayTransaction(1, Tools::getValue('token_payzq'), Tools::getValue('id_employee'));
    }
}
