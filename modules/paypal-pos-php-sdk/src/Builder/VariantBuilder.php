<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationType;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation\Presentation;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\Variant;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOptionCollection;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Uuid\Uuid;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Barcode\Repository\BarcodeRetrieverInterface;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;
class VariantBuilder implements BuilderInterface
{
    /**
     * @var callable
     */
    private $onException;
    private string $taxationType;
    private BarcodeRetrieverInterface $barcodeRetriever;
    private bool $priceSyncEnabled;
    public function __construct(callable $onException, string $taxationType, bool $priceSyncEnabled, BarcodeRetrieverInterface $barcodeRetriever)
    {
        $this->onException = $onException;
        $this->taxationType = $taxationType;
        $this->priceSyncEnabled = $priceSyncEnabled;
        $this->barcodeRetriever = $barcodeRetriever;
    }
    /**
     * @param string $className
     * @param mixed $wcProduct
     * @param BuilderInterface|null $builder
     *
     * @return VariantInterface
     * @throws BuilderException
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     * phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
     */
    public function build(string $className, $wcProduct, ?BuilderInterface $builder = null)
    {
        assert($wcProduct instanceof WC_Product);
        $imageId = (int) $wcProduct->get_image_id();
        $presentation = null;
        if ($imageId) {
            try {
                $presentation = $builder->build(Presentation::class, $wcProduct);
            } catch (BuilderException $exception) {
                ($this->onException)($exception);
            }
        }
        $options = new VariantOptionCollection();
        if ($wcProduct instanceof WC_Product_Variation && !empty((array) $wcProduct->get_attributes())) {
            $options = $builder->build(VariantOptionCollection::class, $wcProduct);
        }
        $vat = $this->taxationType === TaxationType::VAT && $this->priceSyncEnabled ? $builder->build(Vat::class, $wcProduct) : null;
        $barcode = $this->barcodeRetriever->get($wcProduct);
        $variant = new Variant((string) Uuid::fromWcProduct($wcProduct), $wcProduct->get_name(), $wcProduct->get_description(), $wcProduct->get_sku(), $this->stockQuantity($wcProduct), $builder->build(Price::class, $wcProduct), $vat, $presentation, $options, null, null, $barcode);
        return $variant;
    }
    /**
     * @param WC_Product $product
     *
     * @return int
     */
    private function stockQuantity(WC_Product $product): int
    {
        if ($product instanceof WC_Product_Variable) {
            /**
             * @psalm-suppress RedundantCastGivenDocblockType
             */
            if (!$this->hasStockManagingVariations($product) || (bool) !$product->managing_stock()) {
                return 0;
            }
        }
        return (int) $product->get_stock_quantity();
    }
    /**
     * @param WC_Product_Variable $product
     *
     * @return bool
     */
    private function hasStockManagingVariations(WC_Product_Variable $product): bool
    {
        $childrenWithStock = [];
        foreach ($product->get_visible_children() as $variationId) {
            $variation = wc_get_product($variationId);
            if (!$variation) {
                continue;
            }
            if (!$variation->is_purchasable()) {
                continue;
            }
            if (!$variation->managing_stock() || $variation->managing_stock() === 'parent') {
                continue;
            }
            $childrenWithStock[] = $variationId;
        }
        return !empty($childrenWithStock);
    }
}
