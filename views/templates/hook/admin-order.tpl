{*
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
*}
<div class="panel card mt-2" id="e_nkap_panel">
    <div class="panel-heading card-header">
        <h3>{l s='SmobilPay payment status' d='Modules.E_nkap.Shop'}</h3>
    </div>
    <div class="card-body">
        <p>
            <span>{l s='Merchant ID' d='Modules.E_nkap.Shop'}: <strong>{$en_payment.merchant_reference_id|escape:'html':'UTF-8'}</strong></span><br>
            <span>{l s='Payment transaction ID' d='Modules.E_nkap.Shop'}: <strong>{$en_payment.order_transaction_id|escape:'html':'UTF-8'}</strong></span><br>
            <span>{l s='Payment status' d='Modules.E_nkap.Shop'}: <strong>{if empty($en_payment.status)}{l s='Pending' d='Modules.E_nkap.Shop'}{else}{$en_payment.status|escape:'html':'UTF-8'}{/if}</strong></span>
        </p>
        {if {$en_payment.status}|in_array:['PENDING', 'IN_PROGRESS'] || empty($en_payment.status)}
            <p>
                <a class="btn btn-primary" href="{$link|escape:'htmlall':'UTF-8'}">{l s='Check Payment status' d='Modules.E_nkap.Shop'}</a>
            </p>
        {/if}

    </div>
</div>
