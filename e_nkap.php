<?php
/**
 * 2007-2021 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use Enkap\OAuth\Model\CallbackUrl;
use Enkap\OAuth\Model\Status;
use Enkap\OAuth\Services\CallbackUrlService;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButton;
use PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButtonsCollection;

if (!defined('_PS_VERSION_')) {
    exit;
}

class E_Nkap extends PaymentModule
{
    protected $config_form = false;
    protected $api_currency = 'XAF';
    protected $htmlContent = '';
    /**
     * @var string[]
     */
    private $limited_currencies;

    public function __construct()
    {
        $this->name = 'e_nkap';
        $this->tab = 'payments_gateways';
        $this->author = 'Camoo Sarl';

        $this->version = '1.0.0';
        $this->author_uri = 'https://www.enkap.cm';

        $this->controllers = ['confirmation', 'validation', 'notification'];
        $this->need_instance = 0;

        $this->bootstrap = true;
        $this->currencies = true;
        $this->limited_currencies = ['XAF'];
        $this->limited_countries = ['CM'];
        $this->currencies_mode = 'checkbox';

        parent::__construct();

        $this->displayName = $this->trans('SmobilPay for e-commerce', [], 'Modules.E_nkap.Admin');
        $this->description = $this->trans('SmobilPay for e-commerce Prestashop Gateway', [], 'Modules.E_nkap.Admin');
        $this->confirmUninstall = $this->trans(
            'Are you sure you want to delete these details?',
            [],
            'Modules.E_nkap.Shop'
        );
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];

        $_key = Configuration::get('E_NKAP_ACCOUNT_KEY');
        $_secret = Configuration::get('E_NKAP_ACCOUNT_SECRET');

        if (empty($_key) || empty($_secret)) {
            $this->warning = $this->trans(
                'The "Consumer Key" and "Consumer secret" fields must be configured before using this module.',
                [],
                'Modules.E_nkap.Admin'
            );
        }

        /* Backward compatibility */
        if (_PS_VERSION_ < '1.5') {
            require(_PS_MODULE_DIR_ . $this->name . '/backward_compatibility/backward.php');
        }

        require_once dirname(__FILE__) . '/classes/ENkapPaymentCart.php';
    }


    public function getApiCurrency(): string
    {
        return $this->api_currency;
    }

    public function getLanguageKey($langId): string
    {
        $iso_code = Language::getIsoById($langId);

        if (empty($iso_code)) {
            return 'fr';
        }

        return in_array($iso_code, ['fr', 'en']) ? $iso_code : 'en';
    }

    public function install()
    {

        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        if ($this->limited_countries && is_array($this->limited_countries) && !empty($this->limited_countries) &&
            !in_array($iso_code, $this->limited_countries)) {
            $this->_errors[] = $this->l('This module is not available in your country');
            return false;
        }

        Configuration::updateValue('E_NKAP_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('payment') &&
            $this->registerHook('moduleRoutes') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('displayAdminOrderLeft') &&
            $this->registerHook('actionGetAdminOrderButtons') &&
            $this->registerHook('displayAdminOrderMain') &&
            $this->registerHook('displayPaymentReturn') &&
            $this->checkOStatus() &&
            $this->installDb();
    }

    public function installDb()
    {
        return Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'e_nkap_payments` (
                `id_enkap_payment` int(10)  UNSIGNED NOT NULL auto_increment,
                `id_cart` int(10) UNSIGNED NULL,
                `id_order` int(11) NOT NULL,
                `merchant_reference_id` varchar(128) NOT NULL DEFAULT  \'\',
                `order_transaction_id` varchar(128) NOT NULL DEFAULT \'\',
                `status` varchar(50) DEFAULT NULL,
                `order_total` decimal(11, 6) UNSIGNED NULL,
                `date_status` datetime      NOT NULL DEFAULT \'2021-05-20 00:00:00\',
                `date_upd`            timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `date_add`            timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `remote_ip`           varbinary(64) NOT NULL DEFAULT \'0.0.0.0\',
                PRIMARY KEY (`id_enkap_payment`)
            ) DEFAULT CHARSET=utf8 ;');
    }

    public function uninstall()
    {
        Configuration::deleteByName('E_NKAP_LIVE_MODE');

        Db::getInstance()->execute('DROP TABLE `' . _DB_PREFIX_ . 'e_nkap_payments` IF EXISTS;');
        return parent::uninstall();
    }

    public function checkOStatus()
    {
        $os = new OrderState((int)Configuration::get('PS_OS_E_NKAP'));
        if (!Validate::isLoadedObject($os)) {
            $order_state = new OrderState();
            $order_state->name = array();

            foreach (Language::getLanguages() as $language) {
                if (Tools::strtolower($language['iso_code']) === 'fr') {
                    $order_state->name[$language['id_lang']] = 'En attente de validation paiement par SmobilPay';
                } else {
                    $order_state->name[$language['id_lang']] = 'Waiting for SmobilPay payment validation';
                }
            }

            $order_state->send_email = true;
            $order_state->color = 'RoyalBlue';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = false;
            $order_state->invoice = false;
            $order_state->shipped = false;
            $order_state->paid = false;
            $order_state->template = 'enkap_awaiting';

            if ($order_state->add()) {
                $source = dirname(__FILE__) . '/logo.gif';
                $destination = dirname(__FILE__) . '/../../img/os/' . (int)$order_state->id . '.gif';
                @copy($source, $destination);
            }

            Configuration::updateValue('PS_OS_E_NKAP', (int)$order_state->id);
        }

        $os = new OrderState((int)Configuration::get('PS_OS_E_NKAP_ACCEPTED'));
        if (!Validate::isLoadedObject($os)) {
            $order_state = new OrderState();
            $order_state->name = array();

            foreach (Language::getLanguages() as $language) {
                if (Tools::strtolower($language['iso_code']) == 'fr') {
                    $order_state->name[$language['id_lang']] = 'Paiement par SmobilPay accepté';
                } else {
                    $order_state->name[$language['id_lang']] = 'SmobilPay payment accepted';
                }
            }

            $order_state->send_email = true;
            $order_state->color = '#32CD32';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = true;
            $order_state->invoice = true;
            $order_state->shipped = false;
            $order_state->paid = true;
            $order_state->template = 'enkap_payment';

            if ($order_state->add()) {
                $source = dirname(__FILE__) . '/logo.gif';
                $destination = dirname(__FILE__) . '/../../img/os/' . (int)$order_state->id . '.gif';
                @copy($source, $destination);
            }

            Configuration::updateValue('PS_OS_E_NKAP_ACCEPTED', (int)$order_state->id);
        }

        foreach (Language::getLanguages() as $lang) {
            $this->installMailTemplates($lang['iso_code']);
        }
        return true;
    }

    private function displayAdminInfo()
    {
        return $this->display(__FILE__, './views/templates/hook/infos.tpl');
    }

    public function generateReferenceID()
    {
        // Version 4 UUIDs generation
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );
        } else {
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff)
            );
        }
    }

    public function installMailTemplates($lang_iso)
    {
        $templates = [
            'enkap_awaiting',
            'enkap_payment'
        ];
        $formats = [
            'html',
            'txt'
        ];
        foreach ($templates as $template) {
            foreach ($formats as $f) {
                @copy(_PS_MODULE_DIR_ . $this->name .
                    '/mails/' . $template . '.' . $f, _PS_MAIL_DIR_ . $lang_iso . '/' . $template . '.' . $f);
            }
        }
    }

    public function getContent(): string
    {
        if (Tools::isSubmit('submitE_nkapModule') === true) {
            $this->htmlContent .= $this->postProcess();
        }
        $this->htmlContent .= $this->displayAdminInfo();
        $this->htmlContent .= $this->renderForm();
        return $this->htmlContent;
    }

    protected function renderForm(): string
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitE_nkapModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected function getConfigForm(): array
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->trans('Live mode', [], 'Modules.E_nkap.Admin'),
                        'name' => 'E_NKAP_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->trans('Use this module in live mode', [], 'Modules.E_nkap.Admin'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Modules.E_nkap.Admin')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Modules.E_nkap.Admin')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->trans(
                            'Enter a valid Consumer Key from SmobilPay platform',
                            [],
                            'Modules.E_nkap.Admin'
                        ),
                        'name' => 'E_NKAP_ACCOUNT_KEY',
                        'label' => $this->trans('Consumer Key', [], 'Modules.E_nkap.Admin'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'E_NKAP_ACCOUNT_SECRET',
                        'desc' => $this->trans(
                            'Enter a valid Consumer Secret from SmobilPay platform',
                            [],
                            'Modules.E_nkap.Admin'
                        ),
                        'label' => $this->trans('Consumer Secret', [], 'Modules.E_nkap.Admin'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', [], 'Modules.E_nkap.Admin'),
                ),
            ),
        );
    }

    protected function getConfigFormValues()
    {
        return [
            'E_NKAP_LIVE_MODE' => Configuration::get('E_NKAP_LIVE_MODE', true),
            'E_NKAP_ACCOUNT_KEY' => Configuration::get('E_NKAP_ACCOUNT_KEY'),
            'E_NKAP_ACCOUNT_SECRET' => Configuration::get('E_NKAP_ACCOUNT_SECRET'),
        ];
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
        if ($this->setReturnUrls()) {
            return $this->displayConfirmation($this->l('Settings updated successfully!'));
        }
        return $this->displayError(
            $this->l('Keys could not be setup properly. Please make sure that your Consumers keys pairs are valid')
        );
    }

    protected function setReturnUrls(): bool
    {
        $_key = Configuration::get('E_NKAP_ACCOUNT_KEY');
        $_secret = Configuration::get('E_NKAP_ACCOUNT_SECRET');
        $isTestMode = empty(Configuration::get('E_NKAP_LIVE_MODE'));

        $setup = new CallbackUrlService($_key, $_secret, [], $isTestMode);

        /** @var CallbackUrl $callBack */
        try {
            $callBack = $setup->loadModel(CallbackUrl::class);
            $callBack->return_url = $this->getReturnUrl();
            $callBack->notification_url = $this->getNotificationUrl();
            $result = $setup->set($callBack);
        } catch (Exception $exception) {
            return false;
        }
        return $result;
    }

    public function hookPaymentReturn($params)
    {
        if ($this->active === false) {
            return;
        }

        $order = $params['objOrder'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'total' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
        ));

        return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
    }

    public function hookModuleRoutes()
    {
        $my_link = array(
            'enkap_return_rule1' => array(
                'controller' => 'confirmation',
                'rule' => 'e-nkap/return{/:order_ref}',
                'keywords' => array(
                    'order_ref' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'order_ref'),
                    'status' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => $this->name,
                ),
            ),
            'enkap_notification_rule1' => array(
                'controller' => 'notification',
                'rule' => 'e-nkap/notification{/:order_ref}',
                'keywords' => array(
                    'order_ref' => array('regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'order_ref'),
                    'status' => array('regexp' => '[_a-zA-Z0-9-\pL]*'),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => $this->name,
                ),
            ),
        );

        return $my_link;
    }

    public static function getENkapUrl($not_lang = true)
    {
        $ssl_enable = Configuration::get('PS_SSL_ENABLED');
        $id_lang = (int)Context::getContext()->language->id;
        $id_shop = (int)Context::getContext()->shop->id;
        $rewrite_set = (int)Configuration::get('PS_REWRITING_SETTINGS');
        $ssl = null;
        static $force_ssl = null;

        if ($ssl === null) {
            if ($force_ssl === null) {
                $force_ssl = (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE'));
            }

            $ssl = $force_ssl;
        }

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && $id_shop !== null) {
            $shop = new Shop($id_shop);
        } else {
            $shop = Context::getContext()->shop;
        }

        $base = (($ssl && $ssl_enable) ? 'https://' . $shop->domain_ssl : 'http://' . $shop->domain);
        $langUrl = Language::getIsoById($id_lang) . '/';

        if ((!$rewrite_set && in_array($id_shop, array((int)Context::getContext()->shop->id, null))) ||
            !Language::isMultiLanguageActivated($id_shop) ||
            !(int)Configuration::get('PS_REWRITING_SETTINGS', null, null, $id_shop)) {
            $langUrl = '';
        }

        return $base . $shop->getBaseURI() . ($not_lang ? '' : $langUrl);
    }

    public static function getENkapLink($rewrite = 'e_nkap', $params = null, $id_lang = null, $no_lang = true)
    {
        $url = self::getENkapUrl($no_lang);
        $dispatcher = Dispatcher::getInstance();

        if ($params != null) {
            return $url . $dispatcher->createUrl($rewrite, $id_lang, $params);
        } else {
            return $url . $dispatcher->createUrl($rewrite);
        }
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $option = new PaymentOption();
        $option->setModuleName($this->name)
            ->setCallToActionText($this->trans('Pay with SmobilPay', [], 'Modules.E_nkap.Admin'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation(
                $this->fetch('module:' . $this->name . '/views/templates/hook/ps_enkap_intro.tpl')
            );

        return [$option];
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getReturnUrl()
    {
        return self::getENkapLink('enkap_return_rule1', array('order_ref' => ''));
    }

    public function getNotificationUrl()
    {
        return self::getENkapLink('enkap_notification_rule1', array('order_ref' => ''));
    }

    public function hookDisplayAdminOrderLeft($aParams)
    {
        return $this->hookDisplayAdminOrderMain($aParams);
    }

    public function hookDisplayAdminOrderMain($aParams)
    {
        if (!isset($aParams['id_order'])) {
            return;
        }
        $id_order = (int)$aParams['id_order'];
        $order = new Order($id_order);
        $en_payment = ENkapPaymentCart::getByIdCart($order->id_cart);
        if ($en_payment && is_array($en_payment)) {
            $this->context->smarty->assign(
                array(
                    'en_payment' => $en_payment,
                    'link' => $this->context->link->getModuleLink(
                        $this->name,
                        'validation',
                        [
                            'checkPayment' => 1,
                            'order_ref' => $en_payment['merchant_reference_id']
                        ],
                        true
                    ))
            );
            return $this->fetch('module:' . $this->name . '/views/templates/hook/admin-order.tpl');
        }
    }

    public function hookDisplayBackOfficeOrderActions(array $params)
    {
        return $this->hookActionGetAdminOrderButtons($params);
    }


    public function hookActionGetAdminOrderButtons(array $params)
    {
        if (empty($params['actions_bar_buttons_collection'])) {
            return;
        }

        if (!isset($params['id_order'])) {
            return;
        }
        $order = new Order($params['id_order']);
        $en_payment = ENkapPaymentCart::getByIdCart($order->id_cart);
        if (!empty($en_payment) && (empty($en_payment['status']) || in_array(
            $en_payment['status'],
            [Status::INITIALISED_STATUS, Status::IN_PROGRESS_STATUS, Status::CREATED_STATUS]
        ))) {
            $Url = $this->context->link->getModuleLink(
                $this->name,
                'validation',
                [
                    'checkPayment' => 1,
                    'order_ref' => $en_payment['merchant_reference_id']
                ],
                true
            );

            /** @var ActionsBarButtonsCollection $bar */
            $params['actions_bar_buttons_collection']->add(
                new ActionsBarButton(
                    'btn-secondary',
                    ['href' => $Url],
                    $this->l('Check SmobilPay Payment status')
                )
            );
        }
    }

    public function hookDisplayPaymentReturn($params): ?string
    {
        if ($this->active === false || !isset($params['order'])) {
            return null;
        }

        $order = $params['order'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'shop_name' => $this->context->shop->name,
            'reference' => $order->reference,
            'params' => $params,
            'success' => $order->getCurrentOrderState()->id == Configuration::get('PS_OS_MOMO_PAYMENT'),
            'total' => Tools::displayPrice($order->getOrdersTotalPaid(), new Currency($order->id_currency), false),
        ));

        return $this->fetch('module:' . $this->name . '/views/templates/hook/confirmation.tpl');
    }
}
