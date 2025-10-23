<?php

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Organization;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\Organization;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;

interface OrganizationProvider
{

    /**
     * @return Organization
     * @throws ZettleRestException
     */
    public function provide(): Organization;
}
