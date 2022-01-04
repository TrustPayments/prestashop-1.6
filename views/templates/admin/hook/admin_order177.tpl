{*
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2022 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
*}
{if (isset($showAuthorizedActions) && $showAuthorizedActions)}
	<div style="display:none;" class="hidden-print">
		<a class="btn btn-action trustpayments-management-btn"  id="trustpayments_void">
			<i class="icon-remove"></i>
			{l s='Void' mod='trustpayments'}
		</a>
		<a class="btn btn-action trustpayments-management-btn"  id="trustpayments_completion">
			<i class="icon-check"></i>
			{l s='Completion' mod='trustpayments'}
		</a>	
	</div>

	<script type="text/javascript">
		var trustpayments_void_title = "{l s='Are you sure?' mod='trustpayments' js=1}";
		var trustpayments_void_btn_confirm_txt = "{l s='Void Order' mod='trustpayments' js=1}";
		var trustpayments_void_btn_deny_txt = "{l s='No' mod='trustpayments' js=1}";

		var trustpayments_completion_title = "{l s='Are you sure?' mod='trustpayments' js=1}";
		var trustpayments_completion_btn_confirm_txt = "{l s='Complete Order'  mod='trustpayments' js=1}";
		var trustpayments_completion_btn_deny_txt = "{l s='No' mod='trustpayments' js=1}";

		var trustpayments_msg_general_error = "{l s='The server experienced an unexpected error, please try again.'  mod='trustpayments' js=1}";
		var trustpayments_msg_general_title_succes = "{l s='Success'  mod='trustpayments' js=1}";
		var trustpayments_msg_general_title_error = "{l s='Error'  mod='trustpayments' js=1}";
		var trustpayments_btn_info_confirm_txt = "{l s='OK'  mod='trustpayments' js=1}";
	</script>
	
	<div id="trustpayments_void_msg" class="hidden-print" style="display:none">
		{if !empty($affectedOrders)}
			{l s='This will also void the following orders:' mod='trustpayments' js=1}
			<ul>
				{foreach from=$affectedOrders item=other}
					<li>
						<a href="{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}&amp;vieworder&amp;id_order={$other|intval}">
							{l s='Order %d' sprintf=$other mod='trustpayments' js=1}
						</a>
					</li>
				{/foreach}
			</ul>
			{l s='If you only want to void this order, we recommend to remove all products from this order.' mod='trustpayments' js=1}
		{else}
			{l s='This action cannot be undone.' mod='trustpayments' js=1}
		{/if}
	</div>
	
	<div id="trustpayments_completion_msg" class="hidden-print" style="display:none">
		{if !empty($affectedOrders)}
			{l s='This will also complete the following orders:' mod='trustpayments'}
			<ul>
				{foreach from=$affectedOrders item=other}
					<li>
						<a href="{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}&amp;vieworder&amp;id_order={$other|intval}">
								{l s='Order %d' sprintf=$other mod='trustpayments'}
						</a>
					</li>
				{/foreach}
			</ul>
		{else}
			{l s='This finalizes the order, it no longer can be changed.' mod='trustpayments'}			
		{/if}		
	</div>
{/if}
  
{if (isset($showUpdateActions) && $showUpdateActions)}
<div style="display:none;" class="hidden-print">
	<a class="btn btn-default trustpayments-management-btn" id="trustpayments_update">
		<i class="icon-refresh"></i>
		{l s='Update' mod='trustpayments'}
	</a>
</div>
{/if}


{if isset($isTrustPaymentsTransaction)}
<div style="display:none;" class="hidden-print" id="trustpayments_is_transaction"></div>
{/if}

{if isset($editButtons)}
<div style="display:none;" class="hidden-print" id="trustpayments_remove_edit"></div>
{/if}

{if isset($cancelButtons)}
<div style="display:none;" class="hidden-print" id="trustpayments_remove_cancel"></div>
{/if}

{if isset($refundChanges)}
<div style="display:none;" class="hidden-print" id="trustpayments_changes_refund">
<p id="trustpayments_refund_online_text_total">{l s='This refund is sent to %s and money is transfered back to the customer.' sprintf='Trust Payments' mod='trustpayments'}</p>
<p id="trustpayments_refund_offline_text_total" style="display:none;">{l s='This refund is sent to %s, but [1]no[/1] money is transfered back to the customer.' tags=['<b>'] sprintf='Trust Payments' mod='trustpayments'}</p>
<p id="trustpayments_refund_no_text_total" style="display:none;">{l s='This refund is [1]not[/1] sent to %s.' tags=['<b>'] sprintf='Trust Payments' mod='trustpayments'}</p>
<p id="trustpayments_refund_offline_span_total" class="checkbox" style="display: none;">
	<label for="trustpayments_refund_offline_cb_total">
		<input type="checkbox" id="trustpayments_refund_offline_cb_total" name="trustpayments_offline">
		{l s='Send as offline refund to %s.' sprintf='Trust Payments' mod='trustpayments'}
	</label>
</p>

<p id="trustpayments_refund_online_text_partial">{l s='This refund is sent to %s and money is transfered back to the customer.' sprintf='Trust Payments' mod='trustpayments'}</p>
<p id="trustpayments_refund_offline_text_partial" style="display:none;">{l s='This refund is sent to %s, but [1]no[/1] money is transfered back to the customer.' tags=['<b>'] sprintf='Trust Payments' mod='trustpayments'}</p>
<p id="trustpayments_refund_no_text_partial" style="display:none;">{l s='This refund is [1]not[/1] sent to %s.' tags=['<b>'] sprintf='Trust Payments' mod='trustpayments'}</p>
<p id="trustpayments_refund_offline_span_partial" class="checkbox" style="display: none;">
	<label for="trustpayments_refund_offline_cb_partial">
		<input type="checkbox" id="trustpayments_refund_offline_cb_partial" name="trustpayments_offline">
		{l s='Send as offline refund to %s.' sprintf='Trust Payments' mod='trustpayments'}
	</label>
</p>
</div>
{/if}

{if isset($completionPending)}
<div style="display:none;" class="hidden-print" id="trustpayments_completion_pending">
	<span class="span label label-inactive trustpayments-management-info">
		<i class="icon-refresh"></i>
		{l s='Completion in Process' mod='trustpayments'}
	</span>
</div>
{/if}

{if isset($voidPending)}
<div style="display:none;" class="hidden-print" id="trustpayments_void_pending">
	<span class="span label label-inactive trustpayments-management-info">
		<i class="icon-refresh"></i>
		{l s='Void in Process' mod='trustpayments'}
	</span>

</div>
{/if}

{if isset($refundPending)}
<div style="display:none;" class="hidden-print" id="trustpayments_refund_pending">
	<span class="span label label-inactive trustpayments-management-info">
		<i class="icon-refresh"></i>
		{l s='Refund in Process' mod='trustpayments'}
	</span>
</div>
{/if}


<script type="text/javascript">
	var isVersionGTE177 = true;
{if isset($voidUrl)}
	var trustPaymentsVoidUrl = "{$voidUrl|escape:'javascript':'UTF-8'}";
{/if}
{if isset($completionUrl)}
	var trustPaymentsCompletionUrl = "{$completionUrl|escape:'javascript':'UTF-8'}";
{/if}
{if isset($updateUrl)}
	var trustPaymentsUpdateUrl = "{$updateUrl|escape:'javascript':'UTF-8'}";
{/if}

</script>