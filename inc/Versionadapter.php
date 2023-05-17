<?php
/**
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2023 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

class TrustPaymentsVersionadapter
{
    public static function getConfigurationInterface()
    {
        return Adapter_ServiceLocator::get('Core_Business_ConfigurationInterface');
    }

    public static function getAddressFactory()
    {
        return Adapter_ServiceLocator::get('Adapter_AddressFactory');
    }

    public static function clearCartRuleStaticCache()
    {
    }

    public static function getAdminOrderTemplate()
    {
        return 'views/templates/admin/hook/admin_order.tpl';
    }
}
