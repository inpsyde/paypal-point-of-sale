<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Category;

use DateTime;
use Exception;
use Symfony\Component\Uid\Uuid;

class CategoryFactory
{
    /**
     * @param string $name
     * @param string|null $uuid
     * @param string|null $etag
     * @param string|null $createdAt
     * @param string|null $updatedAt
     * @param string|null $updatedBy
     *
     * @return Category
     * @throws Exception
     */
    public function create(
        string $name,
        ?string $uuid = null,
        ?string $etag = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $updatedBy = null
    ): Category {

        $createdAt = $createdAt
            ? (DateTime::createFromFormat('Y-m-d', $createdAt) ?: null)
            : new DateTime();
        $updatedAt = $updatedAt
            ? (DateTime::createFromFormat('Y-m-d', $updatedAt) ?: null)
            : new DateTime();

        return new Category(
            $name,
            $uuid ?? (string) Uuid::v1(),
            $etag,
            $createdAt,
            $updatedAt,
            $updatedBy ?? (string) Uuid::v1()
        );
    }

    /**
     * @param string $name
     * @param string $uuid
     * @param string|null $etag
     * @param string|null $createdAt
     * @param string|null $updatedAt
     * @param string|null $updatedBy
     *
     * @return Category
     *
     * @throws Exception
     */
    public function createFromUuid(
        string $name,
        string $uuid,
        ?string $etag = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $updatedBy = null
    ): Category {

        return new Category(
            $name,
            $uuid,
            $etag,
            new DateTime($createdAt),
            new DateTime($updatedAt),
            $updatedBy
        );
    }
}
