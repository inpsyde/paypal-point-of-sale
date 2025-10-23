<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Discount;

use DateTime;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;

class Discount
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
    private $description;

    /**
     * @var ImageCollection|null
     */
    private $imageCollection;

    /**
     * @var Price|null
     */
    private $amount;

    /**
     * @var float|null
     */
    private $percentage;

    /**
     * @var string|null
     */
    private $externalReference;

    /**
     * @var string|null
     */
    private $etag;

    /**
     * @var DateTime|null
     */
    private $updatedAt;

    /**
     * @var string|null
     */
    private $updatedBy;

    /**
     * @var DateTime|null
     */
    private $createdAt;

    /**
     * Discount constructor.
     *
     * @param string $uuid
     * @param string $name
     * @param string $description
     * @param ImageCollection|null $imageCollection
     * @param Price|null $amount
     * @param float|null $percentage
     * @param string|null $externalReference
     * @param string|null $etag
     * @param DateTime|null $updatedAt
     * @param string|null $updatedBy
     * @param DateTime|null $createdAt
     */
    public function __construct(
        string $uuid,
        string $name,
        string $description,
        ?ImageCollection $imageCollection = null,
        ?Price $amount = null,
        ?float $percentage = null,
        ?string $externalReference = null,
        ?string $etag = null,
        ?DateTime $updatedAt = null,
        ?string $updatedBy = null,
        ?DateTime $createdAt = null
    ) {

        $this->uuid = $uuid;
        $this->name = $name;
        $this->description = $description;
        $this->imageCollection = $imageCollection;
        $this->amount = $amount;
        $this->percentage = $percentage;
        $this->externalReference = $externalReference;
        $this->etag = $etag;
        $this->updatedAt = $updatedAt;
        $this->updatedBy = $updatedBy;
        $this->createdAt = $createdAt;
    }

    /**
     * @return string
     */
    public function uuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return Discount
     */
    public function setUuid(string $uuid): Discount
    {
        $this->uuid = $uuid;

        return $this;
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
     *
     * @return Discount
     */
    public function setName(string $name): Discount
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return Discount
     */
    public function setDescription(string $description): Discount
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return ImageCollection|null
     */
    public function imageCollection(): ?ImageCollection
    {
        return $this->imageCollection;
    }

    /**
     * @param ImageCollection|null $imageCollection
     *
     * @return Discount
     */
    public function setImageCollection(?ImageCollection $imageCollection): Discount
    {
        $this->imageCollection = $imageCollection;

        return $this;
    }

    /**
     * @return Price|null
     */
    public function amount(): ?float
    {
        return $this->amount;
    }

    /**
     * @param Price $amount
     * @return Discount
     */
    public function setAmount(Price $amount): Discount
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return float|null
     */
    public function percentage(): ?float
    {
        return $this->percentage;
    }

    /**
     * @param float $percentage
     *
     * @return Discount
     */
    public function setPercentage(float $percentage): Discount
    {
        $this->percentage = $percentage;

        return $this;
    }

    /**
     * @return string|null
     */
    public function externalReference(): ?string
    {
        return $this->externalReference;
    }

    /**
     * @param string $externalReference
     *
     * @return Discount
     */
    public function setExternalReference(string $externalReference): Discount
    {
        $this->externalReference = $externalReference;

        return $this;
    }

    /**
     * @return string|null
     */
    public function etag(): ?string
    {
        return $this->etag;
    }

    /**
     * @param string $etag
     *
     * @return Discount
     */
    public function setEtag(string $etag): Discount
    {
        $this->etag = $etag;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function updatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     *
     * @return Discount
     */
    public function setUpdatedAt(DateTime $updatedAt): Discount
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string|null
     */
    public function updatedBy(): ?string
    {
        return $this->updatedBy;
    }

    /**
     * @param string $updatedBy
     *
     * @return Discount
     */
    public function setUpdatedBy(string $updatedBy): Discount
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function createdAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     *
     * @return Discount
     */
    public function setCreatedAt(DateTime $createdAt): Discount
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
