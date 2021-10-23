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

{if (isset($status) == true) && ($status == 'ok')}
<h3>{l s='Your order on %s is complete.' sprintf=[$shop_name] d='Modules.E_nkap.Shop'}</h3>
<p class="alert alert-success">
    <strong>{l s='Your SmobilPay payment is saved and waiting for our partner confirmation' d='Modules.E_nkap.Shop'}</strong>
</p>
<p>
    <dl>
        <dt>{l s='Amount' d='Modules.E_nkap.Shop'}</dt>
        <dd><span class="price"><strong>{$total|escape:'htmlall':'UTF-8'}</strong></span></dd>
        <dt>{l s='Reference' d='Modules.E_nkap.Shop'}</dt>
        <dd><span class="reference"><strong>{$reference|escape:'html':'UTF-8'}</strong></span></dd>
    </dl>
    {l s='An email has been sent with this information.' d='Modules.E_nkap.Shop'}
    <br /><br />{l s='If you have questions, comments or concerns, please contact our' d='Modules.E_nkap.Shop'} <a href="{$urls.pages.contact}">{l s='expert customer support team.' d='Modules.E_nkap.Shop'}</a>
</p>
{else}
<h3>{l s='Your order on %s has not been accepted.' sprintf=[$shop_name] d='Modules.E_nkap.Shop'}</h3>
<p>
	<br />- {l s='Reference' d='Modules.E_nkap.Shop'} <span class="reference"> <strong>{$reference|escape:'html':'UTF-8'}</strong></span>
	<br /><br />{l s='Please, try to order again.' d='Modules.E_nkap.Shop'}
	<br /><br />{l s='If you have questions, comments or concerns, please contact our' d='Modules.E_nkap.Shop'} <a href="{$urls.pages.contact}">{l s='expert customer support team.' d='Modules.E_nkap.Shop'}</a>
</p>
{/if}
<hr />
