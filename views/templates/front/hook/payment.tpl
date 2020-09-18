{*
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2020 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 *}
<div class="row">
	<div class="col-xs-12">
		<div class="payment_module trustpayments-method">
			<div class="trustpayments {if empty($image)}no_logo{/if}" 
					{if !empty($image)} 
						style="background-image: url({$image|escape:'html'}); background-repeat: no-repeat; background-size: 64px; background-position:15px;"		
					{/if}
					onclick="document.getElementById('trustpayments-{$methodId|escape:'html':'UTF-8'}-link').click();" >
				<a class="trustpayments" id="trustpayments-{$methodId|escape:'html':'UTF-8'}-link" href="{$link|escape:'html'}" title="{$name}" >{$name}</a>
			{if !empty($description)}
				<span class="payment-method-description">{trustpayments_clean_html text=$description}</span>
			{/if}
			{if !empty($surchargeValues)}
				<span class="trustpayments-surcharge trustpayments-additional-amount"><span class="trustpayments-surcharge-text trustpayments-additional-amount-text">{l s='Minimum Sales Surcharge:' mod='trustpayments'}</span>
					<span class="trustpayments-surcharge-value trustpayments-additional-amount-value">
						{if $priceDisplay}
				          	{displayPrice price=$surchargeValues.surcharge_total} {if $display_tax_label}{l s='(tax excl.)' mod='trustpayments'}{/if}
				        {else}
				          	{displayPrice price=$surchargeValues.surcharge_total_wt} {if $display_tax_label}{l s='(tax incl.)' mod='trustpayments'}{/if}
				        {/if}
			       </span>
			   </span>
			{/if}
			{if !empty($feeValues)}
				<span class="trustpayments-payment-fee trustpayments-additional-amount"><span class="trustpayments-payment-fee-text trustpayments-additional-amount-text">{l s='Payment Fee:' mod='trustpayments'}</span>
					<span class="trustpayments-payment-fee-value trustpayments-additional-amount-value">
						{if $priceDisplay}
				          	{displayPrice price=$feeValues.fee_total} {if $display_tax_label}{l s='(tax excl.)' mod='trustpayments'}{/if}
				        {else}
				          	{displayPrice price=$feeValues.fee_total_wt} {if $display_tax_label}{l s='(tax incl.)' mod='trustpayments'}{/if}
				        {/if}
			       </span>
			   </span>
			{/if}
			</div>					
		</div>	
	</div>
</div>
