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

<div class="clearfix"></div>
<h3><i class="icon-info-sign"></i> {l s='Frequently Asked Questions' mod='payzq_official'}</h3>
 <div class="faq items">

	  <ul id="basics" class="faq-items">
        <li class="faq-item">
            <span class="faq-trigger">{l s='What are the required elements to use the module?' mod='payzq_official'}</span>
            <span class="expand pull-right">+</span>
            <div class="faq-content">
                <p>
                    {l s='To use this module and process credit card payments, you will need to have the following before going any further:' mod='payzq_official'}
                </p>

                <ul>
                    <li>
                        {l s='An installed SSL certificate. In order to get it, please contact your web hosting service or a SSL certificate provider.' mod='payzq_official'}
                    </li>

                    <li>
                        {l s='A PHP version >= 5.3.3 environment (PayZQ prerequisite). If you have an older PHP version, please ask your hosting provider to' mod='payzq_official'}
                        {l s='upgrade it to match the requirement.' mod='payzq_official'}
                    </li>
                </ul>
            </div>
        </li>

        <li class="faq-item">
            <span class="faq-trigger">{l s='How can I get PayZQ test secret and publishable keys for the connection tab?' mod='payzq_official'}</span>
            <span class="expand pull-right">+</span>
            <div class="faq-content">
                {l s='First, you need to create and administrate a PayZQ account. Then, you’ll find your API keys located in your account settings.' mod='payzq_official'}
            </div>
        </li>

        <li class="faq-item">
            <span class="faq-trigger">{l s='What is PayZQ pricing?' mod='payzq_official'}</span>
            <span class="expand pull-right">+</span>
            <div class="faq-content">
                <p>
                    {l s='For European companies, PayZQ charges (per successful transaction): ' mod='payzq_official'}<br>
                    - {l s='1.4% + €0.25/£0.20 with a European card ' mod='payzq_official'}<br>
                    - {l s='2.9% + €0.25/£0.20 with a non-European card' mod='payzq_official'}<br>
                    {l s='PayZQ has no setup fees, no monthly fees, no validation fees, no refund fees, and no card storage fees. There’s no additional fee for failed charges or refunds.' mod='payzq_official'}<br>
                </p>

                <p>
                    {l s='If you’d like to learn more about our simple pricing, please check our website: ' mod='payzq_official'}
                    <a href="http://PayZQ.com/pricing" target="_blank">http://PayZQ.com/pricing</a><br>
                    {l s='We offer customized pricing for larger businesses. If you accept more than €30,000 per month,' mod='payzq_official'}
                    <a target="_blank" href="https://PayZQ.com/contact/sales"> {l s='get in touch' mod='payzq_official'}</a>
                </p>
            </div>
        </li>

        <li class="faq-item">
            <span class="faq-trigger">{l s='What is the difference between Test and Live Mode?' mod='payzq_official'}</span>
            <span class="expand pull-right">+</span>
            <div class="faq-content">
                <p>
                    {l s='Every account is divided into two universes: one for testing, and one for running on your live website.' mod='payzq_official'}
                </p>

                <p>
                    {l s='In test mode, credit card transactions don\'t go through the actual credit card network — instead, they go through simple checks in' mod='payzq_official'}
                    {l s='PayZQ to validate that they look like they might be credit cards.' mod='payzq_official'}
                </p>

                <p>
                    {l s='In order to activate Live mode, you only need to click No in “Test mode” configuration.' mod='payzq_official'}
                </p>
            </div>
        </li>

        <li class="faq-item">
            <span class="faq-trigger">{l s='How can I make test payments using PayZQ payment gateway on my store?' mod='payzq_official'}</span>
            <span class="expand pull-right">+</span>
            <div class="faq-content">
                <p>
                    {l s='When the module is in test mode, you are able to click any of the credit card buttons (VISA, MasterCard, etc. logos) on the' mod='payzq_official'}
                    {l s='payment page to generate a sample credit card number for testing purposes.' mod='payzq_official'}
                </p>
            </div>
        </li>


	  </ul>
</div>
