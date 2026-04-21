<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Balance;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance\ProductBalance;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Balance\ProductBalanceFactory;
class ProductBalanceBuilder implements ProductBalanceBuilderInterface
{
    private ProductBalanceFactory $productBalanceFactory;
    private LocationBalanceCollectionBuilder $locationBalanceCollectionBuilder;
    /**
     * ProductBalanceBuilder constructor.
     *
     * @param ProductBalanceFactory $productBalanceFactory
     * @param LocationBalanceCollectionBuilderInterface $locationBalanceCollectionBuilder
     */
    public function __construct(ProductBalanceFactory $productBalanceFactory, LocationBalanceCollectionBuilderInterface $locationBalanceCollectionBuilder)
    {
        $this->productBalanceFactory = $productBalanceFactory;
        $this->locationBalanceCollectionBuilder = $locationBalanceCollectionBuilder;
    }
    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): ProductBalance
    {
        return $this->build($data);
    }
    /**
     * @inheritDoc
     */
    public function createDataArray(ProductBalance $productBalance): array
    {
        return ['locationUuid' => (string) $productBalance->locationUuid(), 'variants' => $this->locationBalanceCollectionBuilder->createDataArray($productBalance->variants())];
    }
    /**
     * @param array $data
     *
     * @return ProductBalance
     */
    private function build(array $data): ProductBalance
    {
        return $this->productBalanceFactory->create($data['locationUuid'], $this->locationBalanceCollectionBuilder->buildFromArray($data));
    }
}
