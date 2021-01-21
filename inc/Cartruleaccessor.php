<?php
/**
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2021 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

/**
 * This class provides function to download documents from Trust Payments
 */
class TrustPaymentsCartruleaccessor extends CartRule
{
    public static function checkProductRestrictionsStatic(CartRule $cartRule, Cart $cart)
    {
        $context = Context::getContext()->cloneContext();
        $context->cart = $cart;
        return $cartRule->checkProductRestrictions($context, true);
    }
}
