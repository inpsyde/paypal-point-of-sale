<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Organization;

use Syde\PayPal\PointOfSale\PhpSdk\API\OAuth\Organizations;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\Organization;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;

class RestOrganizationProvider implements OrganizationProvider
{
    private Organizations $client;

    public function __construct(
        Organizations $client
    ) {

        $this->client = $client;
    }

    /**
     * @return Organization
     *
     * @throws ZettleRestException
     */
    public function provide(): Organization
    {
        /**
         * We don't handle the possible Exceptions,
         * if the organization can't be built, we have a problem
         */
        return $this->client->account();
    }
}
