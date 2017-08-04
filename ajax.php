<?php
/*
* 2017 PayZQ
*
*	@author PayZQ
*	@copyright	2017 PayZQ
*	@license		http://payzq.net/
*/


if (!file_exists(dirname(__FILE__).'/../../config/config.inc.php')) {
    die('ko');
}

include dirname(__FILE__).'/../../config/config.inc.php';
include dirname(__FILE__).'/../../init.php';
include dirname(__FILE__).'/payzq_ps.php';

$payZQ = Module::getInstanceByName('payzq_ps');

if ($payZQ && $payZQ->active) {
    $context = Context::getContext();

    /* Loading current billing address from PrestaShop */
    if (!isset($context->cart->id)
        || empty($context->cart->id)
        || !isset($context->cart->id_address_invoice)
        || empty($context->cart->id_address_invoice)
    ) {
        die('No active shopping cart');
    }

    $amount = $context->cart->getOrderTotal();

    $params = array(
        'amount' => $amount,
        'number' => Tools::getValue('number'),
        'cvv' => Tools::getValue('cvv'),
        'currency' => $context->currency->iso_code,
        'cardHolderName' => Tools::getValue('cardholder'),
        'type' => Tools::getValue('type'),
        'expiry' => Tools::getValue('expiry'),
    );

    $payZQ->authorize_and_capture($params);
}
