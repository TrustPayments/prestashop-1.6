{*
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2022 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 *}
<div id="trustpayments_documents" style="display:none">
{if !empty($trustPaymentsInvoice)}
	<p class="trustpayments-document">
		<i class="icon-file-text-o"></i>
		<a target="_blank" href="{$trustPaymentsInvoice|escape:'html'}">{l s='Download your %s invoice as a PDF file.' sprintf='Trust Payments' mod='trustpayments'}</a>
	</p>
{/if}
{if !empty($trustPaymentsPackingSlip)}
	<p class="trustpayments-document">
		<i class="icon-truck"></i>
		<a target="_blank" href="{$trustPaymentsPackingSlip|escape:'html'}">{l s='Download your %s packing slip as a PDF file.' sprintf='Trust Payments' mod='trustpayments'}</a>
	</p>
{/if}
</div>
<script type="text/javascript">

jQuery(function($) {    
    $('#trustpayments_documents').find('p.trustpayments-document').each(function(key, element){
	
		$(".info-order.box").append(element);
    });
});

</script>