<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\User;

class UserFactory
{
    /**
     * @param string $id
     * @param string $displayName
     *
     * @return User
     */
    public function create(string $id, string $displayName): User
    {
        return new User((int) $id, $displayName);
    }
}
