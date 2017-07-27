{*
* 2007-2017 PrestaShop
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
*	@author PrestaShop SA <contact@prestashop.com>
*	@copyright	2007-2017 PrestaShop SA
*	@license		http://opensource.org/licenses/afl-3.0.php	Academic Free License (AFL 3.0)
*	International Registered Trademark & Property of PrestaShop SA
*}

<div class="payzq-module-wrapper">
	<div class="payzq-module-header">
	   <span class="payzq-module-intro">{l s='Improve your conversion rate and securely charge your customers with PayZQ, the easiest payment platform' mod='payzq_official'}</span>
	</div>
	<div class="payzq-module-wrap">
		 <div class="payzq-module-col1 floatRight"></div>
		 <div class="payzq-module-col2">
		 	<div class="payzq-module-col1inner">
			 	- <span><a href="https://partners-subscribe.prestashop.com/PayZQ/connect.php?params[return_url]={$return_url|escape:'htmlall':'UTF-8'}" rel="external" target="_blank">{l s='Create your PayZQ account in 10 minutes' mod='payzq_official'}</a> </span>
				{l s='and immediately start accepting payments via Visa, MasterCard and American Express (no additional contract/merchant ID needed from your bank)' mod='payzq_official'}.<br>
				<div class="connect_btn">
					<a href="https://partners-subscribe.prestashop.com/PayZQ/connect.php?params[return_url]={$return_url|escape:'htmlall':'UTF-8'}" class="payzq-connect">
						<span>{l s='Connect with PayZQ' mod='payzq_official'}</span>
					</a>
				</div>
				- <span>{l s='Improve your conversion rate' mod='payzq_official'} </span>
				{l s='by offering a seamless payment experience to your customers: PayZQ lets you host the payment form on your own pages, without redirection to a bank third-part page.' mod='payzq_official'}<br>
				- <span>{l s='Keep your fraud under control' mod='payzq_official'}</span> {l s='thanks to customizable 3D-Secure and' mod='payzq_official'}
				<a target="_blank" href="https://PayZQ.com/radar">{l s='PayZQ Radar' mod='payzq_official'}</a>{l s=', our suite of anti-fraud tools.' mod='payzq_official'}<br>
				- <span>{l s='Easily refund ' mod='payzq_official'}</span>
				{l s='your orders through your PrestaShop’s back-office (and automatically update your PrestaShop order status).' mod='payzq_official'}<br>
				- {l s='Start selling abroad by offering payments in ' mod='payzq_official'}
				<span>{l s='135+ currencies.' mod='payzq_official'}</span><br><br>
				<img src="{$module_dir|escape:'htmlall':'UTF-8'}/views/img/started.png" style="width:100%;">
				<br><br>
				<p>{l s='Find out more about PayZQ on our website: ' mod='payzq_official'}
				<a target="_blank" href="https://PayZQ.com/fr">www.payzq.com</a></p>
				<br>
				<p><span>{l s='How much does PayZQ cost?' mod='payzq_official'}</span></p>
				<p>
					{l s='For European companies, PayZQ charges (per successful transaction):' mod='payzq_official'}<br>
					{l s='- 1.4% + €0.25/£0.20 with a European card' mod='payzq_official'}<br>
					{l s='- 2.9% + €0.25/£0.20 with a non-European card' mod='payzq_official'}<br>
					{l s='PayZQ has no setup fees, no monthly fees, no validation fees, no refund fees, and no card storage fees. ' mod='payzq_official'}<br>
					{l s='There’s no additional fee for failed charges or refunds.' mod='payzq_official'}
				</p><br>
				<p>{l s='If you’d like to learn more about our simple pricing, please check our website: ' mod='payzq_official'}
				<a target="_blank" href="https://www.PayZQ.com/pricing">www.payzq.com/pricing</a></p><br>
				<p>{l s='We offer customized pricing for larger businesses. If you accept more than €30,000 per month,' mod='payzq_official'}
					<a target="_blank" href="https://PayZQ.com/contact/sales"> {l s='get in touch' mod='payzq_official'}</a>.</p>
				<div class="connect_btn">
					<a href="https://partners-subscribe.prestashop.com/PayZQ/connect.php?params[return_url]={$return_url|escape:'htmlall':'UTF-8'}" class="payzq-connect">
						<span>{l s='Connect with PayZQ' mod='payzq_official'}</span>
					</a>
				</div>
			</div>


			<!--<div class="payzq-module-col2inner">
				<h3>{l s='Accept payments worldwide using all major credit cards' mod='payzq_official'}</h3>
				<p><img src="{$module_dir|escape:'htmlall':'UTF-8'}/views/img/payzq-cc.png" alt="PayZQ" class="payzq-cc" /><a href="https://PayZQ.com/signup" class="payzq-module-btn" target="_blank">
				<strong>{l s='Create a FREE Account!' mod='payzq_ps'}</strong></a></p>
			</div>-->
		</div>
	</div>
</div>
