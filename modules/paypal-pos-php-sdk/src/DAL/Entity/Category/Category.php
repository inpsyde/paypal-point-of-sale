<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Category;

use DateTime;
class Category
{
    /**
     * @var string
     */
    private $uuid;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $etag;
    /**
     * @var DateTime
     */
    private $updatedAt;
    /**
     * @var string
     */
    private $updatedBy;
    /**
     * @var DateTime
     */
    private $createdAt;
    /**
     * Category constructor.
     *
     * @param string $name
     * @param string|null $uuid
     * @param string|null $etag
     * @param DateTime|null $createdAt
     * @param DateTime|null $updatedAt
     * @param string|null $updatedBy
     */
    public function __construct(string $name, ?string $uuid = null, ?string $etag = null, ?DateTime $createdAt = null, ?DateTime $updatedAt = null, ?string $updatedBy = null)
    {
        $this->uuid = $uuid;
        $this->name = $name;
        $this->etag = $etag;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->updatedBy = $updatedBy;
    }
    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    /**
     * @param string $name
     * @return Category
     */
    public function setName(string $name): Category
    {
        $this->name = $name;
        return $this;
    }
    /**
     * @return string
     */
    public function uuid(): ?string
    {
        return $this->uuid;
    }
    /**
     * @param string $uuid
     * @return Category
     */
    public function setUuid(string $uuid): Category
    {
        $this->uuid = $uuid;
        return $this;
    }
    /**
     * @return string
     */
    public function etag(): string
    {
        return $this->etag;
    }
    /**
     * @param string $etag
     * @return Category
     */
    public function setEtag(string $etag): Category
    {
        $this->etag = $etag;
        return $this;
    }
    /**
     * @return DateTime
     */
    public function updatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
    /**
     * @param DateTime $updatedAt
     * @return Category
     */
    public function setUpdatedAt(DateTime $updatedAt): Category
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
    /**
     * @return string
     */
    public function updatedBy(): ?string
    {
        return $this->updatedBy;
    }
    /**
     * @param string $updatedBy
     * @return Category
     */
    public function setUpdatedBy(string $updatedBy): Category
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }
    /**
     * @return DateTime
     */
    public function createdAt(): ?DateTime
    {
        return $this->createdAt;
    }
    /**
     * @param DateTime $createdAt
     * @return Category
     */
    public function setCreatedAt(DateTime $createdAt): Category
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
