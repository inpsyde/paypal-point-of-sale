<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\User;

final class User
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $displayName;

    /**
     * User constructor.
     *
     * @param int $id
     * @param string $displayName
     */
    public function __construct(int $id, string $displayName)
    {
        $this->id = $id;
        $this->displayName = $displayName;
    }

    /**
     * @return int
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return User
     */
    public function setId(int $id): User
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function displayName(): string
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     *
     * @return User
     */
    public function setDisplayName(string $displayName): User
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Return by default the E-Mail
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->displayName;
    }
}
