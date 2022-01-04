{*
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2022 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 *}
<h3>{l s='Your order on %s is complete.' sprintf=$shop_name mod='trustpayments'}</h3>
<div class="trustpayments_return">
	<br />{l s='Amount' mod='trustpayments'}: <span class="price"><strong>{$total|escape:'htmlall':'UTF-8'}</strong></span>
	<br />{l s='Order Reference' mod='trustpayments'}: <span class="reference"><strong>{$reference|escape:'html':'UTF-8'}</strong></span>
	<br /><br />{l s='An email has been sent with this information.' mod='trustpayments'}
	<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='trustpayments'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team.' mod='trustpayments'}</a>
</div>
