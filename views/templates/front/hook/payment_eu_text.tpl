{*
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2021 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 *}
{$name|escape:'html':'UTF-8'}
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