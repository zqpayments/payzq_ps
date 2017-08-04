{*
* 2017 PayZQ
*
*	@author PayZQ
*	@copyright	2017 PayZQ
*	@license		http://payzq.net/
*}

{if false}
	{if $refresh == 0}
	<div class="col-lg-2" style="float:right"><a class="close refresh"><i class="process-icon-refresh" style="font-size:1em"></i></a></div>
	<script>
        var validate = "{$path|escape:'htmlall':'UTF-8'}";
        var id_employee = "{$id_employee|escape:'htmlall':'UTF-8'}";
        var token_payzq = "{$token_payzq|escape:'htmlall':'UTF-8'}";
    </script>
		{/if}
{/if}

<div class="payzq-module-wrapper">
	<div class="payzq-module-header">
	   <span class="payzq-module-intro">{l s='PayZQ transactions'}</span>
	</div>
	<div class="row payzq-wraper-content">
		<table class="table">
			<tr>
				<th>{l s='Date (last update)' mod='payzq_ps'}</th>
			   	<th>{l s='Transaction ID' mod='payzq_ps'}</th>
			   	<th>{l s='Name' mod='payzq_ps'}</th>
		      <th>{l s='Card type' mod='payzq_ps'}</th>
			   	<th>{l s='Amount Paid' mod='payzq_ps'}</th>
			   	<th>{l s='Balance' mod='payzq_ps'}</th>
			   	<th>{l s='Result' mod='payzq_ps'}</th>
				<th>{l s='Mode' mod='payzq_ps'}</th>
			</tr>
			{foreach from=$tenta key=k item=v}
			<tr>
				<td>{$v.date|escape:'htmlall':'UTF-8'}</td>
				<td>{$v.id_transaction|escape:'htmlall':'UTF-8'}</td>
				<td>{$v.name|escape:'htmlall':'UTF-8'}</td>
				<td><img src="{$module_dir|escape:'htmlall':'UTF-8'}/views/img/cc-{$v.type|escape:'htmlall':'UTF-8'}.png" alt="card type" style="width:43px;"/></td>
				<td>{$v.amount|escape:'htmlall':'UTF-8'} {$v.currency|escape:'htmlall':'UTF-8'}</td>
				<td>{$v.refund|escape:'htmlall':'UTF-8'} {$v.currency|escape:'htmlall':'UTF-8'}</td>
				{if $v.result == 2}
					<td>Refund</td>
				{elseif $v.result == 3}
					<td>Partial Refund</td>
				{else}
					<td><img src="{$module_dir|escape:'htmlall':'UTF-8'}/views/img/{$v.result|escape:'htmlall':'UTF-8'}ok.gif" alt="result" /></td>
				{/if}
				<td class="uppercase">{$v.state|escape:'htmlall':'UTF-8'}</td>
			</tr>
			{/foreach}
		</table>
	</div>
</div>
