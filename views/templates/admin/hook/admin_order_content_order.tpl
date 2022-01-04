{*
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2022 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
*}
<div class="tab-pane" id="trustpayments_documents">
<h4 class="visible-print">Trust Payments {l s='Documents' mod='trustpayments'} <span class="badge">({$trustPaymentsDocumentsCount|escape:'html':'UTF-8'})</span></h4>

	<div class="table-responsive">
		<table class="table" id="trustpayments_documents_table">
			<tbody>
				{foreach from=$trustPaymentsDocuments item=document}
					<tr>
						<td><a class="_blank" href="{$document.url|escape:'html':'UTF-8'}"><i class="icon-{$document.icon} trustpayments-document"></i><span>{$document.name}<pan></a>
						</td>
					</tr>
				{foreachelse}
					<tr>
						<td colspan="1" class="list-empty">
							<div class="list-empty-msg">
								<i class="icon-warning-sign list-empty-icon"></i>
								{l s='There is no document availabe yet.' mod='trustpayments'}
							</div>
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>

</div>
