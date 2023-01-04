{*
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2023 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 *}
<div id="trustpayments_notifications" style="display:none";>
	<li id="trustpayments_manual_notifs" class="dropdown" data-type="trustpayments_manual_messages">	
		<a href="javascript:void(0);" class="dropdown-toggle notifs" data-toggle="dropdown">
			<i class="icon-bullhorn"></i>
				{if $manualTotal > 0}
					<span id="trustpayments_manual_messages_notif_number_wrapper" class="notifs_badge">
						<span id="trustpayments_manual_messages_notif_value">{$manualTotal|escape:'html':'UTF-8'}</span>
					</span>
				{/if}
		</a>
		<div class="dropdown-menu notifs_dropdown">
			<section id="trustpayments_manual_messages_notif_number_wrapper" class="notifs_panel">
				<div class="notifs_panel_header">
					<h3>Manual Tasks</h3>
				</div>
				<div id="list_trustpayments_manual_messages_notif" class="list_notif">
					{if $manualTotal > 0}
					<a href="{$manualUrl|escape:'html'}" target="_blank">
						<p>{if $manualTotal > 1}
							{l s='There are %s manual tasks that need your attention.' sprintf=$manualTotal mod='trustpayments'}
						{else}
							{l s='There is a manual task that needs your attention.' mod='trustpayments'}
						{/if}
						</p>
					</a>
					{else}
						<span class="no_notifs">
						{l s='There are no manual tasks.' mod='trustpayments'}
						</span>
					{/if}
				</div>
			</section>
		</div>
	</li>
</div>