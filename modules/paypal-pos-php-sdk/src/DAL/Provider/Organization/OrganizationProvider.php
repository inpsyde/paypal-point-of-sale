<?php

namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Organization;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\Organization;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
interface OrganizationProvider
{
    /**
     * @return Organization
     * @throws ZettleRestException
     */
    public function provide(): Organization;
}
