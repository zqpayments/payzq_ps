{*
* 2017 PayZQ
*
*	@author PayZQ
*	@copyright	2017 PayZQ
*	@license		http://payzq.net/
*}
<div class="tabs">

  <div class="sidebar navigation col-md-2">
	{if isset($tab_contents.logo)}
	  <img class="tabs-logo" src="{$tab_contents.logo|escape:'htmlall':'UTF-8'}"/>
	{/if}
	<nav class="list-group categorieList">
	  {foreach from=$tab_contents.contents key=tab_nbr item=content}
		<a class="list-group-item migration-tab"
		   href="#payzq_step_{$tab_nbr + 1|intval}">

		  {if isset($content.icon) && $content.icon != false}
			<i class="{$content.icon|escape:"htmlall":"UTF-8"} pstab-icon"></i>
		  {/if}

		  {$content.name|escape:"htmlall":"UTF-8"}

		  {if isset($content.badge) && $content.badge != false}
			<span class="badge-module-tabs pull-right {$content.badge|escape:"htmlall":"UTF-8"}"></span>
		  {/if}

		</a>
	  {/foreach}
	</nav>
  </div>

  <div class="col-md-10">
	<div class="content-wrap panel">
	  {foreach from=$tab_contents.contents key=tab_nbr item=content}
		<section id="section-shape-{$tab_nbr + 1|intval}">{$content.value|escape:"UTF-8"}</section>
		  <!--html code generated-->
	  {/foreach}
	</div>
  </div>

</div>
<script type="text/javascript">
	var payzq_test_mode = "{l s='test' mod='payzq_official'}";
	var live = "{l s='live' mod='payzq_official'}";
  var conf_refund_1 = "{l s='From here you can refund all or part of a payment you have received. You will not be able to refund an amount greater than the amount of the transaction.' mod='payzq_official'}"

  var conf_mode_description1 = "{l s='Once you have registered with payzq.net, copy your company ID and password to the API, so that you can start making transactions.' mod='payzq_official'}";
  var conf_mode_description2 = "{l s='You can do test transactions whenever you indicate the mode of use in "test". Once all goes well, be sure to dial the "live" mode and enter the corresponding API access key.' mod='payzq_official'}";
  var conf_mode_description3 = "{l s='Find your credentials from the administrative panel of' mod='payzq_official'}";
</script>
<script type="text/javascript" src="{$new_base_dir|escape:'htmlall':'UTF-8'}views/js/PSTabs.js"></script>
<script type="text/javascript">
		(function() {
		[].slice.call(document.querySelectorAll('.tabs')).forEach(function(el) {
			new PSTabs(el);
		});
	})();
</script>
