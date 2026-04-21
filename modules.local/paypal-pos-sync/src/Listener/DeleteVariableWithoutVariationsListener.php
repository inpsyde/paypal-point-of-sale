<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Listener;

use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantTransferInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\MapRecordCreator;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use WC_Product_Variable;

/**
 * Detects a Variable Product without Variations and deletes the Product on Update
 *
 * @see ProductEventListenerRegistry::onChange()
 */
class DeleteVariableWithoutVariationsListener
{
    private OneToOneMapInterface|MapRecordCreator $productMap;

    private OneToOneMapInterface|MapRecordCreator $variantMap;

    private Products $productsClient;

    private LoggerInterface $logger;

    /**
     * DeleteVariableProductWithoutVariationsListener constructor.
     *
     * @param OneToOneMapInterface $productMap
     * @param OneToOneMapInterface $variantMap
     * @param Products $productsClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        OneToOneMapInterface $productMap,
        OneToOneMapInterface $variantMap,
        Products $productsClient,
        LoggerInterface $logger
    ) {

        $this->productMap = $productMap;
        $this->variantMap = $variantMap;
        $this->productsClient = $productsClient;
        $this->logger = $logger;
    }

    public function __invoke(WC_Product_Variable $new, WC_Product_Variable $old): void
    {
        /**
         * It looks like this is not technically 'the correct' function to call since
         * it delegates to $product->get_available_variation() which is documented
         * to be used on the cart page and simply returns an array of variation data instead
         * of WC_Product_Variation[].
         *
         * However, the function does exactly what we want: Tell us if there is a
         * single variation that can currently be bought.
         */
        if (!empty((array) $new->get_available_variations())) {
            return;
        }

        if (!$this->productExistsRemotely($new)) {
            return;
        }

        $productId = (int) $new->get_id();

        $uuid = $this->uuidFromId($productId);

        /**
         * We now know that a remote products exists AND we just got rid of all Variations.
         * This means that we can no longer use our LOCAL data to clean up the id-map since
         * the variable product can no longer provide us with the necessary local IDs.
         *
         * Therefore, we need to make a round-trip to iZ and fetch the variant UUIDs which
         * we can then use to query our local IDs.
         *
         * In case you're wondering why we're not simply calling $old->get_available_variations():
         * Variations are often deleted in separate AJAX-calls, so when our listener triggers,
         * both new and old states will be devoid of the deleted variations
         */
        $variantUuids = $this->fetchVariantsFromZettle($uuid);

        try {
            // Delete the product at Zettle
            $this->productsClient->delete($uuid);
            // Unmap Product locally
            $this->dissolveProductMapping($uuid, $productId);

            if (!empty($variantUuids)) {
                $this->dissolveVariantsMapping($variantUuids);
            }
        } catch (ZettleRestException $exception) {
            $this->logger->warning($exception);
        }
    }

    /**
     * @param WC_Product_Variable $product
     *
     * @return bool
     */
    protected function productExistsRemotely(WC_Product_Variable $product): bool
    {
        $uuid = $this->uuidFromId((int) $product->get_id());

        if ($uuid === null) {
            return false;
        }

        $existingProduct = $this->fetchProductWithUuid($uuid);

        return $existingProduct !== null;
    }

    /**
     * @param string $uuid
     *
     * @param int $productId
     *
     * @return bool
     */
    protected function dissolveProductMapping(string $uuid, int $productId): bool
    {
        try {
            $this->productMap->deleteRecord($productId, $uuid);
        } catch (IdNotFoundException $exception) {
            return false;
        }

        return true;
    }

    /**
     * @param string[] $uuids
     *
     * @return bool
     */
    protected function dissolveVariantsMapping(array $uuids): bool
    {
        $unresolvedVariants = [];

        foreach ($uuids as $uuid) {
            $variantId = $this->variantIdFromUuid($uuid);

            if ($variantId === null) {
                $unresolvedVariants[] = $uuid;

                continue;
            }

            $success = $this->deleteVariant($uuid, $variantId);

            if (!$success) {
                $unresolvedVariants[] = $uuid;

                continue;
            }
        }

        if (!empty($unresolvedVariants)) {
            $variantIdStrings = implode(', ', $unresolvedVariants);

            $this->logger->error("Couldn't unresolved the following Variants: {$variantIdStrings}");

            return false;
        }

        return true;
    }

    /**
     * @param int $productId
     *
     * @return string|null
     */
    private function uuidFromId(int $productId): ?string
    {
        try {
            $uuid = $this->productMap->remoteId($productId);
        } catch (IdNotFoundException $exception) {
            return null;
        }

        return $uuid;
    }

    /**
     * @param string $uuid
     *
     * @return int|null
     */
    private function variantIdFromUuid(string $uuid): ?int
    {
        try {
            $variantId = $this->variantMap->localId($uuid);
        } catch (IdNotFoundException $exception) {
            return null;
        }

        return $variantId;
    }

    /**
     * @param string $uuid
     * @param int $variantId
     *
     * @return bool
     */
    private function deleteVariant(string $uuid, int $variantId): bool
    {
        try {
            $this->variantMap->deleteRecord($variantId, $uuid);
        } catch (IdNotFoundException $exception) {
            return false;
        }

        return true;
    }

    /**
     * @param string $uuid
     *
     * @return ProductInterface|null
     */
    private function fetchProductWithUuid(string $uuid): ?ProductInterface
    {
        try {
            $existingProduct = $this->productsClient->read($uuid);
        } catch (ZettleRestException $exception) {
            return null;
        }

        return $existingProduct;
    }

    /**
     * Fetch Variations from Zettle Api
     *
     * @param string $uuid
     *
     * @return string[] Variant UUIDs
     */
    private function fetchVariantsFromZettle(string $uuid): array
    {
        $variantUuids = [];

        $product = $this->fetchProductWithUuid($uuid);

        if ($product === null) {
            return $variantUuids;
        }

        if (empty($product->variants()->all())) {
            return $variantUuids;
        }

        foreach ($product->variants()->all() as $variant) {
            if (!$variant instanceof VariantTransferInterface) {
                continue;
            }

            $variantUuids[] = (string) $variant->uuid();
        }

        return $variantUuids;
    }
}
