<?php

use Enkap\OAuth\Services\OrderService;
use Enkap\OAuth\Model\Order as EnkapOrder;

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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
class E_nkapValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if ($this->module->active === false) {
            die;
        }
        
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == $this->module->name) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->trans('This payment method is not available.', array(), 'Modules.E_nkap.Shop'));
        }

        $cart = $this->context->cart;
        $cart_id = (int)$cart->id;
        $amount = (float)$cart->getOrderTotal();
        $customer = new Customer($cart->id_customer);

        $secure_key = $customer->secure_key;
        if ( $secure_key != Context::getContext()->customer->secure_key ) {
            die($this->trans('Invalid Customer key.', array(), 'Modules.E_nkap.Shop'));
        }

        Context::getContext()->currency = new Currency(Context::getContext()->cart->id_currency);
        Context::getContext()->language = new Language(Context::getContext()->customer->id_lang);
        
        $merchantReferenceId = $this->module->generateReferenceID();
        $dataData = [
            'merchantReference' => $merchantReferenceId,
            'email' => $customer->email,
            'customerName' => $customer->firstname . ' ' . $customer->lastname,
            'totalAmount' => $amount,
            'description' => sprintf('Payment from %s', $this->context->shop->name),
            'currency' => $this->module->getApiCurrency(),
            'items' => []
        ];
        
        $cart_items = $this->context->cart->getProducts();
        foreach ($cart_items as $item) {
            $dataData['items'][] = [
                'itemId' => (int)$item['id_product'],
                'particulars' => $item['name'],
                'unitCost' => (float)$item['price'],
                'quantity' => $item['cart_quantity']
            ];
        }
        
        $_key = Configuration::get('E_NKAP_ACCOUNT_KEY');
        $_secret = Configuration::get('E_NKAP_ACCOUNT_SECRET');
        try {
            $orderService = new OrderService($_key, $_secret);
            $order = $orderService->loadModel(EnkapOrder::class);
            $order->fromStringArray($dataData);
            $response = $orderService->place($order);
            
            $payment_status = (int)Configuration::get('PS_OS_E_NKAP');
            $module_name = $this->module->displayName;
            $this->module->validateOrder($cart_id, $payment_status, $amount, $module_name, '', [],
                Context::getContext()->cart->id_currency, false, $secure_key);
            $this->logEnkapPayment($cart_id, (int)$this->module->currentOrder, $merchantReferenceId, $response->getOrderTransactionId());
            Tools::redirect($response->getRedirectUrl());
        } catch (Throwable $exception) {
            die('E-Nkap Payment Error: ' . $exception->getMessage());
        }
    }
    
    protected function logEnkapPayment(int $cartId, $orderId, string $merchantReferenceId, string $orderTransactionId)
    {
        $e_nkap_payment = new ENkapPaymentCart(ENkapPaymentCart::getIdByIdCart($cartId));
        $e_nkap_payment->id_cart = $cartId;
        $e_nkap_payment->id_order = $orderId;
        $e_nkap_payment->merchant_reference_id = $merchantReferenceId;
        $e_nkap_payment->order_transaction_id = $orderTransactionId;
    }
}
