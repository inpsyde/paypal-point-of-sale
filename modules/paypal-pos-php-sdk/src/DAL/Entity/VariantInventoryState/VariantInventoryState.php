<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantInventoryState;

final class VariantInventoryState
{
    /**
     * @var string
     */
    private $uuid;
    /**
     * @var string
     */
    private $productUuid;
    /**
     * @var string
     */
    private $variantUuid;
    /**
     * @var int
     */
    private $balance;
    /**
     * VariantChangeHistory constructor.
     *
     * @param string $locationUuid
     * @param string $productUuid
     * @param string $variantUuid
     * @param int $balance
     *
     */
    public function __construct(string $locationUuid, string $productUuid, string $variantUuid, int $balance)
    {
        $this->uuid = $locationUuid;
        $this->productUuid = $productUuid;
        $this->variantUuid = $variantUuid;
        $this->balance = $balance;
    }
    /**
     * @return string
     */
    public function locationUuid(): string
    {
        return $this->uuid;
    }
    /**
     * @return string
     */
    public function productUuid(): string
    {
        return $this->productUuid;
    }
    /**
     * @return string
     */
    public function variantUuid(): string
    {
        return $this->variantUuid;
    }
    /**
     * @return int
     */
    public function balance(): int
    {
        return $this->balance;
    }
}
