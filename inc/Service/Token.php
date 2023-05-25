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
 * This service provides functions to deal with Trust Payments tokens.
 */
class TrustPaymentsServiceToken extends TrustPaymentsServiceAbstract
{

    /**
     * The token API service.
     *
     * @var \TrustPayments\Sdk\Service\TokenService
     */
    private $tokenService;

    /**
     * The token version API service.
     *
     * @var \TrustPayments\Sdk\Service\TokenVersionService
     */
    private $tokenVersionService;

    public function updateTokenVersion($spaceId, $tokenVersionId)
    {
        $tokenVersion = $this->getTokenVersionService()->read($spaceId, $tokenVersionId);
        $this->updateInfo($spaceId, $tokenVersion);
    }

    public function updateToken($spaceId, $tokenId)
    {
        $query = new \TrustPayments\Sdk\Model\EntityQuery();
        $filter = new \TrustPayments\Sdk\Model\EntityQueryFilter();
        $filter->setType(\TrustPayments\Sdk\Model\EntityQueryFilterType::_AND);
        $filter->setChildren(
            array(
                $this->createEntityFilter('token.id', $tokenId),
                $this->createEntityFilter('state', \TrustPayments\Sdk\Model\CreationEntityState::ACTIVE)
            )
        );
        $query->setFilter($filter);
        $query->setNumberOfEntities(1);
        $tokenVersions = $this->getTokenVersionService()->search($spaceId, $query);
        if (! empty($tokenVersions)) {
            $this->updateInfo($spaceId, current($tokenVersions));
        } else {
            $info = TrustPaymentsModelTokeninfo::loadByToken($spaceId, $tokenId);
            if ($info->getId()) {
                $info->delete();
            }
        }
    }

    protected function updateInfo($spaceId, \TrustPayments\Sdk\Model\TokenVersion $tokenVersion)
    {
        $info = TrustPaymentsModelTokeninfo::loadByToken($spaceId, $tokenVersion->getToken()->getId());
        if (! in_array(
            $tokenVersion->getToken()->getState(),
            array(
                \TrustPayments\Sdk\Model\CreationEntityState::ACTIVE,
                \TrustPayments\Sdk\Model\CreationEntityState::INACTIVE
            )
        )) {
            if ($info->getId()) {
                $info->delete();
            }
            return;
        }

        $info->setCustomerId($tokenVersion->getToken()
            ->getCustomerId());
        $info->setName($tokenVersion->getName());

	if ($tokenVersion->getPaymentConnectorConfiguration() === null) {
	    return;
	}

	$info->setPaymentMethodId(
            $tokenVersion->getPaymentConnectorConfiguration()
                ->getPaymentMethodConfiguration()
                ->getId()
        );
        $info->setConnectorId($tokenVersion->getPaymentConnectorConfiguration()
            ->getConnector());

        $info->setSpaceId($spaceId);
        $info->setState($tokenVersion->getToken()
            ->getState());
        $info->setTokenId($tokenVersion->getToken()
            ->getId());
        $info->save();
    }

    public function deleteToken($spaceId, $tokenId)
    {
        $this->getTokenService()->delete($spaceId, $tokenId);
    }

    /**
     * Returns the token API service.
     *
     * @return \TrustPayments\Sdk\Service\TokenService
     */
    protected function getTokenService()
    {
        if ($this->tokenService == null) {
            $this->tokenService = new \TrustPayments\Sdk\Service\TokenService(
                TrustPaymentsHelper::getApiClient()
            );
        }

        return $this->tokenService;
    }

    /**
     * Returns the token version API service.
     *
     * @return \TrustPayments\Sdk\Service\TokenVersionService
     */
    protected function getTokenVersionService()
    {
        if ($this->tokenVersionService == null) {
            $this->tokenVersionService = new \TrustPayments\Sdk\Service\TokenVersionService(
                TrustPaymentsHelper::getApiClient()
            );
        }

        return $this->tokenVersionService;
    }
}
