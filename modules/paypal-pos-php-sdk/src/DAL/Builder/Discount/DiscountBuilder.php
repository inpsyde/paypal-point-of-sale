<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Discount;

use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\AbstractBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Image\ImageCollectionBuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Price\PriceBuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Discount\Discount;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Discount\DiscountFactory;
class DiscountBuilder extends AbstractBuilder implements DiscountBuilderInterface
{
    /**
     * @var DiscountFactory
     */
    private $discountFactory;
    /**
     * @var ImageCollectionBuilderInterface
     */
    private $imageCollectionBuilder;
    /**
     * @var PriceBuilderInterface
     */
    private $priceBuilder;
    /**
     * DiscountBuilder constructor.
     *
     *  @param DiscountFactory $discountFactory
     * @param ImageCollectionBuilderInterface $imageCollectionBuilder
     * @param PriceBuilderInterface $priceBuilder
     */
    public function __construct(DiscountFactory $discountFactory, ImageCollectionBuilderInterface $imageCollectionBuilder, PriceBuilderInterface $priceBuilder)
    {
        $this->discountFactory = $discountFactory;
        $this->imageCollectionBuilder = $imageCollectionBuilder;
        $this->priceBuilder = $priceBuilder;
    }
    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): Discount
    {
        return $this->build($data);
    }
    /**
     * @inheritDoc
     */
    public function createDataArray(Discount $discount): array
    {
        $data = ['uuid' => $discount->uuid(), 'name' => $discount->name()];
        if ($discount->imageCollection()) {
            $data['imageLookupKeys'] = $this->imageCollectionBuilder->createDataArray($discount->imageCollection());
        }
        if ($discount->amount()) {
            $data['amount'] = $this->priceBuilder->createDataArray($discount->amount(), 'amount');
        }
        if ($discount->percentage()) {
            $data['percentage'] = $discount->percentage();
        }
        if ($discount->externalReference()) {
            $data['externalReference'] = $discount->externalReference();
        }
        if ($discount->etag()) {
            $data['etag'] = $discount->etag();
        }
        if ($discount->updatedAt()) {
            $data['updated'] = $discount->updatedAt()->format('Y-m-d');
        }
        if ($discount->updatedBy()) {
            $data['updatedBy'] = (string) $discount->updatedBy();
        }
        if ($discount->createdAt()) {
            $data['created'] = $discount->createdAt()->format('Y-m-d');
        }
        return $data;
    }
    /**
     * @param array $data
     *
     * @return Discount
     *
     * @throws Exception
     */
    private function build(array $data): Discount
    {
        $images = $data['imageLookupKeys'] ? $this->imageCollectionBuilder->buildFromArray($data['imageLookupKeys']) : null;
        $amount = $data['amount'] ? $this->priceBuilder->buildFromArray($data['amount'], 'amount') : null;
        $percentage = $data['percentage'] ? (float) $data['percentage'] : null;
        return $this->discountFactory->create($data['uuid'], $data['name'], $data['description'], $images, $amount, $percentage, $this->getDataFromKey('externalReference', $data), $this->getDataFromKey('etag', $data), $this->getDataFromKey('updatedAt', $data), $this->getDataFromKey('updatedBy', $data), $this->getDataFromKey('createdAt', $data));
    }
}
