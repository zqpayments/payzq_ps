{*
* 2017 PayZQ
*
*	@author PayZQ
*	@copyright	2017 PayZQ
*	@license		http://payzq.net/
*}

<div class="row payzq-payment">
  <div class="col-xs-12">
    <div class="payment_module" style="border: 1px solid #d6d4d4; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; padding-left: 15px; padding-right: 15px; background: #fbfbfb;">
      <input type="hidden" id="payzq-incorrect_number" value="{l s='The card number is incorrect.' mod='payzq_ps'}">
      <input type="hidden" id="payzq-invalid_number" value="{l s='The card number is not a valid credit card number.' mod='payzq_ps'}">
      <input type="hidden" id="payzq-invalid_expiry_month" value="{l s='The card\'s expiration month is invalid.' mod='payzq_ps'}">
      <input type="hidden" id="payzq-invalid_expiry_year" value="{l s='The card\'s expiration year is invalid.' mod='payzq_ps'}">
      <input type="hidden" id="payzq-invalid_cvc" value="{l s='The card\'s security code is invalid.' mod='payzq_ps'}">
      <input type="hidden" id="payzq-expired_card" value="{l s='The card has expired.' mod='payzq_ps'}">
      <input type="hidden" id="payzq-incorrect_cvc" value="{l s='The card\'s security code is incorrect.' mod='payzq_ps'}">
      <input type="hidden" id="payzq-incorrect_zip" value="{l s='The card\'s zip code failed validation.' mod='payzq_ps'}">
      <input type="hidden" id="payzq-card_declined" value="{l s='The card was declined.' mod='payzq_ps'}">
      <input type="hidden" id="payzq-missing" value="{l s='There is no card on a customer that is being charged.' mod='payzq_ps'}">
      <input type="hidden" id="payzq-processing_error" value="{l s='An error occurred while processing the car.' mod='payzq_ps'}">
      <input type="hidden" id="payzq-rate_limit" value="{l s='An error occurred due to requests hitting the API too quickly. Please let us know if you\'re consistently running into this error.' mod='payzq_ps'}">
      <input type="hidden" id="payzq-3d_declined" value="{l s='The card doesn\'t support 3DS.' mod='payzq_ps'}">
      <input type="hidden" id="payzq-no_api_key" value="{l s='There\'s an error with your API keys. If you\'re the administrator of this website, please go on the "Connection" tab of your plugin.' mod='payzq_ps'}">
      <div id="payzq-ajax-loader"><img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/ajax-loader.gif" alt="" /> {l s='Transaction in progress, please wait.' mod='payzq_ps'}</div>
      <form action="#" id="payzq-payment-form">
        {* Classic Credit card form *}
        <div class="payzq-payment-errors">{if isset($smarty.get.payzq_error)}{$smarty.get.payzq_error|escape:'htmlall':'UTF-8'}{/if}</div>
        <a name="payzq_error" style="display:none"></a>
        <input type="hidden" id="payzq-publishable-key" value="{$publishableKey|escape:'htmlall':'UTF-8'}"/>
        <div>
          <label>{l s='Cardholder\'s Name' mod='payzq_ps'}</label>  <label class="required"> </label><br />
          <input type="text"  autocomplete="off" class="payzq-cardname" data-payzq="name" value="{$customer_name|escape:'htmlall':'UTF-8'}"/>
        </div>
        <div>
          <label>{l s='Card Number ' mod='payzq_ps'}</label>  <label class="required"> </label><br />
          <input type="text" size="20" autocomplete="off" class="payzq-card-number" id="card_number" data-payzq="number" placeholder="&#9679;&#9679;&#9679;&#9679; &#9679;&#9679;&#9679;&#9679; &#9679;&#9679;&#9679;&#9679; &#9679;&#9679;&#9679;&#9679;"/>
        </div>
        <div class="">
          <label>{l s='Expiry date' mod='payzq_ps'}</label>  <label class="required"> </label><br />
          <input type="text" size="7" autocomplete="off" id="card_expiry" class="payzq-card-expiry" maxlength = 5 placeholder="MM/YY"/>
        </div>
        <div>
          <label>{l s='CVC/CVV' mod='payzq_ps'}</label>  <label class="required"> </label><br />
          <input type="text" size="7" autocomplete="off" data-payzq="cvc" class="payzq-card-cvc" placeholder="&#9679;&#9679;&#9679;"/>
          <a href="javascript:void(0)" class="payzq-card-cvc-info" style="border: none;">
            <div class="cvc-info">
              {l s='The CVC (Card Validation Code) is a 3 or 4 digit code on the reverse side of Visa, MasterCard and Discover cards and on the front of American Express cards.' mod='payzq_ps'}
            </div>
          </a>
        </div>
        <div class="clear"></div>
        <img class="powered_payzq" alt="" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/verified_by_visa.png"/>
        <img class="powered_payzq" alt="" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/mastercard_securecode.png"/>
        <img class="powered_payzq" alt="" src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/powered.png"/>
      </form>
    </div>
  </div>
</div>
<div id="modal_payzq"  class="modal" style="display: none">
  <div id="result_3d"> </div></div>
<script type="text/javascript">
  var mode = {$payzq_mode|escape:'htmlall':'UTF-8'};
  var currency = "{$currency|escape:'htmlall':'UTF-8'}";
  var amount_ttl = {$amount_ttl|escape:'htmlall':'UTF-8'};
  var secure_mode = {$secure_mode|escape:'htmlall':'UTF-8'};
  var baseDir = "{$baseDir|escape:'htmlall':'UTF-8'}";
  var billing_address = {$billing_address|escape nofilter};
  var module_dir = "{$module_dir|escape:'htmlall':'UTF-8'}";
  {literal}

</script>
{/literal}
