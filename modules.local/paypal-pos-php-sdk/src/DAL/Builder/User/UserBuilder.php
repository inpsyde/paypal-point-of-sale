<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\User;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\User\User;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\User\UserFactory;

class UserBuilder implements UserBuilderInterface
{
    private UserFactory $userFactory;

    /**
     * UserBuilder constructor.
     * @param UserFactory $userFactory
     */
    public function __construct(UserFactory $userFactory)
    {
        $this->userFactory = $userFactory;
    }

    /**
     * @param array $data
     *
     * @return User
     */
    public function buildFromArray(array $data): User
    {
        return $this->build($data);
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function createDataArray(User $user): array
    {
        return [
            'userId' => $user->id(),
            'userDisplayName' => $user->displayName(),
        ];
    }

    /**
     * @param array $data
     *
     * @return User
     */
    private function build(array $data): User
    {
        return $this->userFactory->create(
            $data['userId'],
            $data['userDisplayName']
        );
    }
}
