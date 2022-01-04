<?php
/**
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2022 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

/**
 * This service provides functions to deal with Trust Payments refunds.
 */
class TrustPaymentsServiceRefund extends TrustPaymentsServiceAbstract
{
    private static $refundableStates = array(
        \TrustPayments\Sdk\Model\TransactionState::COMPLETED,
        \TrustPayments\Sdk\Model\TransactionState::DECLINE,
        \TrustPayments\Sdk\Model\TransactionState::FULFILL
    );

    /**
     * The refund API service.
     *
     * @var \TrustPayments\Sdk\Service\RefundService
     */
    private $refundService;

    /**
     * Returns the refund by the given external id.
     *
     * @param int $spaceId
     * @param string $externalId
     * @return \TrustPayments\Sdk\Model\Refund
     */
    public function getRefundByExternalId($spaceId, $externalId)
    {
        $query = new \TrustPayments\Sdk\Model\EntityQuery();
        $query->setFilter($this->createEntityFilter('externalId', $externalId));
        $query->setNumberOfEntities(1);
        $result = $this->getRefundService()->search($spaceId, $query);
        if ($result != null && ! empty($result)) {
            return current($result);
        } else {
            throw new Exception('The refund could not be found.');
        }
    }

    public function executeRefund(Order $order, array $parsedParameters)
    {
        $currentRefundJob = null;
        try {
            TrustPaymentsHelper::startDBTransaction();
            $transactionInfo = TrustPaymentsHelper::getTransactionInfoForOrder($order);
            if ($transactionInfo === null) {
                throw new Exception(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Could not load corresponding transaction',
                        'refund'
                    )
                );
            }

            TrustPaymentsHelper::lockByTransactionId(
                $transactionInfo->getSpaceId(),
                $transactionInfo->getTransactionId()
            );
            // Reload after locking
            $transactionInfo = TrustPaymentsModelTransactioninfo::loadByTransaction(
                $transactionInfo->getSpaceId(),
                $transactionInfo->getTransactionId()
            );
            $spaceId = $transactionInfo->getSpaceId();
            $transactionId = $transactionInfo->getTransactionId();

            if (! in_array($transactionInfo->getState(), self::$refundableStates)) {
                throw new Exception(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'The transaction is not in a state to be refunded.',
                        'refund'
                    )
                );
            }

            if (TrustPaymentsModelRefundjob::isRefundRunningForTransaction($spaceId, $transactionId)) {
                throw new Exception(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Please wait until the existing refund is processed.',
                        'refund'
                    )
                );
            }

            $refundJob = new TrustPaymentsModelRefundjob();
            $refundJob->setState(TrustPaymentsModelRefundjob::STATE_CREATED);
            $refundJob->setOrderId($order->id);
            $refundJob->setSpaceId($transactionInfo->getSpaceId());
            $refundJob->setTransactionId($transactionInfo->getTransactionId());
            $refundJob->setExternalId(uniqid($order->id . '-'));
            $refundJob->setRefundParameters($parsedParameters);
            $refundJob->save();
            // validate Refund Job
            $this->createRefundObject($refundJob);
            $currentRefundJob = $refundJob->getId();
            TrustPaymentsHelper::commitDBTransaction();
        } catch (Exception $e) {
            TrustPaymentsHelper::rollbackDBTransaction();
            throw $e;
        }
        $this->sendRefund($currentRefundJob);
    }

    protected function sendRefund($refundJobId)
    {
        $refundJob = new TrustPaymentsModelRefundjob($refundJobId);
        TrustPaymentsHelper::startDBTransaction();
        TrustPaymentsHelper::lockByTransactionId($refundJob->getSpaceId(), $refundJob->getTransactionId());
        // Reload refund job;
        $refundJob = new TrustPaymentsModelRefundjob($refundJobId);
        if ($refundJob->getState() != TrustPaymentsModelRefundjob::STATE_CREATED) {
            // Already sent in the meantime
            TrustPaymentsHelper::rollbackDBTransaction();
            return;
        }
        try {
            $executedRefund = $this->refund($refundJob->getSpaceId(), $this->createRefundObject($refundJob));
            $refundJob->setState(TrustPaymentsModelRefundjob::STATE_SENT);
            $refundJob->setRefundId($executedRefund->getId());

            if ($executedRefund->getState() == \TrustPayments\Sdk\Model\RefundState::PENDING) {
                $refundJob->setState(TrustPaymentsModelRefundjob::STATE_PENDING);
            }
            $refundJob->save();
            TrustPaymentsHelper::commitDBTransaction();
        } catch (\TrustPayments\Sdk\ApiException $e) {
            if ($e->getResponseObject() instanceof \TrustPayments\Sdk\Model\ClientError) {
                $refundJob->setFailureReason(
                    array(
                        'en-US' => sprintf(
                            TrustPaymentsHelper::getModuleInstance()->l(
                                'Could not send the refund to %s. Error: %s',
                                'refund'
                            ),
                            'Trust Payments',
                            TrustPaymentsHelper::cleanExceptionMessage($e->getMessage())
                        )
                    )
                );
                $refundJob->setState(TrustPaymentsModelRefundjob::STATE_FAILURE);
                $refundJob->save();
                TrustPaymentsHelper::commitDBTransaction();
            } else {
                $refundJob->save();
                TrustPaymentsHelper::commitDBTransaction();
                $message = sprintf(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Error sending refund job with id %d: %s',
                        'refund'
                    ),
                    $refundJobId,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'TrustPaymentsModelRefundjob');
                throw $e;
            }
        } catch (Exception $e) {
            $refundJob->save();
            TrustPaymentsHelper::commitDBTransaction();
            $message = sprintf(
                TrustPaymentsHelper::getModuleInstance()->l('Error sending refund job with id %d: %s', 'refund'),
                $refundJobId,
                $e->getMessage()
            );
            PrestaShopLogger::addLog($message, 3, null, 'TrustPaymentsModelRefundjob');
            throw $e;
        }
    }

    public function applyRefundToShop($refundJobId)
    {
        $refundJob = new TrustPaymentsModelRefundjob($refundJobId);
        TrustPaymentsHelper::startDBTransaction();
        TrustPaymentsHelper::lockByTransactionId($refundJob->getSpaceId(), $refundJob->getTransactionId());
        // Reload refund job;
        $refundJob = new TrustPaymentsModelRefundjob($refundJobId);
        if ($refundJob->getState() != TrustPaymentsModelRefundjob::STATE_APPLY) {
            // Already processed in the meantime
            TrustPaymentsHelper::rollbackDBTransaction();
            return;
        }
        try {
            $order = new Order($refundJob->getOrderId());
            $strategy = TrustPaymentsBackendStrategyprovider::getStrategy();
            $appliedData = $strategy->applyRefund($order, $refundJob->getRefundParameters());
            $refundJob->setState(TrustPaymentsModelRefundjob::STATE_SUCCESS);
            $refundJob->save();
            TrustPaymentsHelper::commitDBTransaction();
            try {
                $strategy->afterApplyRefundActions($order, $refundJob->getRefundParameters(), $appliedData);
            } catch (Exception $e) {
                // We ignore errors in the after apply actions
            }
        } catch (Exception $e) {
            TrustPaymentsHelper::rollbackDBTransaction();
            TrustPaymentsHelper::startDBTransaction();
            TrustPaymentsHelper::lockByTransactionId($refundJob->getSpaceId(), $refundJob->getTransactionId());
            $refundJob = new TrustPaymentsModelRefundjob($refundJobId);
            $refundJob->increaseApplyTries();
            if ($refundJob->getApplyTries() > 3) {
                $refundJob->setState(TrustPaymentsModelRefundjob::STATE_FAILURE);
                $refundJob->setFailureReason(array(
                    'en-US' => sprintf($e->getMessage())
                ));
            }
            $refundJob->save();
            TrustPaymentsHelper::commitDBTransaction();
        }
    }

    public function updateForOrder($order)
    {
        $transactionInfo = TrustPaymentsHelper::getTransactionInfoForOrder($order);
        $spaceId = $transactionInfo->getSpaceId();
        $transactionId = $transactionInfo->getTransactionId();
        $refundJob = TrustPaymentsModelRefundjob::loadRunningRefundForTransaction($spaceId, $transactionId);
        if ($refundJob->getState() == TrustPaymentsModelRefundjob::STATE_CREATED) {
            $this->sendRefund($refundJob->getId());
        } elseif ($refundJob->getState() == TrustPaymentsModelRefundjob::STATE_APPLY) {
            $this->applyRefundToShop($refundJob->getId());
        }
    }

    public function updateRefunds($endTime = null)
    {
        $toSend = TrustPaymentsModelRefundjob::loadNotSentJobIds();
        foreach ($toSend as $id) {
            if ($endTime !== null && time() + 15 > $endTime) {
                return;
            }
            try {
                $this->sendRefund($id);
            } catch (Exception $e) {
                $message = sprintf(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Error updating refund job with id %d: %s',
                        'refund'
                    ),
                    $id,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'TrustPaymentsModelRefundjob');
            }
        }
        $toApply = TrustPaymentsModelRefundjob::loadNotAppliedJobIds();
        foreach ($toApply as $id) {
            if ($endTime !== null && time() + 15 > $endTime) {
                return;
            }
            try {
                $this->applyRefundToShop($id);
            } catch (Exception $e) {
                $message = sprintf(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Error applying refund job with id %d: %s',
                        'refund'
                    ),
                    $id,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'TrustPaymentsModelRefundjob');
            }
        }
    }

    public function hasPendingRefunds()
    {
        $toSend = TrustPaymentsModelRefundjob::loadNotSentJobIds();
        $toApply = TrustPaymentsModelRefundjob::loadNotAppliedJobIds();
        return ! empty($toSend) || ! empty($toApply);
    }

    /**
     * Creates a refund request model for the given parameters.
     *
     * @param Order $order
     * @param array $refund
     *            Refund data to be determined
     * @return \TrustPayments\Sdk\Model\RefundCreate
     */
    protected function createRefundObject(TrustPaymentsModelRefundjob $refundJob)
    {
        $order = new Order($refundJob->getOrderId());

        $strategy = TrustPaymentsBackendStrategyprovider::getStrategy();

        $spaceId = $refundJob->getSpaceId();
        $transactionId = $refundJob->getTransactionId();
        $externalRefundId = $refundJob->getExternalId();
        $parsedData = $refundJob->getRefundParameters();
        $amount = $strategy->getRefundTotal($parsedData);
        $type = $strategy->getTrustPaymentsRefundType($parsedData);

        $reductions = $strategy->createReductions($order, $parsedData);
        $reductions = $this->fixReductions($amount, $spaceId, $transactionId, $reductions);

        $remoteRefund = new \TrustPayments\Sdk\Model\RefundCreate();
        $remoteRefund->setExternalId($externalRefundId);
        $remoteRefund->setReductions($reductions);
        $remoteRefund->setTransaction($transactionId);
        $remoteRefund->setType($type);

        return $remoteRefund;
    }

    /**
     * Returns the fixed line item reductions for the refund.
     *
     * If the amount of the given reductions does not match the refund's grand total, the amount to refund is
     * distributed equally to the line items.
     *
     * @param float $refundTotal
     * @param int $spaceId
     * @param int $transactionId
     * @param \TrustPayments\Sdk\Model\LineItemReductionCreate[] $reductions
     * @return \TrustPayments\Sdk\Model\LineItemReductionCreate[]
     */
    protected function fixReductions($refundTotal, $spaceId, $transactionId, array $reductions)
    {
        $baseLineItems = $this->getBaseLineItems($spaceId, $transactionId);
        $reductionAmount = TrustPaymentsHelper::getReductionAmount($baseLineItems, $reductions);

        $configuration = TrustPaymentsVersionadapter::getConfigurationInterface();
        $computePrecision = $configuration->get('_PS_PRICE_COMPUTE_PRECISION_');

        if (Tools::ps_round($refundTotal, $computePrecision) != Tools::ps_round($reductionAmount, $computePrecision)) {
            $fixedReductions = array();
            $baseAmount = TrustPaymentsHelper::getTotalAmountIncludingTax($baseLineItems);
            $rate = $refundTotal / $baseAmount;
            foreach ($baseLineItems as $lineItem) {
                $reduction = new \TrustPayments\Sdk\Model\LineItemReductionCreate();
                $reduction->setLineItemUniqueId($lineItem->getUniqueId());
                $reduction->setQuantityReduction(0);
                $reduction->setUnitPriceReduction(
                    round($lineItem->getAmountIncludingTax() * $rate / $lineItem->getQuantity(), 8)
                );
                $fixedReductions[] = $reduction;
            }

            return $fixedReductions;
        } else {
            return $reductions;
        }
    }

    /**
     * Sends the refund to the gateway.
     *
     * @param int $spaceId
     * @param \TrustPayments\Sdk\Model\RefundCreate $refund
     * @return \TrustPayments\Sdk\Model\Refund
     */
    public function refund($spaceId, \TrustPayments\Sdk\Model\RefundCreate $refund)
    {
        return $this->getRefundService()->refund($spaceId, $refund);
    }

    /**
     * Returns the line items that are to be used to calculate the refund.
     *
     * This returns the line items of the latest refund if there is one or else of the completed transaction.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @param \TrustPayments\Sdk\Model\Refund $refund
     * @return \TrustPayments\Sdk\Model\LineItem[]
     */
    protected function getBaseLineItems($spaceId, $transactionId, \TrustPayments\Sdk\Model\Refund $refund = null)
    {
        $lastSuccessfulRefund = $this->getLastSuccessfulRefund($spaceId, $transactionId, $refund);
        if ($lastSuccessfulRefund) {
            return $lastSuccessfulRefund->getReducedLineItems();
        } else {
            return $this->getTransactionInvoice($spaceId, $transactionId)->getLineItems();
        }
    }

    /**
     * Returns the transaction invoice for the given transaction.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @throws Exception
     * @return \TrustPayments\Sdk\Model\TransactionInvoice
     */
    protected function getTransactionInvoice($spaceId, $transactionId)
    {
        $query = new \TrustPayments\Sdk\Model\EntityQuery();

        $filter = new \TrustPayments\Sdk\Model\EntityQueryFilter();
        $filter->setType(\TrustPayments\Sdk\Model\EntityQueryFilterType::_AND);
        $filter->setChildren(
            array(
                $this->createEntityFilter(
                    'state',
                    \TrustPayments\Sdk\Model\TransactionInvoiceState::CANCELED,
                    \TrustPayments\Sdk\Model\CriteriaOperator::NOT_EQUALS
                ),
                $this->createEntityFilter('completion.lineItemVersion.transaction.id', $transactionId)
            )
        );
        $query->setFilter($filter);
        $query->setNumberOfEntities(1);
        $invoiceService = new \TrustPayments\Sdk\Service\TransactionInvoiceService(
            TrustPaymentsHelper::getApiClient()
        );
        $result = $invoiceService->search($spaceId, $query);
        if (! empty($result)) {
            return $result[0];
        } else {
            throw new Exception('The transaction invoice could not be found.');
        }
    }

    /**
     * Returns the last successful refund of the given transaction, excluding the given refund.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @param \TrustPayments\Sdk\Model\Refund $refund
     * @return \TrustPayments\Sdk\Model\Refund
     */
    protected function getLastSuccessfulRefund(
        $spaceId,
        $transactionId,
        \TrustPayments\Sdk\Model\Refund $refund = null
    ) {
        $query = new \TrustPayments\Sdk\Model\EntityQuery();

        $filter = new \TrustPayments\Sdk\Model\EntityQueryFilter();
        $filter->setType(\TrustPayments\Sdk\Model\EntityQueryFilterType::_AND);
        $filters = array(
            $this->createEntityFilter('state', \TrustPayments\Sdk\Model\RefundState::SUCCESSFUL),
            $this->createEntityFilter('transaction.id', $transactionId)
        );
        if ($refund != null) {
            $filters[] = $this->createEntityFilter(
                'id',
                $refund->getId(),
                \TrustPayments\Sdk\Model\CriteriaOperator::NOT_EQUALS
            );
        }

        $filter->setChildren($filters);
        $query->setFilter($filter);

        $query->setOrderBys(
            array(
                $this->createEntityOrderBy('createdOn', \TrustPayments\Sdk\Model\EntityQueryOrderByType::DESC)
            )
        );
        $query->setNumberOfEntities(1);

        $result = $this->getRefundService()->search($spaceId, $query);
        if (! empty($result)) {
            return $result[0];
        } else {
            return false;
        }
    }

    /**
     * Returns the refund API service.
     *
     * @return \TrustPayments\Sdk\Service\RefundService
     */
    protected function getRefundService()
    {
        if ($this->refundService == null) {
            $this->refundService = new \TrustPayments\Sdk\Service\RefundService(
                TrustPaymentsHelper::getApiClient()
            );
        }

        return $this->refundService;
    }
}
