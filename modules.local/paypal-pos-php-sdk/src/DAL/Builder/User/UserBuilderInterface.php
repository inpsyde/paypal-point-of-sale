<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\User;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\User\User;

interface UserBuilderInterface
{
    /**
     * @param array $data
     *
     * @return User
     */
    public function buildFromArray(array $data): User;

    /**
     * @param User $user
     * @return array
     */
    public function createDataArray(User $user): array;
}
