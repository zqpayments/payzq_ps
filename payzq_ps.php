

<?php
/**
 * 2007-2017 PrestaShop
 *
 * DISCLAIMER
 ** Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author PayZQ <contact@payzq.com>
 * @copyright 2017 PayZQ SA
 * @license PayZQ SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__).'/Curl.php';
require_once dirname(__FILE__).'/JWT.php';

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Payzq_ps extends PaymentModule
{
    /* status */
    const _FLAG_NULL_ = 0;

    const _FLAG_ERROR_ = 1;
    const _FLAG_WARNING_ = 2;
    const _FLAG_SUCCESS_ = 4;

    const _FLAG_STDERR_ = 1;
    const _FLAG_STDOUT_ = 2;
    const _FLAG_STDIN_ = 4;
    const _FLAG_MAIL_ = 8;
    const _FLAG_NO_FLUSH__ = 16;
    const _FLAG_FLUSH__ = 32;

    /* PayZQ Pre fix */
    const _PS_PAYZQ_ = '_PS_PAYZQ_';

    /* 0: no VERBOSE, 1: VERBOSE, 2: VERBOSE + ANSI COLOR */
    public static $verbose = 2;
    public static $log_file = '';
    public $mail = '';

    /* init conf var */
    private static $psconf = array();
    /* init hook var */
    private static $pshook = array();

    /* tab section shape */
    private $section_shape = 1;

    public $addons_track;

    public $errors = array();
    public $warnings = array();
    public $infos = array();
    public $success = array();

    /* refund */
    public $refund = 0;

    // PayZQ
    private $api_base_url = 'http://test-zms.zertifica.org:7743/api/v1/transactions/';
    private $key_jwt = 'secret';
    private $iv = '4242424242424242';

    public function __construct()
    {
        $this->name = 'payzq_ps';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'PayZQ SA';
        $this->bootstrap = true;
        $this->display = 'view';
        $this->module_key = 'bb21cb93bbac29159ef3af00bca52354';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->currencies = true;
        /* curl check */
        if (is_callable('curl_init') === false) {
            $this->warning = $this->l('This module require cURL, please activate (PHP extension).');
        }

        parent::__construct();

        $this->meta_title = $this->l('PayZQ');
        $this->displayName = $this->l('PayZQ payment module');
        $this->description = $this->l('Start accepting PayZQ payments today, directly from your shop!');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?', $this->name);
        if (!Configuration::get('PS_SSL_ENABLED')) {
            $this->warning = $this->l('You must enable SSL on the store if you want to use this module');
        }

        /* Use a specific name to bypass an Order confirmation controller check */
        if (in_array(Tools::getValue('controller'), array('orderconfirmation', 'order-confirmation'))) {
            $this->displayName = $this->l('Payment by PayZQ');
        }
    }

    public function install()
    {
        $partial_refund_state = Configuration::get(self::_PS_PAYZQ_.'partial_refund_state');

        /* Create Order State for PayZQ */
        if ($partial_refund_state === false) {
            $order_state = new OrderState();
            $langs = Language::getLanguages();
            foreach ($langs as $lang) {
                $order_state->name[$lang['id_lang']] = pSQL('PayZQ Partial Refund payzq1');
            }
            $order_state->invoice = false;
            $order_state->send_email = false;
            $order_state->logable = true;
            $order_state->color = '#FFDD99';
            $order_state->save();

            Configuration::updateValue(self::_PS_PAYZQ_.'partial_refund_state', $order_state->id);
        }

        if (!parent::install()) return false;

        if (!$this->registerHook('header')
            || !$this->registerHook('orderConfirmation') /* same of paymentReturn */
            || !$this->registerHook('paymentOptions')
            || !$this->registerHook('adminOrder')) {
            return false;
        }

        if (!Configuration::updateValue(self::_PS_PAYZQ_.'mode', 1)
            || !Configuration::updateValue(self::_PS_PAYZQ_.'refund_mode', 1)
            || !Configuration::updateValue(self::_PS_PAYZQ_.'secure', 1)) {
            return false;
        }

        $this->createPayZQTable();

        return true;
    }

    public function uninstall()
    {
        /* Delete Database + Table */
        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'payzq`');
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'payzq`');

        return parent::uninstall()
            && Configuration::updateValue(self::_PS_PAYZQ_.'key', '')
            && Configuration::updateValue(self::_PS_PAYZQ_.'test_key', '')
            && Configuration::updateValue(self::_PS_PAYZQ_.'merchant_key', '');
    }

    /* Create Database PayZQ Payment */
    /*
     * name: cardholder's name
     * id_cart: cart order's id
     * last4: last four digits of the card
     * type: card's type [Visa, MasterCard, Amex, Discover, Jcb, Diners]
     * amount: transaction's amount
     * refund: refund amount
     * currency: currency ISO code
     * result: transaction result []
     * type: request mode. [0:LIVE, 1:TEST]
     * date_add: transaction date
    */
    protected function createPayZQTable()
    {
        $db = Db::getInstance();
        $query = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'payzq` (
            `id_payment` int(11) NOT NULL AUTO_INCREMENT,
            `id_payzq` varchar(255) NOT NULL,
            `id_transaction` varchar(255) NOT NULL,
            `id_target_transaction` varchar(255) NOT NULL,
            `name` varchar(255) NOT NULL,
            `id_cart` int(11) NOT NULL,
            `last4` varchar(4) NOT NULL,
            `type` varchar(255) NOT NULL,
            `amount` varchar(255) NOT NULL,
            `refund` varchar(255) NOT NULL,
            `currency` varchar(255) NOT NULL,
            `result` tinyint(4) NOT NULL,
            `state` tinyint(4) NOT NULL,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_payment`),
           KEY `id_cart` (`id_cart`)
       ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1';
        $db->Execute($query);

        return true;
    }

    private function hasErrors()
    {
        return !!$this->errors;
    }

    private function hasWarnings()
    {
        return !!$this->warnings;
    }

    private function hasInfos()
    {
        return !!$this->infos;
    }

    private function hasSuccess()
    {
        return !!$this->success;
    }

    public static function arrayAsHtmlList(array $ar = array())
    {
        if (!empty($ar)) {
            return '<ul><li>'.implode('</li><li>', $ar).'</li></ul>';
        }
        return '';
    }


    /* HG - Show message on the head of configuration module view */
    public function showHeadMessages(&$terror = '')
    {
        $msgs_list = array_map('array_filter', array(
            'displayInfos' => $this->infos,
            'displayWarning' => $this->warnings,
            'displayError' => $this->errors,
            'displayConfirmation' => $this->success,
        ));

        foreach ($msgs_list as $display => $msgs) {
            if (!empty($msgs)) {
                $terror = call_user_func(array($this, $display), '<p>PayZQ</p>'.self::arrayAsHtmlList($msgs)).$terror;
            }
        }

        return (!empty($terror) ? $terror : ($terror = $this->displayError('Unknow error(s)')));
    }

    /*
     ** @method: c
     ** @description: self configuration key check
     **
     ** @arg: $key
     ** @return: key if configuration has key else throw new exception
     */
    private function c($key = false)
    {
        if ($key && array_key_exists($key, self::$psconf)) {
            return self::_CONF_PREFIX_.$key;
        }
        throw new PrestaShopException(sprintf($this->l('undefined key : [%s]'), ($key ? $key : 'none')));
    }

    /*
     ** @Method: updateConfiguration
     ** @description: submitOptionsconfiguration update values
     **
     ** @arg:
     ** @return: (none)
     */
    private function updateOptionsConfiguration()
    {
        if (Tools::isSubmit('submitOptionsconfiguration')) {
            $prefix_len = Tools::strlen(self::_CONF_PREFIX_);
            foreach ($_POST as $key => $value) {
                /* 	$key = sprintf('%.50s', $key); */
                if (Tools::isSubmit($key) && !strncmp($key, self::_CONF_PREFIX_, $prefix_len)) {
                    if (Configuration::hasKey($key)) {
                        Configuration::updateValue($key, Tools::getValue($key));
                    } else {
                        $this->errors[] = sprintf($this->l('the key: [%s] doesn\'t exist..'), $key);
                    }
                }
            }
        }

        return !$this->hasErrors();
    }

    /* HG - return class to indicate the conection configuration */
    public function getBadgesClass(array $keys = array())
    {
        $class = self::_FLAG_NULL_;

        if (!empty($keys)) {
            foreach ($keys as $key) {
                if (isset($this->errors[$key])) {
                    $class |= self::_FLAG_ERROR_;
                } elseif (isset($this->warnings[$key])) {
                    $class |= self::_FLAG_WARNING_;
                } else {
                    $class |= self::_FLAG_SUCCESS_;
                }
            }

            if ($class & self::_FLAG_ERROR_) {
                return 'tab-error';
            } elseif ($class & self::_FLAG_WARNING_) {
                return 'tab-warning';
            } elseif ($class & self::_FLAG_SUCCESS_) {
                return 'tab-success';
            }
        }

        return false;
    }

    /* HG - load libraries CSS and JS files to check compatibility */
    public function loadAssetCompatibility()
    {
        $css_compatibility = $js_compatibility = array();

        $css_compatibility = array(
            $this->_path.'/views/css/compatibility/font-awesome.min.css',
            $this->_path.'/views/css/compatibility/bootstrap-select.min.css',
            $this->_path.'/views/css/compatibility/bootstrap-responsive.min.css',
            $this->_path.'/views/css/compatibility/bootstrap.min.css',
            $this->_path.'/views/css/tabs15.css',
            $this->_path.'/views/css/compatibility/bootstrap.extend.css',
        );
        $this->context->controller->addCSS($css_compatibility, 'all');

        // Load JS
        $js_compatibility = array(
            $this->_path.'/views/js/compatibility/bootstrap-select.min.js',
            $this->_path.'/views/js/compatibility/bootstrap.min.js'
        );

        $this->context->controller->addJS($js_compatibility);
    }

    /* HG - load custom module CSS and JS files o*/
    private function loadRessources()
    {
        $content = array(
            $this->displaySomething(),
            $this->displayForm(),
            $this->displayTransaction(),
            // $this->displaySecure(),
            $this->displayRefundForm(),
            $this->displayFAQ(),
            $this->displayContact()
        );

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
            $domain = Tools::getShopDomainSsl(true, true);
        } else {
            $domain = Tools::getShopDomain(true, true);
        }

        $tab_contents = array(
            'title' => $this->l('PayZQ Payment'),
            'contents' => array(
                array(
                    'name' => $this->l('About PayZQ'),
                    'icon' => 'icon-book',
                    'value' => $content[0],
                    'badge' => $this->getBadgesClass(),
                ),
                array(
                    'name' => $this->l('Configuration'),
                    'icon' => 'icon-power-off',
                    'value' => $content[1],
                    'badge' => $this->getBadgesClass(array(
                        'log_error_secret',
                        'log_error_publishable',
                        'log_success_secret',
                        'log_success_publishable',
                        'connection',
                        'empty'
                    )),
                ),
                array(
                    'name' => $this->l('Transactions'),
                    'icon' => 'icon-credit-card',
                    'value' => $content[2],
                    'badge' => $this->getBadgesClass(),
                ),
                // array(
                //     'name' => $this->l('3D secure'),
                //     'icon' => 'icon-credit-card',
                //     'value' => $content[3],
                //     'badge' => $this->getBadgesClass(),
                // ),
                array(
                    'name' => $this->l('Refund'),
                    'icon' => 'icon-ticket',
                    'value' => $content[3],
                    'badge' => $this->getBadgesClass(),
                ),
                array(
                    'name' => $this->l('FAQ'),
                    'icon' => 'icon-question',
                    'value' => $content[4],
                    'badge' => $this->getBadgesClass(),
                ),
                array(
                    'name' => $this->l('Contact'),
                    'icon' => 'icon-envelope',
                    'value' => $content[5],
                    'badge' => $this->getBadgesClass(),
                ),
            ),
            'logo' => $domain.__PS_BASE_URI__.basename(_PS_MODULE_DIR_).'/'.$this->name.'/views/img/PayZQ_logo.png'
        );


        $this->context->smarty->assign('tab_contents', $tab_contents);
        $this->context->smarty->assign('ps_version', _PS_VERSION_);
        $this->context->smarty->assign('new_base_dir', $this->_path);
        $this->context->controller->addJs($this->_path.'/views/js/faq.js');
        $this->context->controller->addCss($this->_path.'/views/css/started.css');
        $this->context->controller->addCss($this->_path.'/views/css/tabs.css');
        $this->context->controller->addJs($this->_path.'/views/js/back.js');

        return $this->display($this->_path, 'views/templates/admin/main.tpl');
    }

    public function loadAddonTracker()
    {
        $track_query = 'utm_source=back-office&utm_medium=module&utm_campaign=back-office-%s&utm_content=%s';
        $lang = new Language(Configuration::get('PS_LANG_DEFAULT'));

        if ($lang && Validate::isLoadedObject($lang)) {
            $track_query = sprintf($track_query, Tools::strtoupper($lang->iso_code), $this->name);
            $this->context->smarty->assign('url_track', $track_query);
            return true;
        }

        return false;
    }

    /* HG - verify if the secret key is set */
    public function contentLogIn()
    {
        if (Tools::isSubmit('submit_login')) {

            // check the merchant_key
            $merchant_key = Tools::getValue(self::_PS_PAYZQ_.'merchant_key');
            if (!empty($merchant_key)) {
                  Configuration::updateValue(self::_PS_PAYZQ_.'merchant_key', Tools::getValue(self::_PS_PAYZQ_.'merchant_key'));
            } else {
                $this->errors['empty'] = $this->l('Merchant ID and Token Key fields are mandatory');
            }
            Configuration::updateValue(self::_PS_PAYZQ_.'mode', Tools::getValue(self::_PS_PAYZQ_.'mode'));

            if (Tools::getValue(self::_PS_PAYZQ_.'mode') == 1) {
                $secret_key = Tools::getValue(self::_PS_PAYZQ_.'test_key');
                if (!empty($secret_key)) {
                    Configuration::updateValue(self::_PS_PAYZQ_.'test_key', Tools::getValue(self::_PS_PAYZQ_.'test_key'));
                } else {
                    $this->errors['empty'] = $this->l('Merchant ID and Token Key fields are mandatory');
                }
                Configuration::updateValue(self::_PS_PAYZQ_.'mode', Tools::getValue(self::_PS_PAYZQ_.'mode'));
            } else {
                $secret_key = Tools::getValue(self::_PS_PAYZQ_.'key');
                if (!empty($secret_key)) {
                    Configuration::updateValue(self::_PS_PAYZQ_.'key', Tools::getValue(self::_PS_PAYZQ_.'key'));
                } else {
                    $this->errors['empty'] = $this->l('Merchant ID and Token Key fields are mandatory');
                }
                Configuration::updateValue(self::_PS_PAYZQ_.'mode', Tools::getValue(self::_PS_PAYZQ_.'mode'));
            }
        }
    }

    /* HG - get form secure value */
    public function contentSecure()
    {
        if (Tools::isSubmit('submit_secure')) {
            Configuration::updateValue(self::_PS_PAYZQ_.'secure', Tools::getValue(self::_PS_PAYZQ_.'secure'));
        }
    }

    /* HG - get form refund a payment */
    public function contentRefund()
    {
        if (Tools::isSubmit('submit_refund_id')) {

            $refund_id = Tools::getValue(self::_PS_PAYZQ_.'refund_id');
            if (!empty($refund_id)) {
                $refund = Db::getInstance()->ExecuteS('SELECT *	FROM '._DB_PREFIX_.'payzq WHERE `id_transaction` = "'.pSQL($refund_id).'"');
            } else {
                $this->errors['refund'] = $this->l('Please make sure to put a PayZQ Transaction Id');
                return false;
            }

            if ($refund) {
                $this->refund = 1;
                Configuration::updateValue(self::_PS_PAYZQ_.'refund_id', Tools::getValue(self::_PS_PAYZQ_.'refund_id'));
            } else {
                $this->refund = 0;
                $this->errors['refund'] = $this->l('This PayZQ Transaction ID doesn\'t exist, please check it again');
                Configuration::updateValue(self::_PS_PAYZQ_.'refund_id', '');
                return false;
            }

            $amount = null;
            $mode = Tools::getValue(self::_PS_PAYZQ_.'refund_mode');
            if ($mode == 0) {
                $amount = Tools::getValue(self::_PS_PAYZQ_.'refund_amount');
            }

            $this->apiRefund($refund[0]['id_transaction'], $refund[0]['currency'], $mode, $refund[0]['id_cart'], $amount);
        }
    }

    /* HG - initializes the module configuration and load the views */
    public function getContent()
    {

        /* Check if SSL is enabled */
        if (!Configuration::get('PS_SSL_ENABLED')) {
            $this->errors[] = $this->l('A SSL certificate is required to process credit card payments using PayZQ. Please consult the FAQ.');
        }

        /* Do Log In  */
        $this->contentLogIn();

        if (!Configuration::get(self::_PS_PAYZQ_.'key')
            && !Configuration::get(self::_PS_PAYZQ_.'merchant_key')
            && !Configuration::get(self::_PS_PAYZQ_.'test_key')
          ) {
            $this->warnings['connection'] = false;
        }

        /* Do Secure */
        $this->contentSecure();

        /* Do Refund */
        $this->contentRefund();

        /* generate url track */
        $this->loadAddonTracker();

        /* Your content */
        $html = $this->loadRessources();

        if (!empty($this->errors) || !empty($this->warnings)) {
            $this->showHeadMessages($html);
        }

        return $html;
    }

    /* HG - generate refund data */
    private function generateRefundData($payZQ_order, $amount = null)
    {
      $host= gethostname();
      $ip = gethostbyname($host);

      $amount = (!$amount) ? ($payZQ_order[0]['amount'] - $payZQ_order[0]['refund']) : $amount;

      return array(
        "type" => "refund",
        "transaction_id" => $this->getNextCodeTransaction(),
        "target_transaction_id" => $payZQ_order[0]['id_transaction'],
        "amount" => floatval(number_format($amount, 2, '.', '')),
        "currency" => $payZQ_order[0]['currency'],
        "ip" => $ip,
      );
    }

    /* HG - make a call to refund API */
    public function apiRefund($refund_id, $currency, $mode, $id_card, $amount = null)
    {
        if (!$this->getSecretKey() || !$this->getMerchantKey()) {
            $this->errors['cred'] = $this->l('Invalid PayZQ credentials, please check your configuration.s');
        }

        if (empty($refund_id)) {
            $this->errors['refund'] = $this->l('You must enter a valid PayZQ Transaction ID');
            return false;
        }

        $refund = Db::getInstance()->ExecuteS('SELECT *	FROM '._DB_PREFIX_.'payzq WHERE `id_transaction` = "'.pSQL($refund_id).'"');
        if ($mode == 1) { /* Total refund */
          $data = $this->generateRefundData($refund);
          $result = 2;

          if ($data['amount'] == 0) {
            $this->errors['refund'] = $this->l('The entire amount of the transaction has been refunded');
            return false;
          }

        } else { /* Partial refund */

          if (!(is_float($amount) + 0) && (is_int($amount) + 0)) {
            $this->errors['refund'] = $this->l('Value no valid');
            return false;
          }

          if ($amount == 0) {
            $this->errors['refund'] = $this->l('The refund amount can not be zero or null');
            return false;
          }

          $data = $this->generateRefundData($refund, $amount);
        }

        try {
          $new_amount_refunded = $data['amount'] + $refund[0]['refund'];

          $result =  ($new_amount_refunded == $refund[0]['amount']) ? 2 : 3;

          if ($new_amount_refunded > $refund[0]['amount']) {
            $this->errors['refund'] = $this->l('The amount to be retunded exceeds the amount of the transaction').' ('.$refund[0]['amount'].')';
            return false;
          }

          list($curl_body, $curl_status, $curl_header) = $this->callCURL($data);

          if ($curl_status != 200 || !($message = json_decode($curl_body, true))) {
            $this->errors['exception'] = $curl_body;
            return false;
          }

          if ($message['code'] !== '00') {
            // error no pudo ser refunded
            $this->errors['refund'] = $message['code'].' - '.$this->l('Refund declined. If the error persist contact PayZQ');
            return false;
          }

          if ($message['code'] === '00') {
            //si lo que se retorna es menor al pago
            Db::getInstance()->Execute(
                'UPDATE `'._DB_PREFIX_.'payzq` SET `result` = '.(int)$result.', `date_add` = NOW(), `refund` = "'
                .pSQL($new_amount_refunded).'" WHERE `id_transaction` = "'.pSQL($refund_id).'"'
            );

            $this->addTransaction(
              $message['authorization_code'],
              $data['transaction_id'],
              $data['target_transaction_id'],
              $refund[0]['name'],
              $refund[0]['type'],
              0,
              $data['amount'],
              $refund[0]['currency'],
              $result,
              $refund[0]['id_cart']
            );
          }
        } catch (Exception $e) {
          // Something else happened, completely unrelated to PayZQ
          $this->errors['exception'] = $this->l('1 An error has ocurred. If persist contact PayZQ').' '.$e->getMessage();
          return false;
        }

        $id_order = Order::getOrderByCartId($id_card);
        $order = new Order($id_order);
        $state = Db::getInstance()->getValue('SELECT `result` FROM '._DB_PREFIX_.'payzq WHERE `id_transaction` = "'.pSQL($refund_id).'"');

        if ($state == 2) {
            /* Refund State */
            $order->setCurrentState(7);
        } elseif ($state == 3) {
            /* Partial Refund State */
            $order->setCurrentState(Configuration::get(self::_PS_PAYZQ_.'partial_refund_state'));
        }
        $this->success['refund_success'] = $this->l('Refunds processed successfully');
    }

    /* HG - make a call to charge a payment API */
    public function chargev2(array $params)
    {
        if (!$this->getSecretKey() || !$this->getMerchantKey()) {
            die(Tools::jsonEncode(array('code' => '0', 'msg' => $this->l('Invalid PayZQ credentials, please check your configuration.'))));
        }

        try {
            if (!$params['cardHolderName'] || $params['cardHolderName'] == '') {
                $cardHolderName = $this->context->customer->firstname.' '.$this->context->customer->lastname;
            } else {
                $cardHolderName = $params['cardHolderName'];
            }

            $data = array('transaction_id' => '-', 'target_transaction_id' => '-');
            $data = $this->generateData($cardHolderName, $params);

            list($curl_body, $curl_status, $curl_header) = $this->callCURL($data);

            print_r('$curl_body');
            print_r($curl_body);
            print_r('$curl_status');
            print_r($curl_status);
            print_r('$curl_header');
            print_r($curl_header);

            if ($curl_status == 200 && $message = json_decode($curl_body, true)) {
              if ($message['code'] === '00') {
                $status = 1;
                $authorization_code = $message['authorization_code'];
                $this->addTransaction($authorization_code, $data['transaction_id'], $data['target_transaction_id'], $params['cardHolderName'], $params['type'], $params['amount'], 0, $params['currency'], $status, (int)$this->context->cart->id);

                $message = $this->l('PayZQ Transaction authorization_code:').' '.$authorization_code;

                $paid = $params['amount'];

                /* Add transaction on Prestashop back Office (Order) */
                parent::validateOrder(
                    (int)$this->context->cart->id,
                    (int)Configuration::get('PS_OS_PAYMENT'),
                    (float)$paid,
                    $this->l('Payment by PayZQ'),
                    $message,
                    array(),
                    null,
                    false,
                    $this->context->customer->secure_key
                );

                $id_order = Order::getOrderByCartId($this->context->cart->id);

                /* Ajax redirection Order Confirmation */
                die(Tools::jsonEncode(array(
                    // 'chargeObject' => $charge,
                    'code' => '1',
                    'url' => Context::getContext()->link->getPageLink('order-confirmation', true).'?id_cart='.(int)$this->context->cart->id.'&id_module='.(int)$this->id.'&id_order='.(int)$id_order.'&key='.$this->context->customer->secure_key,
                )));

              } else {
                $status = 0;
                $authorization_code = $message['authorization_code'];
                $this->addTransaction($authorization_code, $data['transaction_id'], $data['target_transaction_id'], $params['cardHolderName'], $params['type'], $params['amount'], 0, $params['currency'], $status, (int)$this->context->cart->id);

                die(Tools::jsonEncode(array(
                    'code' => '0',
                    'msg' => $message['code'].' - '.$this->l('Payment declined. Unknown error, please use another card or contact us.'),
                )));

              }
            } else {
              // server error
              $this->addTransaction($curl_body, $data['transaction_id'], $data['target_transaction_id'], $params['cardHolderName'], $params['type'], $params['amount'], 0, $params['currency'], 0, (int)$this->context->cart->id);

              die(Tools::jsonEncode(array(
                  'code' => '0',
                  'msg' => $this->l('2 An error has ocurred. If persist contact PayZQ').' '.$curl_body,
              )));
            }

        } catch (Exception $e) {

            $this->addTransaction($e->getMessage(), $data['transaction_id'], $data['target_transaction_id'], $params['cardHolderName'], $params['type'], $params['amount'], 0, $params['currency'], 0, (int)$this->context->cart->id);

            die(Tools::jsonEncode(array(
                'code' => '0',
                'msg' => $this->l('3 An error has ocurred. If persist contact PayZQ').' '.$e->getMessage(),
            )));
        }
    }

    /* HG - generate transaction_id*/
    private function getNextCodeTransaction()
    {
      $sql = 'SELECT UTC_TIMESTAMP() as time FROM DUAL;';
      $result = Db::getInstance()->ExecuteS($sql);

      $host= gethostname();
      $ip = gethostbyname($host);

      $chars = array(' ', '-', '.', ':');
      $ip = str_replace($chars, '', $ip);
      $time = str_replace($chars, '', $result[0]['time']);

      return 'PAY'.$ip.'ZQ'.$time;
    }

    /* HG - generate data for authorize_and_capture transaction */
    private function generateData($cardHolderName, $params)
    {
      $address_delivery = new Address($this->context->cart->id_address_delivery);
      $address_invoice = new Address($this->context->cart->id_address_invoice);

      $country_delivery = new Country($address_delivery->id_country);
      $country_invoice = new Country($address_invoice->id_country);

      $order_shipping_cost_no_tax = round($this->context->cart->getOrderTotal(false, 5), 2);
      $order_shipping_cost_total = round($this->context->cart->getOrderTotal(true, 5), 2);

      $order_wrapping_cost_no_tax = round($this->context->cart->getOrderTotal(false, 6), 2);
      $order_wrapping_cost_total = round($this->context->cart->getOrderTotal(true, 6), 2);

      $products =  $this->context->cart->getProducts();

      $credit_card = array(
        "cardholder" => $cardHolderName,
        "type" => $params['type'],
        "number" => $params['number'],
        "cvv" => $params['cvv'],
        "expiry" => $params['expiry'],
      );

      $avs = array(
        "address" => $address_invoice->firstname. ' ' .$address_invoice->lastname,
        "country" => 'ESP',
        "state_province" => $address_invoice->city,
        "email" => 'test@test.com',
        "cardholder_name" => $cardHolderName,
        "postal_code" => $address_invoice->postcode,
        "phone" => '',
        "city" => $address_invoice->city,
      );

      $billing = array(
        "name" => $address_invoice->firstname. ' ' .$address_invoice->lastname,
        "fiscal_code" => $address_invoice->postcode,
        "address" => $address_invoice->address1. ' '. $address_invoice->address2,
        "country" => $country_invoice->iso_code,
        "state_province" => $address_invoice->city,
        "postal_code" => $address_invoice->postcode,
        "city" => $address_invoice->city,
      );

      $shipping = array(
        "name" => $cardHolderName,
        "fiscal_code" => '',
        "address" => $address_delivery->address1. ' '. $address_delivery->address2,
        "country" => $country_invoice->iso_code, //$this->context->country->iso_code,
        "state_province" => $address_delivery->city,
        "postal_code" => $address_delivery->postcode,
        "city" => $address_delivery->city,
      );

      $breakdown = array();

      foreach ($products as $key => $product) {
        $price = round($product['price'], 2);
        $total_wt = round($product['total_wt'], 2);

        $breakdown[] = array(
          "description" => $product['name'],
          "subtotal" => $price,
          "taxes" => $total_wt - $price,
          "total" => $total_wt
        );
      }

      if ($order_shipping_cost_total > 0) {
        $breakdown[] = array(
          "description" => 'Shipping cost',
          "subtotal" => $order_shipping_cost_no_tax,
          "taxes" => $order_shipping_cost_total - $order_shipping_cost_no_tax,
          "total" => floatval($order_shipping_cost_total)
        );
      }

      if ($order_wrapping_cost_total > 0) {
        $breakdown[] = array(
          "description" => 'Wrapping cost',
          "subtotal" => $order_wrapping_cost_no_tax,
          "taxes" => $order_wrapping_cost_total - $order_wrapping_cost_no_tax,
          "total" => floatval($order_wrapping_cost_total)
        );
      }

      $context = $this->context;

      $amount = $context->cart->getOrderTotal();

      $nex_code_transaction = $this->getNextCodeTransaction();

      $host= gethostname();
      $ip = gethostbyname($host);

      return array(
        "type" => "authorize_and_capture",
        "transaction_id" => $nex_code_transaction,
        "target_transaction_id" => '',
        "amount" => floatval(number_format($params['amount'], 2, '.', '')),
        "currency" => $params['currency'],
        "credit_card" => $credit_card,
        "avs" => $avs,
        "billing" => $billing,
        "shipping" => $shipping,
        "breakdown" => $breakdown,
        "3ds" => false,
        "ip" => $ip,
      );
    }


    public function cypherData($json_data)
    {
      $merchant_key = Configuration::get(self::_PS_PAYZQ_.'merchant_key');
      $margen = (strlen($json_data) == 16) ? 0 : (intdiv(strlen($json_data), 16) + 1) * 16 - strlen($json_data);
      // AES-128-CFB porque la merchant_key es de 16 bytes
      // the option 3 is not documented in php web page
      $data = openssl_encrypt($json_data.str_repeat('#', $margen), 'aes-128-cbc', $merchant_key, 3, $this->iv);
      $json_compress = gzcompress($data);
      $json_b64 = base64_encode($json_compress);
      $json_utf = utf8_decode($json_b64);
      return $json_utf;
    }

    public function decodeCypherData($codified_data)
    {
      $merchant_key = Configuration::get(self::_PS_PAYZQ_.'merchant_key');
      $compressed_data = base64_decode($codified_data);
      $descompressed_data = gzuncompress($compressed_data);
      $decrypted_data = openssl_decrypt($descompressed_data , 'aes-128-cbc' , $merchant_key, OPENSSL_ZERO_PADDING, $this->iv );
      $clean_text = str_replace('#','', $decrypted_data);
      $data = json_decode($clean_text, true);
      return $data;
    }

    /* HG - call API PayZQ*/
    private function callCURL($data)
    {
      $mode = Configuration::get(self::_PS_PAYZQ_.'mode');
      $merchant_key = Configuration::get(self::_PS_PAYZQ_.'merchant_key');

      if ($mode == 1) {
        // mode test
        $token = Configuration::get(self::_PS_PAYZQ_.'test_key');
      } else {
        $token = Configuration::get(self::_PS_PAYZQ_.'key');
      }

      $codify_data = ($merchant_key && $merchant_key != '') ? true : false;

      $token_payload = JWT::decode($token, $this->key_jwt, false);
      $cypher = (in_array('cypher', $token_payload['security'])) ? true : false;

      $curl_headers = array(
        'Content-Type: application/json',
        'Authorization: JWT '.$token
      );

      $json = json_encode($data, JSON_PRESERVE_ZERO_FRACTION);

      if ($codify_data) {
        $cypher_data = $this->cypherData($json);
        $json = json_encode(array('request' => $cypher_data));
      }

      $curl = new Curl();
      return $curl->request('post', $this->api_base_url, $curl_headers, $json, false);
    }

    /* HG - insert a record on payments table*/
    private function addTransaction($payzq_id, $transaction_id, $target_transaction_id, $name, $type, $amount, $refund, $currency, $result, $id_cart = 0)
    {
        if ($id_cart == 0) {
            $id_cart = (int)$this->context->cart->id;
        }

        if ($type == 'American Express') {
            $type = 'amex';
        } elseif ($type == 'Diners Club') {
            $type = 'diners';
        }

        /* Add request on Database */
        Db::getInstance()->Execute(
            'INSERT INTO '._DB_PREFIX_
            .'payzq (id_payzq, id_transaction, id_target_transaction, name, id_cart, type, amount, refund, currency, result, state, date_add)
            VALUES ("'.pSQL($payzq_id).'", "'.pSQL($transaction_id).'", "'.pSQL($target_transaction_id).'", "'.pSQL($name).'", \''.(int)$id_cart.'\', "'.pSQL(Tools::strtolower($type)).'", "'
            .pSQL($amount).'", "'.pSQL($refund).'", "'.pSQL(Tools::strtolower($currency)).'", '.(int)$result.', '.(int)Configuration::get(self::_PS_PAYZQ_.'mode').', NOW())'
        );
    }

    /* HG - display order confirmation view */
    public function hookOrderConfirmation($params)
    {
        $this->context->smarty->assign('payzq_order_reference', pSQL($params['order']->reference));
        if ($params['order']->module == $this->name) {
            return $this->display(__FILE__, 'views/templates/front/order-confirmation.tpl');
        }
    }

    /* HG - show the 3DS form */
    public function displaySecure()
    {
        $fields_form = array();
        $fields_value = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('3D secure'),
            ),
            'input' => array(
                array(
                    'type' => 'radio',
                    'name' => self::_PS_PAYZQ_.'secure',
                    'desc' => $this->l(''),
                    'values' => array(
                        array(
                            'id' => 'secure_all',
                            'value' => 1,
                            'label' => $this->l('Request 3D-Secure authentication on all charges'),
                        ),
                        array(
                            'id' => 'secure_only',
                            'value' => 0,
                            'label' => $this->l('Request 3D-Secure authentication on charges above 50 EUR/USD/GBP only'),
                        )
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right button',
            ),
        );

        $submit_action = 'submit_secure';
        $fields_value = array_merge($fields_value, array(
            self::_PS_PAYZQ_.'secure' => Configuration::get(self::_PS_PAYZQ_.'secure'),
        ));

        return $this->renderGenericForm($fields_form, $fields_value, $this->getSectionShape(), $submit_action);
    }

    /* HG - show the connection form */
    public function displayForm()
    {
        $fields_form = array();
        $fields_value = array();
        $type = 'switch';

        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('PayZQ log in'),
            ),
            'input' => array(
                array(
                    'type' => 'radio',
                    'label' => $this->l('Mode'),
                    'name' => self::_PS_PAYZQ_.'mode',
                    'desc' => $this->l(' '),
                    'size' => 500,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Test'),
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Live'),
                        )
                    ),
                  ),
                  array(
                      'type' => 'text',
                      'label' => $this->l('PayZQ Merchant Key'),
                      'name' => self::_PS_PAYZQ_.'merchant_key',
                      'size' => 20,
                      'id' => 'merchant_key',
                      'class' => 'fixed-width-xxl',
                      'required' => true
                  ),
                  array(
                      'type' => 'text',
                      'label' => $this->l('PayZQ Tokne'),
                      'name' => self::_PS_PAYZQ_.'key',
                      'size' => 20,
                      'id' => 'secret_key',
                      'class' => 'fixed-width-xxl',
                      'required' => true
                  ),
                  array(
                      'type' => 'text',
                      'label' => $this->l('PayZQ Test Token'),
                      'name' => self::_PS_PAYZQ_.'test_key',
                      'id' => 'test_secret_key',
                      'size' => 20,
                      'class' => 'fixed-width-xxl',
                      'required' => true
                  ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right button',
                ),
          );

        $submit_action = 'submit_login';
        $fields_value = array_merge($fields_value, array(
            self::_PS_PAYZQ_.'mode' => Configuration::get(self::_PS_PAYZQ_.'mode'),
            self::_PS_PAYZQ_.'key' => Configuration::get(self::_PS_PAYZQ_.'key'),
            self::_PS_PAYZQ_.'merchant_key' => Configuration::get(self::_PS_PAYZQ_.'merchant_key'),
            self::_PS_PAYZQ_.'test_key' => Configuration::get(self::_PS_PAYZQ_.'test_key'),
        ));

        return $this->renderGenericForm($fields_form, $fields_value, $this->getSectionShape(), $submit_action);
    }

     /* HG - show the transaction's table  */
    public function displayTransaction($refresh = 0, $token_ajax = null, $id_employee = null)
    {
        $token_module = '';
        if ($token_ajax && $id_employee) {
            $employee = new Employee($id_employee);
            $this->context->employee = $employee;
            $token_module = Tools::getAdminTokenLite('AdminModules', $this->context);
        }

        if ($token_module == $token_ajax || $refresh == 0) {
            $this->getSectionShape();
            $orders = Db::getInstance()->ExecuteS('SELECT * FROM '._DB_PREFIX_.'payzq WHERE id_target_transaction = "" OR id_target_transaction IS NULL ORDER BY date_add DESC');
            $tenta = array();
            $html = '';

            foreach ($orders as $order) {
                if ($order['result'] == 0) {
                    $result = 'n';
                } elseif ($order['result'] == 1) {
                    $result = '';
                } elseif ($order['result'] == 2) {
                    $result = 2;
                } else {
                    $result = 3;
                }

                $refund = Tools::safeOutput($order['amount']) - Tools::safeOutput($order['refund']);
                array_push($tenta, array(
                    'date' => Tools::safeOutput($order['date_add']),
                    'last_digits' => Tools::safeOutput($order['last4']),
                    'type' => Tools::strtolower($order['type']),
                    'amount' => Tools::safeOutput($order['amount']),
                    'currency' => Tools::safeOutput(Tools::strtoupper($order['currency'])),
                    'refund' => $refund,
                    'id_transaction' => Tools::safeOutput($order['id_transaction']),
                    'name' => Tools::safeOutput($order['name']),
                    'result' => $result,
                    'state' => Tools::safeOutput($order['state']) ? $this->l('Test') : $this->l('Live'),
                ));
            }

            $this->context->smarty->assign('tenta', $tenta);
            $this->context->smarty->assign('refresh', $refresh);
            $this->context->smarty->assign('token_payzq', Tools::getAdminTokenLite('AdminModules'));
            $this->context->smarty->assign('id_employee', $this->context->employee->id);
            $this->context->smarty->assign('path', Tools::getShopDomainSsl(true, true).$this->_path);

            $html .= $this->display($this->_path, 'views/templates/admin/transaction.tpl');

            return $html;
        }

    }

    /* HG - show the refund form  */
    public function displayRefundForm()
    {
        $output = '';

        $fields_form = array();
        $fields_value = array();

        $fields_value1 = array();

        $fields_form[1]['form'] = array(
            'legend' => array(
                'title' => $this->l('Choose an Order you want to Refund'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('PayZQ Transaction ID'),
                    'desc' => '<i>'.$this->l('To process a refund, please input PayZQ’s payment ID below, which can be found in the « Payments » tab of this plugin').'</i>',
                    'name' => self::_PS_PAYZQ_.'refund_id',
                    'class' => 'fixed-width-xxl',
                    'required' => true
                ),
                array(
                    'type' => 'radio',
                    'desc' => '<i>'.$this->l('Refunds take 5 to 10 days to appear on your customer\'s statement').'</i>',
                    'name' => self::_PS_PAYZQ_.'refund_mode',
                    'size' => 50,
                    'values' => array(
                        array(
                            'id' => 'active_on_refund',
                            'value' => 1,
                            'label' => $this->l('Full refund')
                        ),
                        array(
                            'id' => 'active_off_refund',
                            'value' => 0,
                            'label' => $this->l('Partial Refund')
                        )
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Amount'),
                    'desc' => $this->l('Please, enter an amount your want to refund'),
                    'name' => self::_PS_PAYZQ_.'refund_amount',
                    'size' => 20,
                    'id' => 'refund_amount',
                    'class' => 'fixed-width-sm',
                    'required' => true
                ),
            ),
            'submit' => array(
                'title' => $this->l('Request Refund'),
                'class' => 'btn btn-default pull-right button',
            ),
        );
        $this->refund = 1;

        $submit_action = 'submit_refund_id';
        $fields_value = array_merge($fields_value1, array(
            self::_PS_PAYZQ_.'refund_id' => Configuration::get(self::_PS_PAYZQ_.'refund_id'),
            self::_PS_PAYZQ_.'refund_mode' => Configuration::get(self::_PS_PAYZQ_.'refund_mode'),
            self::_PS_PAYZQ_.'refund_amount' => Configuration::get(self::_PS_PAYZQ_.'refund_amount'),
        ));

        $output .= $this->renderGenericForm($fields_form, $fields_value, $this->getSectionShape(), $submit_action);

        if ($this->refund) {
            $refund_id = Tools::getValue(self::_PS_PAYZQ_.'refund_id');
            $orders = Db::getInstance()->ExecuteS('SELECT *	FROM '._DB_PREFIX_.'payzq WHERE `id_transaction` = "'.pSQL($refund_id).'"');

            $tenta = array();

            foreach ($orders as $order) {
                if ($order['result'] == 0) {
                    $result = 'n';
                } elseif ($order['result'] == 1) {
                    $result = '';
                } elseif ($order['result'] == 2) {
                    $result = 2;
                } else {
                    $result = 3;
                }

                $refund = Tools::safeOutput($order['amount']) - Tools::safeOutput($order['refund']);
                array_push($tenta, array(
                    'date' => Tools::safeOutput($order['date_add']),
                    'last_digits' => Tools::safeOutput($order['last4']),
                    'type' => Tools::strtolower($order['type']),
                    'amount' => Tools::safeOutput($order['amount']),
                    'currency' => Tools::safeOutput(Tools::strtoupper($order['currency'])),
                    'refund' => $refund,
                    'id_transaction' => Tools::safeOutput($order['id_transaction']),
                    'name' => Tools::safeOutput($order['name']),
                    'result' => $result,
                    'state' => Tools::safeOutput($order['state']) ? $this->l('Test') : $this->l('Live'),
                ));
            }

            $this->context->smarty->assign('tenta', $tenta);
            $output .= $this->display($this->_path, 'views/templates/admin/transaction.tpl');
        }

        return $output;
    }

    /* HG - show the FAQ  */
    public function displayFaq()
    {
        return $this->display($this->_path, 'views/templates/admin/faq.tpl');
    }

    /* HG - show the contact view  */
    public function displayContact()
    {
        $this->getSectionShape();
        return $this->display($this->_path, 'views/templates/admin/contact.tpl');
    }

    /* HG - show the main view of module configuration  */
    public function displaySomething()
    {
        $this->getSectionShape();
        $return_url = '';
        if (Configuration::get('PS_SSL_ENABLED')) {
            $domain = Tools::getShopDomainSsl(true);
        } else {
            $domain = Tools::getShopDomain(true);
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $return_url = urlencode($domain.$_SERVER['REQUEST_URI'].'#payzq_step_2');
        }
        $this->context->smarty->assign('return_url', $return_url);
        return $this->display($this->_path, 'views/templates/admin/started.tpl');
    }

    public static function generateList(array $values, $identifier = 'mode')
    {
        $arr = array();

        foreach (array_values($values) as $key => $value) {
            array_push($arr, array($identifier => $key, 'name' => $value));
        }

        return $arr;
    }

    public function checkList($key, array $arr)
    {
        if (($id = (int)Configuration::get($this->c($key))) !== false && isset($arr[(int)$id])) {
            return $arr[(int)$id];
        }
        return false;
    }

    public function generateDescription(array $ar = array())
    {
        if (!empty($ar)) {
            return '<p>'.implode('</p><p>', $ar).'</p>';
        }
        return '';
    }

    private function getSectionShape()
    {
        return 'payzq_step_'.(int)$this->section_shape++;
    }

    /* HG - render a generic form */
    public function renderGenericForm($fields_form, $fields_value = array(), $fragment = false, $submit = false, array $tpl_vars = array())
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        if ($fragment !== false) {
            $helper->token .= '#'.$fragment;
        }

        if ($submit) {
            $helper->submit_action = $submit;
        }

        $helper->tpl_vars = array_merge(array(
            'fields_value' => $fields_value,
            'id_language' => $this->context->language->id,
            'back_url' => $this->context->link->getAdminLink('AdminModules')
            .'&configure='.$this->name
            .'&tab_module='.$this->tab
            .'&module_name='.$this->name.($fragment !== false ? '#'.$fragment : '')
        ), $tpl_vars);

        return $helper->generateForm($fields_form);
    }

    public function renderGenericOptions($fields_form, $fragment = false, array $tpl_vars = array())
    {
        $helper = new HelperOptions($this);
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = array('save' => array(
            'href' => '',
            'desc' => $this->l('Save')
        ));

        $helper->id = $this->id;
        $helper->module = $this;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        if ($fragment !== false) {
            $helper->token .= '#'.$fragment;
        }

        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->title = $this->displayName;
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = $default_lang;
        $helper->tpl_vars = array_merge(array(
            'submit_action' => 'index.php',
            'id_language' => $this->context->language->id,
            'back_url' => $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name
        ), $tpl_vars);

        return $helper->generateOptions($fields_form);
    }

    /* get the PayZQ secret key (token)  */
    public function getSecretKey()
    {
        if (Configuration::get(self::_PS_PAYZQ_.'mode')) {
            return Configuration::get(self::_PS_PAYZQ_.'test_key');
        } else {
            return Configuration::get(self::_PS_PAYZQ_.'key');
        }
    }

    /* get the PayZQ merchant secret key  */
    public function getMerchantKey()
    {
        return Configuration::get(self::_PS_PAYZQ_.'merchant_key');
    }

    /* HG - insert the payment option to available list */
    public function hookPaymentOptions($params)
    {
        $payment_options = array();
        $embeddedOption = new PaymentOption();
        $default_country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'));
        if (Tools::strtolower($default_country->iso_code) == 'us') {
            $cc_img = 'cc_merged.png';
        } else {
            $cc_img = 'logo-payment.png';
        }
        $embeddedOption->setCallToActionText($this->l('Pay by card'))
                       ->setForm($this->generateForm())
                       ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/'.$cc_img));
        $payment_options[] = $embeddedOption;
        return $payment_options;
    }

    /* HG - insert the resources module */
    public function hookHeader()
    {
        if (Tools::getValue('controller') == "order") {
            $this->context->controller->registerStylesheet($this->name.'-frontcss', 'modules/'.$this->name.'/views/css/front.css');
            $this->context->controller->registerJavascript($this->name.'-paymentjs', 'modules/'.$this->name.'/views/js/payment_payzq.js');
            $this->context->controller->registerJavascript($this->name.'-modaljs', 'modules/'.$this->name.'/views/js/jquery.the-modal.js');
            $this->context->controller->registerStylesheet($this->name.'-modalcss', 'modules/'.$this->name.'/views/css/the-modal.css');
        }
    }

    /* HG - generate the payment form */
    protected function generateForm()
    {
        // TODO: ojo aqui con este true
        if (true || Configuration::get('PS_SSL_ENABLED')) {
            $context = $this->context;

            $amount = $context->cart->getOrderTotal();
            $currency = $context->currency->iso_code;
            $secure_mode_all = Configuration::get(self::_PS_PAYZQ_.'secure');
            if (!$secure_mode_all && $amount >= 50) {
                $secure_mode_all = 1;
            }

            $address_delivery = new Address($context->cart->id_address_delivery);

            $billing_address = array(
                'line1' => $address_delivery->address1,
                'line2' => $address_delivery->address2,
                'city' => $address_delivery->city,
                'zip_code' => $address_delivery->postcode,
                'country' => $address_delivery->country,
                'phone' => $address_delivery->phone ? $address_delivery->phone : $address_delivery->phone_mobile,
                'email' => $this->context->customer->email,
            );

            $domain = $context->link->getBaseLink($context->shop->id, true);

            $this->context->smarty->assign(
                array(
                    'publishableKey' => $this->getSecretKey(),
                    'mode' => Configuration::get(self::_PS_PAYZQ_.'mode'),
                    'customer_name' => $this->context->customer->firstname.' '.$this->context->customer->lastname,
                    'currency' => $currency,
                    'amount_ttl' => $amount,
                    'baseDir' => $domain,
                    'secure_mode' => $secure_mode_all,
                    'payzq_mode' => Configuration::get(self::_PS_PAYZQ_.'mode'),
                    'module_dir' => $this->_path,
                    'billing_address' => Tools::jsonEncode($billing_address),
                )
            );
        }
        return $this->context->smarty->fetch('module:payzq_ps/views/templates/hook/payment.tpl');
    }
}
