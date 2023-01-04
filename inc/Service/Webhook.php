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

/**
 * This service handles webhooks.
 */
class TrustPaymentsServiceWebhook extends TrustPaymentsServiceAbstract
{

    /**
     * The webhook listener API service.
     *
     * @var \TrustPayments\Sdk\Service\WebhookListenerService
     */
    private $webhookListenerService;

    /**
     * The webhook url API service.
     *
     * @var \TrustPayments\Sdk\Service\WebhookUrlService
     */
    private $webhookUrlService;

    private $webhookEntities = array();

    /**
     * Constructor to register the webhook entites.
     */
    public function __construct()
    {
        $this->webhookEntities[1487165678181] = new TrustPaymentsWebhookEntity(
            1487165678181,
            'Manual Task',
            array(
                \TrustPayments\Sdk\Model\ManualTaskState::DONE,
                \TrustPayments\Sdk\Model\ManualTaskState::EXPIRED,
                \TrustPayments\Sdk\Model\ManualTaskState::OPEN
            ),
            'TrustPaymentsWebhookManualtask'
        );
        $this->webhookEntities[1472041857405] = new TrustPaymentsWebhookEntity(
            1472041857405,
            'Payment Method Configuration',
            array(
                \TrustPayments\Sdk\Model\CreationEntityState::ACTIVE,
                \TrustPayments\Sdk\Model\CreationEntityState::DELETED,
                \TrustPayments\Sdk\Model\CreationEntityState::DELETING,
                \TrustPayments\Sdk\Model\CreationEntityState::INACTIVE
            ),
            'TrustPaymentsWebhookMethodconfiguration',
            true
        );
        $this->webhookEntities[1472041829003] = new TrustPaymentsWebhookEntity(
            1472041829003,
            'Transaction',
            array(
                \TrustPayments\Sdk\Model\TransactionState::AUTHORIZED,
                \TrustPayments\Sdk\Model\TransactionState::DECLINE,
                \TrustPayments\Sdk\Model\TransactionState::FAILED,
                \TrustPayments\Sdk\Model\TransactionState::FULFILL,
                \TrustPayments\Sdk\Model\TransactionState::VOIDED,
                \TrustPayments\Sdk\Model\TransactionState::COMPLETED
            ),
            'TrustPaymentsWebhookTransaction'
        );
        $this->webhookEntities[1472041819799] = new TrustPaymentsWebhookEntity(
            1472041819799,
            'Delivery Indication',
            array(
                \TrustPayments\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED
            ),
            'TrustPaymentsWebhookDeliveryindication'
        );

        $this->webhookEntities[1472041831364] = new TrustPaymentsWebhookEntity(
            1472041831364,
            'Transaction Completion',
            array(
                \TrustPayments\Sdk\Model\TransactionCompletionState::FAILED,
                \TrustPayments\Sdk\Model\TransactionCompletionState::SUCCESSFUL
            ),
            'TrustPaymentsWebhookTransactioncompletion'
        );

        $this->webhookEntities[1472041867364] = new TrustPaymentsWebhookEntity(
            1472041867364,
            'Transaction Void',
            array(
                \TrustPayments\Sdk\Model\TransactionVoidState::FAILED,
                \TrustPayments\Sdk\Model\TransactionVoidState::SUCCESSFUL
            ),
            'TrustPaymentsWebhookTransactionvoid'
        );

        $this->webhookEntities[1472041839405] = new TrustPaymentsWebhookEntity(
            1472041839405,
            'Refund',
            array(
                \TrustPayments\Sdk\Model\RefundState::FAILED,
                \TrustPayments\Sdk\Model\RefundState::SUCCESSFUL
            ),
            'TrustPaymentsWebhookRefund'
        );
        $this->webhookEntities[1472041806455] = new TrustPaymentsWebhookEntity(
            1472041806455,
            'Token',
            array(
                \TrustPayments\Sdk\Model\CreationEntityState::ACTIVE,
                \TrustPayments\Sdk\Model\CreationEntityState::DELETED,
                \TrustPayments\Sdk\Model\CreationEntityState::DELETING,
                \TrustPayments\Sdk\Model\CreationEntityState::INACTIVE
            ),
            'TrustPaymentsWebhookToken'
        );
        $this->webhookEntities[1472041811051] = new TrustPaymentsWebhookEntity(
            1472041811051,
            'Token Version',
            array(
                \TrustPayments\Sdk\Model\TokenVersionState::ACTIVE,
                \TrustPayments\Sdk\Model\TokenVersionState::OBSOLETE
            ),
            'TrustPaymentsWebhookTokenversion'
        );
    }

    /**
     * Installs the necessary webhooks in Trust Payments.
     */
    public function install()
    {
        $spaceIds = array();
        foreach (Shop::getShops(true, null, true) as $shopId) {
            $spaceId = Configuration::get(TrustPaymentsBasemodule::CK_SPACE_ID, null, null, $shopId);
            if ($spaceId && ! in_array($spaceId, $spaceIds)) {
                $webhookUrl = $this->getWebhookUrl($spaceId);
                if ($webhookUrl == null) {
                    $webhookUrl = $this->createWebhookUrl($spaceId);
                }
                $existingListeners = $this->getWebhookListeners($spaceId, $webhookUrl);
                foreach ($this->webhookEntities as $webhookEntity) {
                    /* @var TrustPaymentsWebhookEntity $webhookEntity */
                    $exists = false;
                    foreach ($existingListeners as $existingListener) {
                        if ($existingListener->getEntity() == $webhookEntity->getId()) {
                            $exists = true;
                        }
                    }
                    if (! $exists) {
                        $this->createWebhookListener($webhookEntity, $spaceId, $webhookUrl);
                    }
                }
                $spaceIds[] = $spaceId;
            }
        }
    }

    /**
     *
     * @param int|string $id
     * @return TrustPaymentsWebhookEntity
     */
    public function getWebhookEntityForId($id)
    {
        if (isset($this->webhookEntities[$id])) {
            return $this->webhookEntities[$id];
        }
        return null;
    }

    /**
     * Create a webhook listener.
     *
     * @param TrustPaymentsWebhookEntity $entity
     * @param int $spaceId
     * @param \TrustPayments\Sdk\Model\WebhookUrl $webhookUrl
     * @return \TrustPayments\Sdk\Model\WebhookListenerCreate
     */
    protected function createWebhookListener(
        TrustPaymentsWebhookEntity $entity,
        $spaceId,
        \TrustPayments\Sdk\Model\WebhookUrl $webhookUrl
    ) {
        $webhookListener = new \TrustPayments\Sdk\Model\WebhookListenerCreate();
        $webhookListener->setEntity($entity->getId());
        $webhookListener->setEntityStates($entity->getStates());
        $webhookListener->setName('Prestashop ' . $entity->getName());
        $webhookListener->setState(\TrustPayments\Sdk\Model\CreationEntityState::ACTIVE);
        $webhookListener->setUrl($webhookUrl->getId());
        $webhookListener->setNotifyEveryChange($entity->isNotifyEveryChange());
        return $this->getWebhookListenerService()->create($spaceId, $webhookListener);
    }

    /**
     * Returns the existing webhook listeners.
     *
     * @param int $spaceId
     * @param \TrustPayments\Sdk\Model\WebhookUrl $webhookUrl
     * @return \TrustPayments\Sdk\Model\WebhookListener[]
     */
    protected function getWebhookListeners($spaceId, \TrustPayments\Sdk\Model\WebhookUrl $webhookUrl)
    {
        $query = new \TrustPayments\Sdk\Model\EntityQuery();
        $filter = new \TrustPayments\Sdk\Model\EntityQueryFilter();
        $filter->setType(\TrustPayments\Sdk\Model\EntityQueryFilterType::_AND);
        $filter->setChildren(
            array(
                $this->createEntityFilter('state', \TrustPayments\Sdk\Model\CreationEntityState::ACTIVE),
                $this->createEntityFilter('url.id', $webhookUrl->getId())
            )
        );
        $query->setFilter($filter);
        return $this->getWebhookListenerService()->search($spaceId, $query);
    }

    /**
     * Creates a webhook url.
     *
     * @param int $spaceId
     * @return \TrustPayments\Sdk\Model\WebhookUrlCreate
     */
    protected function createWebhookUrl($spaceId)
    {
        $webhookUrl = new \TrustPayments\Sdk\Model\WebhookUrlCreate();
        $webhookUrl->setUrl($this->getUrl());
        $webhookUrl->setState(\TrustPayments\Sdk\Model\CreationEntityState::ACTIVE);
        $webhookUrl->setName('Prestashop');
        return $this->getWebhookUrlService()->create($spaceId, $webhookUrl);
    }

    /**
     * Returns the existing webhook url if there is one.
     *
     * @param int $spaceId
     * @return \TrustPayments\Sdk\Model\WebhookUrl
     */
    protected function getWebhookUrl($spaceId)
    {
        $query = new \TrustPayments\Sdk\Model\EntityQuery();
        $filter = new \TrustPayments\Sdk\Model\EntityQueryFilter();
        $filter->setType(\TrustPayments\Sdk\Model\EntityQueryFilterType::_AND);
        $filter->setChildren(
            array(
                $this->createEntityFilter('state', \TrustPayments\Sdk\Model\CreationEntityState::ACTIVE),
                $this->createEntityFilter('url', $this->getUrl())
            )
        );
        $query->setFilter($filter);
        $query->setNumberOfEntities(1);
        $result = $this->getWebhookUrlService()->search($spaceId, $query);
        if (! empty($result)) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * Returns the webhook endpoint URL.
     *
     * @return string
     */
    protected function getUrl()
    {
        $link = Context::getContext()->link;

        $shopIds = Shop::getShops(true, null, true);
        asort($shopIds);
        $shopId = reset($shopIds);

        $languageIds = Language::getLanguages(true, $shopId, true);
        asort($languageIds);
        $languageId = reset($languageIds);

        $url = $link->getModuleLink('trustpayments', 'webhook', array(), true, $languageId, $shopId);
        // We have to parse the link, because of issue http://forge.prestashop.com/browse/BOOM-5799
        $urlQuery = parse_url($url, PHP_URL_QUERY);
        if (stripos($urlQuery, 'controller=module') !== false && stripos($urlQuery, 'controller=webhook') !== false) {
            $url = str_replace('controller=module', 'fc=module', $url);
        }
        return $url;
    }

    /**
     * Returns the webhook listener API service.
     *
     * @return \TrustPayments\Sdk\Service\WebhookListenerService
     */
    protected function getWebhookListenerService()
    {
        if ($this->webhookListenerService == null) {
            $this->webhookListenerService = new \TrustPayments\Sdk\Service\WebhookListenerService(
                TrustPaymentsHelper::getApiClient()
            );
        }
        return $this->webhookListenerService;
    }

    /**
     * Returns the webhook url API service.
     *
     * @return \TrustPayments\Sdk\Service\WebhookUrlService
     */
    protected function getWebhookUrlService()
    {
        if ($this->webhookUrlService == null) {
            $this->webhookUrlService = new \TrustPayments\Sdk\Service\WebhookUrlService(
                TrustPaymentsHelper::getApiClient()
            );
        }
        return $this->webhookUrlService;
    }
}
