<?php
/**
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2020 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

/**
 * Provider of currency information from the gateway.
 */
class TrustPaymentsProviderCurrency extends TrustPaymentsProviderAbstract
{
    protected function __construct()
    {
        parent::__construct('trustpayments_currencies');
    }

    /**
     * Returns the currency by the given code.
     *
     * @param string $code
     * @return \TrustPayments\Sdk\Model\RestCurrency
     */
    public function find($code)
    {
        return parent::find($code);
    }

    /**
     * Returns a list of currencies.
     *
     * @return \TrustPayments\Sdk\Model\RestCurrency[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $currencyService = new \TrustPayments\Sdk\Service\CurrencyService(
            TrustPaymentsHelper::getApiClient()
        );
        return $currencyService->all();
    }

    protected function getId($entry)
    {
        /* @var \TrustPayments\Sdk\Model\RestCurrency $entry */
        return $entry->getCurrencyCode();
    }
}
