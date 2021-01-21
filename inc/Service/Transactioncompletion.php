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
 * This service provides functions to deal with Trust Payments transaction completions.
 */
class TrustPaymentsServiceTransactioncompletion extends TrustPaymentsServiceAbstract
{

    /**
     * The transaction completion API service.
     *
     * @var \TrustPayments\Sdk\Service\TransactionCompletionService
     */
    private $completionService;

    public function executeCompletion($order)
    {
        $currentCompletionJob = null;
        try {
            TrustPaymentsHelper::startDBTransaction();
            $transactionInfo = TrustPaymentsHelper::getTransactionInfoForOrder($order);
            if ($transactionInfo === null) {
                throw new Exception(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Could not load corresponding transaction.',
                        'transactioncompletion'
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

            if ($transactionInfo->getState() != \TrustPayments\Sdk\Model\TransactionState::AUTHORIZED) {
                throw new Exception(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'The transaction is not in a state to be completed.',
                        'transactioncompletion'
                    )
                );
            }

            if (TrustPaymentsModelCompletionjob::isCompletionRunningForTransaction($spaceId, $transactionId)) {
                throw new Exception(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Please wait until the existing completion is processed.',
                        'transactioncompletion'
                    )
                );
            }

            if (TrustPaymentsModelVoidjob::isVoidRunningForTransaction($spaceId, $transactionId)) {
                throw new Exception(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'There is a void in process. The order can not be completed.',
                        'transactioncompletion'
                    )
                );
            }

            $completionJob = new TrustPaymentsModelCompletionjob();
            $completionJob->setSpaceId($spaceId);
            $completionJob->setTransactionId($transactionId);
            $completionJob->setState(TrustPaymentsModelCompletionjob::STATE_CREATED);
            $completionJob->setOrderId(
                TrustPaymentsHelper::getOrderMeta($order, 'trustPaymentsMainOrderId')
            );
            $completionJob->save();
            $currentCompletionJob = $completionJob->getId();
            TrustPaymentsHelper::commitDBTransaction();
        } catch (Exception $e) {
            TrustPaymentsHelper::rollbackDBTransaction();
            throw $e;
        }

        try {
            $this->updateLineItems($currentCompletionJob);
            $this->sendCompletion($currentCompletionJob);
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function updateLineItems($completionJobId)
    {
        $completionJob = new TrustPaymentsModelCompletionjob($completionJobId);
        TrustPaymentsHelper::startDBTransaction();
        TrustPaymentsHelper::lockByTransactionId(
            $completionJob->getSpaceId(),
            $completionJob->getTransactionId()
        );
        // Reload completion job;
        $completionJob = new TrustPaymentsModelCompletionjob($completionJobId);

        if ($completionJob->getState() != TrustPaymentsModelCompletionjob::STATE_CREATED) {
            // Already updated in the meantime
            TrustPaymentsHelper::rollbackDBTransaction();
            return;
        }
        try {
            $baseOrder = new Order($completionJob->getOrderId());
            $collected = $baseOrder->getBrother()->getResults();
            $collected[] = $baseOrder;

            $lineItems = TrustPaymentsServiceLineitem::instance()->getItemsFromOrders($collected);
            TrustPaymentsServiceTransaction::instance()->updateLineItems(
                $completionJob->getSpaceId(),
                $completionJob->getTransactionId(),
                $lineItems
            );
            $completionJob->setState(TrustPaymentsModelCompletionjob::STATE_ITEMS_UPDATED);
            $completionJob->save();
            TrustPaymentsHelper::commitDBTransaction();
        } catch (\TrustPayments\Sdk\ApiException $e) {
            if ($e->getResponseObject() instanceof \TrustPayments\Sdk\Model\ClientError) {
                $completionJob->setFailureReason(
                    array(
                        'en-US' => sprintf(
                            TrustPaymentsHelper::getModuleInstance()->l(
                                'Could not update the line items. Error: %s',
                                'transactioncompletion'
                            ),
                            TrustPaymentsHelper::cleanExceptionMessage($e->getMessage())
                        )
                    )
                );
                $completionJob->setState(TrustPaymentsModelCompletionjob::STATE_FAILURE);
                $completionJob->save();
                TrustPaymentsHelper::commitDBTransaction();
            } else {
                $completionJob->save();
                TrustPaymentsHelper::commitDBTransaction();
                $message = sprintf(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Error updating line items for completion job with id %d: %s',
                        'transactioncompletion'
                    ),
                    $completionJobId,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'TrustPaymentsModelCompletionjob');
                throw $e;
            }
        } catch (Exception $e) {
            $completionJob->save();
            TrustPaymentsHelper::commitDBTransaction();
            $message = sprintf(
                TrustPaymentsHelper::getModuleInstance()->l(
                    'Error updating line items for completion job with id %d: %s',
                    'transactioncompletion'
                ),
                $completionJobId,
                $e->getMessage()
            );
            PrestaShopLogger::addLog($message, 3, null, 'TrustPaymentsModelCompletionjob');
            throw $e;
        }
    }

    protected function sendCompletion($completionJobId)
    {
        $completionJob = new TrustPaymentsModelCompletionjob($completionJobId);
        TrustPaymentsHelper::startDBTransaction();
        TrustPaymentsHelper::lockByTransactionId(
            $completionJob->getSpaceId(),
            $completionJob->getTransactionId()
        );
        // Reload completion job;
        $completionJob = new TrustPaymentsModelCompletionjob($completionJobId);

        if ($completionJob->getState() != TrustPaymentsModelCompletionjob::STATE_ITEMS_UPDATED) {
            // Already sent in the meantime
            TrustPaymentsHelper::rollbackDBTransaction();
            return;
        }
        try {
            $completion = $this->getCompletionService()->completeOnline(
                $completionJob->getSpaceId(),
                $completionJob->getTransactionId()
            );
            $completionJob->setCompletionId($completion->getId());
            $completionJob->setState(TrustPaymentsModelCompletionjob::STATE_SENT);
            $completionJob->save();
            TrustPaymentsHelper::commitDBTransaction();
        } catch (\TrustPayments\Sdk\ApiException $e) {
            if ($e->getResponseObject() instanceof \TrustPayments\Sdk\Model\ClientError) {
                $completionJob->setFailureReason(
                    array(
                        'en-US' => sprintf(
                            TrustPaymentsHelper::getModuleInstance()->l(
                                'Could not send the completion to %s. Error: %s',
                                'transactioncompletion'
                            ),
                            'Trust Payments',
                            TrustPaymentsHelper::cleanExceptionMessage($e->getMessage())
                        )
                    )
                );
                $completionJob->setState(TrustPaymentsModelCompletionjob::STATE_FAILURE);
                $completionJob->save();
                TrustPaymentsHelper::commitDBTransaction();
            } else {
                $completionJob->save();
                TrustPaymentsHelper::commitDBTransaction();
                $message = sprintf(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Error sending completion job with id %d: %s',
                        'transactioncompletion'
                    ),
                    $completionJobId,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'TrustPaymentsModelCompletionjob');
                throw $e;
            }
        } catch (Exception $e) {
            $completionJob->save();
            TrustPaymentsHelper::commitDBTransaction();
            $message = sprintf(
                TrustPaymentsHelper::getModuleInstance()->l(
                    'Error sending completion job with id %d: %s',
                    'transactioncompletion'
                ),
                $completionJobId,
                $e->getMessage()
            );
            PrestaShopLogger::addLog($message, 3, null, 'TrustPaymentsModelCompletionjob');
            throw $e;
        }
    }

    public function updateForOrder($order)
    {
        $transactionInfo = TrustPaymentsHelper::getTransactionInfoForOrder($order);
        $spaceId = $transactionInfo->getSpaceId();
        $transactionId = $transactionInfo->getTransactionId();
        $completionJob = TrustPaymentsModelCompletionjob::loadRunningCompletionForTransaction(
            $spaceId,
            $transactionId
        );
        $this->updateLineItems($completionJob->getId());
        $this->sendCompletion($completionJob->getId());
    }

    public function updateCompletions($endTime = null)
    {
        $toProcess = TrustPaymentsModelCompletionjob::loadNotSentJobIds();
        foreach ($toProcess as $id) {
            if ($endTime !== null && time() + 15 > $endTime) {
                return;
            }
            try {
                $this->updateLineItems($id);
                $this->sendCompletion($id);
            } catch (Exception $e) {
                $message = sprintf(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Error updating completion job with id %d: %s',
                        'transactioncompletion'
                    ),
                    $id,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'TrustPaymentsModelCompletionjob');
            }
        }
    }

    public function hasPendingCompletions()
    {
        $toProcess = TrustPaymentsModelCompletionjob::loadNotSentJobIds();
        return ! empty($toProcess);
    }

    /**
     * Returns the transaction completion API service.
     *
     * @return \TrustPayments\Sdk\Service\TransactionCompletionService
     */
    protected function getCompletionService()
    {
        if ($this->completionService == null) {
            $this->completionService = new \TrustPayments\Sdk\Service\TransactionCompletionService(
                TrustPaymentsHelper::getApiClient()
            );
        }
        return $this->completionService;
    }
}
