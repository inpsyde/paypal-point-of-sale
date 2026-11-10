<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Filter;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\LazyVariant;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\StockQuantityAwareInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\Variant;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantTransferInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map\MapRecordCreator;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use WC_Product;
/**
 * Searches our ID-Map for an existing record and updates the Variant's UUID with it.
 * If none is found, it will wrap the Variant into a LazyVariant that will automatically
 * create a new entry as soon as required (when accessing its UUID)
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
 * phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
 */
class VariantConnectionFilter implements FilterInterface
{
    private OneToOneMapInterface&MapRecordCreator $idMap;
    /**
     * @var array<int, VariantInterface>
     */
    private array $lazyPool = [];
    public function __construct(OneToOneMapInterface&MapRecordCreator $idMap)
    {
        $this->idMap = $idMap;
        add_action('paypal-pos.clear-product-cache', function () {
            $this->lazyPool = [];
        });
    }
    public function accepts(mixed $entity, mixed $payload): bool
    {
        return $entity instanceof Variant and $payload instanceof WC_Product;
    }
    /**
     * @inheritDoc
     */
    public function filter(mixed $variant, mixed $wcProduct): object
    {
        assert($wcProduct instanceof WC_Product);
        assert($variant instanceof VariantTransferInterface);
        assert($variant instanceof StockQuantityAwareInterface);
        $wcProductId = (int) $wcProduct->get_id();
        try {
            /**
             * Try to fetch the existing UUID for the current Variant.
             * Then update the entity's uuid property with it
             */
            $remoteId = $this->idMap->remoteId($wcProductId);
            $variant->setUuid($remoteId);
        } catch (IdNotFoundException $exception) {
            /**
             * On failure, fetch an instance of LazyVariant so it can sort itself out later
             */
            $variant = $this->getLazyVariant($wcProductId, $variant);
        }
        return $variant;
    }
    private function getLazyVariant(int $localId, VariantTransferInterface&StockQuantityAwareInterface $variant): VariantInterface
    {
        if (!isset($this->lazyPool[$localId])) {
            $this->lazyPool[$localId] = new LazyVariant($localId, $variant, $this->idMap);
        }
        return $this->lazyPool[$localId];
    }
}
