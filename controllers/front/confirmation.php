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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class EnkapConfirmationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if ((Tools::isSubmit('order_ref') == false) || (Tools::isSubmit('status') === false)) {
            return false;
        }
        $merchant_reference_id = Tools::getValue('order_ref');
        $en_payment = ENkapPaymentCart::getByMerchantReference($merchant_reference_id);
        if (!empty($en_payment) && is_array($en_payment)) {
            $cart = new Cart((int)$en_payment['id_cart']);
            $customer = new Customer($cart->id_customer);
            Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int)$en_payment['id_cart'] .
                '&id_module=' . $this->module->id . '&id_order=' . (int)$en_payment['id_order'] .
                '&key=' . $customer->secure_key);
        } else {
            echo Tools::displayError('SmobilPay payment not found on local shop');
        }
        exit;
    }
}
