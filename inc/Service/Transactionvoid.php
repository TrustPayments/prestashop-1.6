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
 * This service provides functions to deal with Trust Payments transaction voids.
 */
class TrustPaymentsServiceTransactionvoid extends TrustPaymentsServiceAbstract
{

    /**
     * The transaction void API service.
     *
     * @var \TrustPayments\Sdk\Service\TransactionVoidService
     */
    private $voidService;

    public function executeVoid($order)
    {
        $currentVoidId = null;
        try {
            TrustPaymentsHelper::startDBTransaction();
            $transactionInfo = TrustPaymentsHelper::getTransactionInfoForOrder($order);
            if ($transactionInfo === null) {
                throw new Exception(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Could not load corresponding transaction.',
                        'transactionvoid'
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
                        'The transaction is not in a state to be voided.',
                        'transactionvoid'
                    )
                );
            }
            if (TrustPaymentsModelVoidjob::isVoidRunningForTransaction($spaceId, $transactionId)) {
                throw new Exception(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Please wait until the existing void is processed.',
                        'transactionvoid'
                    )
                );
            }
            if (TrustPaymentsModelCompletionjob::isCompletionRunningForTransaction($spaceId, $transactionId)) {
                throw new Exception(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'There is a completion in process. The order can not be voided.',
                        'transactionvoid'
                    )
                );
            }

            $voidJob = new TrustPaymentsModelVoidjob();
            $voidJob->setSpaceId($spaceId);
            $voidJob->setTransactionId($transactionId);
            $voidJob->setState(TrustPaymentsModelVoidjob::STATE_CREATED);
            $voidJob->setOrderId(
                TrustPaymentsHelper::getOrderMeta($order, 'trustPaymentsMainOrderId')
            );
            $voidJob->save();
            $currentVoidId = $voidJob->getId();
            TrustPaymentsHelper::commitDBTransaction();
        } catch (Exception $e) {
            TrustPaymentsHelper::rollbackDBTransaction();
            throw $e;
        }
        $this->sendVoid($currentVoidId);
    }

    protected function sendVoid($voidJobId)
    {
        $voidJob = new TrustPaymentsModelVoidjob($voidJobId);
        TrustPaymentsHelper::startDBTransaction();
        TrustPaymentsHelper::lockByTransactionId($voidJob->getSpaceId(), $voidJob->getTransactionId());
        // Reload void job;
        $voidJob = new TrustPaymentsModelVoidjob($voidJobId);
        if ($voidJob->getState() != TrustPaymentsModelVoidjob::STATE_CREATED) {
            // Already sent in the meantime
            TrustPaymentsHelper::rollbackDBTransaction();
            return;
        }
        try {
            $void = $this->getVoidService()->voidOnline($voidJob->getSpaceId(), $voidJob->getTransactionId());
            $voidJob->setVoidId($void->getId());
            $voidJob->setState(TrustPaymentsModelVoidjob::STATE_SENT);
            $voidJob->save();
            TrustPaymentsHelper::commitDBTransaction();
        } catch (\TrustPayments\Sdk\ApiException $e) {
            if ($e->getResponseObject() instanceof \TrustPayments\Sdk\Model\ClientError) {
                $voidJob->setFailureReason(
                    array(
                        'en-US' => sprintf(
                            TrustPaymentsHelper::getModuleInstance()->l(
                                'Could not send the void to %s. Error: %s',
                                'transactionvoid'
                            ),
                            'Trust Payments',
                            TrustPaymentsHelper::cleanExceptionMessage($e->getMessage())
                        )
                    )
                );
                $voidJob->setState(TrustPaymentsModelVoidjob::STATE_FAILURE);
                $voidJob->save();
                TrustPaymentsHelper::commitDBTransaction();
            } else {
                $voidJob->save();
                TrustPaymentsHelper::commitDBTransaction();
                $message = sprintf(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Error sending void job with id %d: %s',
                        'transactionvoid'
                    ),
                    $voidJobId,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'TrustPaymentsModelVoidjob');
                throw $e;
            }
        } catch (Exception $e) {
            $voidJob->save();
            TrustPaymentsHelper::commitDBTransaction();
            $message = sprintf(
                TrustPaymentsHelper::getModuleInstance()->l(
                    'Error sending void job with id %d: %s',
                    'transactionvoid'
                ),
                $voidJobId,
                $e->getMessage()
            );
            PrestaShopLogger::addLog($message, 3, null, 'TrustPaymentsModelVoidjob');
            throw $e;
        }
    }

    public function updateForOrder($order)
    {
        $transactionInfo = TrustPaymentsHelper::getTransactionInfoForOrder($order);
        $spaceId = $transactionInfo->getSpaceId();
        $transactionId = $transactionInfo->getTransactionId();
        $voidJob = TrustPaymentsModelVoidjob::loadRunningVoidForTransaction($spaceId, $transactionId);
        if ($voidJob->getState() == TrustPaymentsModelVoidjob::STATE_CREATED) {
            $this->sendVoid($voidJob->getId());
        }
    }

    public function updateVoids($endTime = null)
    {
        $toProcess = TrustPaymentsModelVoidjob::loadNotSentJobIds();

        foreach ($toProcess as $id) {
            if ($endTime !== null && time() + 15 > $endTime) {
                return;
            }
            try {
                $this->sendVoid($id);
            } catch (Exception $e) {
                $message = sprintf(
                    TrustPaymentsHelper::getModuleInstance()->l(
                        'Error updating void job with id %d: %s',
                        'transactionvoid'
                    ),
                    $id,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'TrustPaymentsModelVoidjob');
            }
        }
    }

    public function hasPendingVoids()
    {
        $toProcess = TrustPaymentsModelVoidjob::loadNotSentJobIds();
        return ! empty($toProcess);
    }

    /**
     * Returns the transaction void API service.
     *
     * @return \TrustPayments\Sdk\Service\TransactionVoidService
     */
    protected function getVoidService()
    {
        if ($this->voidService == null) {
            $this->voidService = new \TrustPayments\Sdk\Service\TransactionVoidService(
                TrustPaymentsHelper::getApiClient()
            );
        }

        return $this->voidService;
    }
}
