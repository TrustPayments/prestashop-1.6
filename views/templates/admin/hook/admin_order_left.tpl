{*
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2022 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
*}
<div id="trustPaymentsTransactionInfo" class="card">
	<div class="card-header">
		<i class="icon-rocket"></i>
		Trust Payments {l s='Transaction Information' mod='trustpayments'}
	</div>
	<div class="card-body">
	<div class="trustpayments-transaction-data-column-container">
		<div class="trustpayments-transaction-column">
			<p>
				<strong>{l s='General Details' mod='trustpayments'}</strong>
			</p>
			<dl class="well list-detail">
				<dt>{l s='Payment Method' mod='trustpayments'}</dt>
				<dd>{$configurationName|escape:'html':'UTF-8'}
			{if !empty($methodImage)} 
			 	<br /><img
						src="{$methodImage|escape:'html'}"
						width="50" />
			{/if}
				</dd>
				<dt>{l s='Transaction State' mod='trustpayments'}</dt>
				<dd>{$transactionState|escape:'html':'UTF-8'}</dd>
			{if !empty($failureReason)} 
            	<dt>{l s='Failure Reason' mod='trustpayments'}</dt>
				<dd>{$failureReason|escape:'html':'UTF-8'}</dd>
			{/if}
        		<dt>{l s='Authorization Amount' mod='trustpayments'}</dt>
				<dd>{displayPrice price=$authorizationAmount}</dd>
				<dt>{l s='Transaction' mod='trustpayments'}</dt>
				<dd>
					<a href="{$transactionUrl|escape:'html'}" target="_blank">
						{l s='View' mod='trustpayments'}
					</a>
				</dd>
			</dl>
		</div>
		{if !empty($labelsByGroup)}
			{foreach from=$labelsByGroup item=group}
			<div class="trustpayments-transaction-column">
				<div class="trustpayments-payment-label-container" id="trustpayments-payment-label-container-{$group.id|escape:'html':'UTF-8'}">
					<p class="trustpayments-payment-label-group">
						<strong>
						{$group.translatedTitle|escape:'html':'UTF-8'}
						</strong>
					</p>
					<dl class="well list-detail">
						{foreach from=$group.labels item=label}
	                		<dt>{$label.translatedName|escape:'html':'UTF-8'}</dt>
							<dd>{$label.value|escape:'html':'UTF-8'}</dd>
						{/foreach}
					</dl>
				</div>
			</div>
			{/foreach}
		{/if}
	</div>
	{if !empty($completions)}
		<div class="trustpayments-transaction-data-column-container panel">
			<div class="panel-heading">
				<i class="icon-check"></i>
					Trust Payments {l s='Completions' mod='trustpayments'}
			</div>
			<div class="table-responsive">
				<table class="table" id="trustpayments_completion_table">
					<thead>
						<tr>
							<th>
								<span class="title_box ">{l s='Job Id' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Completion Id' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Status' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Error Message' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Links' mod='trustpayments'}</span>
							</th>
						</tr>
					</thead>
					<tbody>
					{foreach from=$completions item=completion}
						<tr>
							<td>{$completion->getId()|escape:'html':'UTF-8'}</td>
							<td>{if ($completion->getCompletionId() != 0)}
									{$completion->getCompletionId()|escape:'html':'UTF-8'}
								{else}
									{l s='Not available' mod='trustpayments'}
								{/if}	
							</td>
							<td>{$completion->getState()|escape:'html':'UTF-8'}</td>
							<td>{if !empty($completion->getFailureReason())}
									{assign var='failureReason' value="{trustpayments_translate text=$completion->getFailureReason()}"}
									{$failureReason|escape:'html':'UTF-8'}
								{else}
									{l s='(None)' mod='trustpayments'}
								{/if}
							</td>
							<td>
								{if ($completion->getCompletionId() != 0)}
									{assign var='completionUrl' value="{trustpayments_completion_url completion=$completion}"}
									<a href="{$completionUrl|escape:'html'}" target="_blank">
										{l s='View' mod='trustpayments'}
									</a>
								{else}
									{l s='Not available' mod='trustpayments'}
								{/if}	
							</td>
						</tr>
					{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	{/if}
		{if !empty($void)}
		<div class="trustpayments-transaction-data-column-container panel">
			<div class="panel-heading">
				<i class="icon-remove"></i>
					Trust Payments {l s='Voids' mod='trustpayments'}
			</div>
			<div class="table-responsive">
				<table class="table" id="trustpayments_void_table">
					<thead>
						<tr>
							<th>
								<span class="title_box ">{l s='Job Id' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Void Id' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Status' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Error Message' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Links' mod='trustpayments'}</span>
							</th>
						</tr>
					</thead>
					<tbody>
					{foreach from=$voids item=voidItem}
						<tr>
							<td>{$voidItem->getId()|escape:'html':'UTF-8'}</td>
							<td>{if ($voidItem->getVoidId() != 0)}
									{$voidItem->getVoidId()|escape:'html':'UTF-8'}
								{else}
									{l s='Not available' mod='trustpayments'}
								{/if}		
							</td>
							<td>{$voidItem->getState()|escape:'html':'UTF-8'}</td>
							<td>{if !empty($voidItem->getFailureReason())}
									{assign var='failureReason' value="{trustpayments_translate text=$voidItem->getFailureReason()}"}
									{$failureReason|escape:'html':'UTF-8'}
								{else}
									{l s='(None)' mod='trustpayments'}
								{/if}
							</td>
							<td>
								{if ($voidItem->getVoidId() != 0)}
									{assign var='voidUrl' value="{trustpayments_void_url void=$voidItem}"}
									<a href="{$voidUrl|escape:'html'}" target="_blank">
										{l s='View' mod='trustpayments'}
									</a>
								{else}
									{l s='Not available' mod='trustpayments'}
								{/if}	
							</td>
						</tr>
					{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	{/if}
		{if !empty($refunds)}
		<div class="trustpayments-transaction-data-column-container panel">
			<div class="panel-heading">
				<i class="icon-exchange"></i>
					Trust Payments {l s='Refunds' mod='trustpayments'}
			</div>
			<div class="table-responsive">
				<table class="table" id="trustpayments_refund_table">
					<thead>
						<tr>
							<th>
								<span class="title_box ">{l s='Job Id' mod='trustpayments'}</span>
							</th>
							
							<th>
								<span class="title_box ">{l s='External Id' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Refund Id' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Amount' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Type' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Status' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Error Message' mod='trustpayments'}</span>
							</th>
							<th>
								<span class="title_box ">{l s='Links' mod='trustpayments'}</span>
							</th>
						</tr>
					</thead>
					<tbody>
					{foreach from=$refunds item=refund}
						<tr>
							<td>{$refund->getId()|escape:'html':'UTF-8'}</td>
							<td>{$refund->getExternalId()|escape:'html':'UTF-8'}</td>
							<td>
								{if ($refund->getRefundId() != 0)}
									{$refund->getRefundId()|escape:'html':'UTF-8'}
								{else}
									{l s='Not available' mod='trustpayments'}
								{/if}	
							</td>
							<td>
								{assign var='refundAmount' value="{trustpayments_refund_amount refund=$refund}"}
								{displayPrice price=$refundAmount currency=$currency->id}
							</td>
							<td>
								{assign var='refundType' value="{trustpayments_refund_type refund=$refund}"}
								{$refundType|escape:'html':'UTF-8'}
							</td>
							<td>{$refund->getState()|escape:'html':'UTF-8'}</td>
							<td>{if !empty($refund->getFailureReason())}
									{assign var='failureReason' value="{trustpayments_translate text=$refund->getFailureReason()}"}
									{$failureReason|escape:'html':'UTF-8'}
								{else}
									{l s='(None)' mod='trustpayments'}
								{/if}
							</td>
							<td>
								{if ($refund->getRefundId() != 0)}
									{assign var='refundURl' value="{trustpayments_refund_url refund=$refund}"}
									<a href="{$refundURl|escape:'html'}" target="_blank">
										{l s='View' mod='trustpayments'}
									</a>
								{else}
									{l s='Not available' mod='trustpayments'}
								{/if}	
							</td>
						</tr>
					{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	{/if}
	</div>	

</div>