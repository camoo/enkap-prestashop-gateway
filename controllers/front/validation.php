<?php

use Enkap\OAuth\Services\OrderService;
use Enkap\OAuth\Services\StatusService;

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
        if ( Tools::isSubmit('checkPayment') && ($merchant_reference_id = Tools::getValue('order_ref')) ) {
            $payment = ENkapPaymentCart::getByMerchantReference($merchant_reference_id);
            
            if ($payment && is_array($payment)) {
                $_key = Configuration::get('E_NKAP_ACCOUNT_KEY');
                $_secret = Configuration::get('E_NKAP_ACCOUNT_SECRET');
                $isTestMode = !empty(Configuration::get('E_NKAP_LIVE_MODE'));
                
                $statusService = new StatusService($_key, $_secret, [], $isTestMode);
                $status = $statusService->getByTransactionId($payment['order_transaction_id']);
                
                if ($status && is_object($status)) {
                    $id_order_state = false;
                    $extra_vars = array('transaction_id' => $payment['order_transaction_id']);
                    $order = new Order( (int)$payment['id_order'] );
                    
                    if ( $status->confirmed() ) {
                        $id_order_state = (int)Configuration::get('PS_OS_E_NKAP_ACCEPTED');
                    } elseif ( $status->initialized() || $status->isInProgress() ) {
                        $id_order_state = (int)Configuration::get('PS_OS_E_NKAP');
                    } elseif ( $status->failed() ) {
                        $id_order_state = (int)Configuration::get('PS_OS_ERROR');
                    } elseif ( $status->canceled() ) {
                        $id_order_state = (int)Configuration::get('PS_OS_CANCELED');
                    }
                    
                    if ( $id_order_state && $id_order_state !== $order->getCurrentOrderState()->id ) {
                        $new_history = new OrderHistory();
                        $new_history->id_order = (int) $order->id;
                        $new_history->changeIdOrderState((int) $id_order_state, $order, true);
                        $new_history->addWithemail(true, $extra_vars);
                        
                        if ( isset($extra_vars['transaction_id']) && $extra_vars['transaction_id'] ) {
                            $order_payments = OrderPayment::getByOrderReference($order->reference);
                            foreach ($order_payments as $p) {
                                if ($this->module->displayName == $p->payment_method) {
                                    $p->transaction_id = $extra_vars['transaction_id'];
                                    $p->update();
                                }
                            }
                        }
                    }
                    die($status->getCurrent());
                }
            }
            die (Tools::displayError('Unable to update status'));
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
        $amount = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $customer = new Customer($cart->id_customer);

        $secure_key = $customer->secure_key;
        if ( $secure_key != Context::getContext()->customer->secure_key ) {
            die($this->trans('Invalid Customer key.', [], 'Modules.E_nkap.Shop'));
        }

        Context::getContext()->currency = new Currency((int) Context::getContext()->cart->id_currency);
        Context::getContext()->language = new Language((int) Context::getContext()->customer->id_lang);
        
        $merchantReferenceId = $this->module->generateReferenceID();
        $dataData = [
            'merchantReference' => $merchantReferenceId,
            'email' => $customer->email,
            'customerName' => $customer->firstname . ' ' . $customer->lastname,
            'totalAmount' => $amount,
            'description' => sprintf('Payment from %s', $this->context->shop->name),
            'currency' => $this->module->getApiCurrency(),
            'langKey' => $this->module->getLanguageKey(Context::getContext()->customer->id_lang),
            'items' => []
        ];
        
        $cart_items = $this->context->cart->getProducts();
        foreach ($cart_items as $item) {
            $dataData['items'][] = [
                'itemId' => (int)$item['id_product'],
                'particulars' => $item['name'],
                'unitCost' => (float)$item['price'],
                'subTotal' => (float)$item['price'],
                'quantity' => $item['cart_quantity']
            ];
        }
        
        $_key = Configuration::get('E_NKAP_ACCOUNT_KEY');
        $_secret = Configuration::get('E_NKAP_ACCOUNT_SECRET');
        $isTestMode = !empty(Configuration::get('E_NKAP_LIVE_MODE'));
        try {
            $orderService = new OrderService($_key, $_secret, [], $isTestMode);
            $order = $orderService->loadModel(\Enkap\OAuth\Model\Order::class);
            $order->fromStringArray($dataData);
            $response = $orderService->place($order);
            
            $payment_status = (int)Configuration::get('PS_OS_E_NKAP');
            $module_name = $this->module->displayName;
            $message = 'E-Nkap order created #'.$response->getOrderTransactionId();
            $this->module->validateOrder($cart_id, $payment_status, $amount, $module_name, $message, array('transaction_id'=>$response->getOrderTransactionId()), (int) Context::getContext()->cart->id_currency, false, $secure_key);
            $this->logEnkapPayment($cart_id, (int)$this->module->currentOrder, $merchantReferenceId, $response->getOrderTransactionId(), $amount);
            Tools::redirect($response->getRedirectUrl());
        } catch (Throwable $e) {
            die(Tools::displayError('E-Nkap Payment Error: ' . $e->getMessage()));
        }
    }
    
    protected function logEnkapPayment(
        int $cartId, $orderId,
        string $merchantReferenceId,
        string $orderTransactionId,
        $amount = 0.0,
        $status = 'Pending'
    )
    {
        $e_nkap_payment = new ENkapPaymentCart((int)ENkapPaymentCart::getIdByIdCart($cartId));
        $e_nkap_payment->id_cart = $cartId;
        $e_nkap_payment->id_order = $orderId;
        $e_nkap_payment->merchant_reference_id = $merchantReferenceId;
        $e_nkap_payment->order_transaction_id = $orderTransactionId;
        $e_nkap_payment->order_total = $amount;
        $e_nkap_payment->status = $status;
        $e_nkap_payment->save();
    }
}
