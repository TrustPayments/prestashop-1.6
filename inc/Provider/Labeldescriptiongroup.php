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
 * Provider of label descriptor group information from the gateway.
 */
class TrustPaymentsProviderLabeldescriptiongroup extends TrustPaymentsProviderAbstract
{
    protected function __construct()
    {
        parent::__construct('trustpayments_label_description_group');
    }

    /**
     * Returns the label descriptor group by the given code.
     *
     * @param int $id
     * @return \TrustPayments\Sdk\Model\LabelDescriptorGroup
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Returns a list of label descriptor groups.
     *
     * @return \TrustPayments\Sdk\Model\LabelDescriptorGroup[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $labelDescriptorGroupService = new \TrustPayments\Sdk\Service\LabelDescriptionGroupService(
            TrustPaymentsHelper::getApiClient()
        );
        return $labelDescriptorGroupService->all();
    }

    protected function getId($entry)
    {
        /* @var \TrustPayments\Sdk\Model\LabelDescriptorGroup $entry */
        return $entry->getId();
    }
}
