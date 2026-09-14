<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\Zettle\Product;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map\MapRecordCreator;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map\OneToManyMapInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
class ProductRepository implements ProductRepositoryInterface
{
    private OneToOneMapInterface&OneToManyMapInterface&MapRecordCreator $productMap;
    public function __construct(OneToManyMapInterface&OneToOneMapInterface&MapRecordCreator $productMap)
    {
        $this->productMap = $productMap;
    }
    /**
     * @inheritDoc
     */
    public function findByUuid(string $uuid): ?int
    {
        try {
            $productId = $this->productMap->localId($uuid);
        } catch (IdNotFoundException $exception) {
            return null;
        }
        return $productId;
    }
    /**
     * @inheritDoc
     */
    public function findById(int $productId): ?string
    {
        try {
            $uuid = $this->productMap->remoteId($productId);
        } catch (IdNotFoundException $exception) {
            return null;
        }
        return $uuid;
    }
}
