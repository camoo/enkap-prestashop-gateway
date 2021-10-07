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

<div class="panel">
	<div class="row e_nkap-header">
		<img src="{$module_dir|escape:'html':'UTF-8'}views/img/template_2_logo.png" class="col-xs-6 col-md-3 text-center" id="payment-logo" />
		<div class="col-xs-6 col-md-6 text-center text-muted">
			{l s='My Payment Module and PrestaShop have partnered to provide the easiest way for you to accurately calculate and file sales tax.' mod='e_nkap'}
		</div>
		<div class="col-xs-12 col-md-3 text-center">
			<a href="#" onclick="javascript:return false;" class="btn btn-primary" id="create-account-btn">{l s='Create an account' mod='e_nkap'}</a><br />
			{l s='Already have one?' mod='e_nkap'}<a href="#" onclick="javascript:return false;"> {l s='Log in' mod='e_nkap'}</a>
		</div>
	</div>

	<hr />
	
	<div class="e_nkap-content">
		<div class="row">
			<div class="col-md-5">
				<h5>{l s='Benefits of using my payment module' mod='e_nkap'}</h5>
				<ul class="ul-spaced">
					<li>
						<strong>{l s='It is fast and easy' mod='e_nkap'}:</strong>
						{l s='It is pre-integrated with PrestaShop, so you can configure it with a few clicks.' mod='e_nkap'}
					</li>
					
					<li>
						<strong>{l s='It is global' mod='e_nkap'}:</strong>
						{l s='Accept payments in XX currencies from XXX markets around the world.' mod='e_nkap'}
					</li>
					
					<li>
						<strong>{l s='It is trusted' mod='e_nkap'}:</strong>
						{l s='Industry-leading fraud an buyer protections keep you and your customers safe.' mod='e_nkap'}
					</li>
					
					<li>
						<strong>{l s='It is cost-effective' mod='e_nkap'}:</strong>
						{l s='There are no setup fees or long-term contracts. You only pay a low transaction fee.' mod='e_nkap'}
					</li>
				</ul>
			</div>
			
			<div class="col-md-2">
				<h5>{l s='Pricing' mod='e_nkap'}</h5>
				<dl class="list-unstyled">
					<dt>{l s='Payment Standard' mod='e_nkap'}</dt>
					<dd>{l s='No monthly fee' mod='e_nkap'}</dd>
					<dt>{l s='Payment Express' mod='e_nkap'}</dt>
					<dd>{l s='No monthly fee' mod='e_nkap'}</dd>
					<dt>{l s='Payment Pro' mod='e_nkap'}</dt>
					<dd>{l s='$5 per month' mod='e_nkap'}</dd>
				</dl>
				<a href="#" onclick="javascript:return false;">(Detailed pricing here)</a>
			</div>
			
			<div class="col-md-5">
				<h5>{l s='How does it work?' mod='e_nkap'}</h5>
				<iframe src="//player.vimeo.com/video/75405291" width="335" height="188" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
			</div>
		</div>

		<hr />
		
		<div class="row">
			<div class="col-md-12">
				<p class="text-muted">{l s='My Payment Module accepts more than 80 localized payment methods around the world' mod='e_nkap'}</p>
				
				<div class="row">
					<img src="{$module_dir|escape:'html':'UTF-8'}views/img/template_2_cards.png" class="col-md-3" id="payment-logo" />
					<div class="col-md-9 text-center">
						<h6>{l s='For more information, call 888-888-1234' mod='e_nkap'} {l s='or' mod='e_nkap'} <a href="mailto:contact@prestashop.com">contact@prestashop.com</a></h6>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="panel">
	<p class="text-muted">
		<i class="icon icon-info-circle"></i> {l s='In order to create a secure account with My Payment Module, please complete the fields in the settings panel below:' mod='e_nkap'}
		{l s='By clicking the "Save" button you are creating secure connection details to your store.' mod='e_nkap'}
		{l s='My Payment Module signup only begins when you client on "Activate your account" in the registration panel below.' mod='e_nkap'}
		{l s='If you already have an account you can create a new shop within your account.' mod='e_nkap'}
	</p>
	<p>
		<a href="#" onclick="javascript:return false;"><i class="icon icon-file"></i> Link to the documentation</a>
	</p>
</div>