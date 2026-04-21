<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductDebug\Cli;

use Inpsyde\WcProductContracts\ProductState;
use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use Syde\PayPal\PointOfSale\Sync\Status\StatusCodeMatcher;
use Syde\PayPal\PointOfSale\Sync\Status\SyncStatusCodes;
use Syde\PayPal\PointOfSale\Sync\Validator\ProductValidator;
use Throwable;
use WC_Product;
use WP_CLI;

use function WP_CLI\Utils\format_items;

class ProductsCommand
{
    /**
     * @var string[]
     */
    private array $productTypeWhitelist;

    private OneToOneMapInterface $productMap;

    private BuilderInterface $builder;

    private Products $productsClient;

    private ProductValidator $productValidator;

    private StatusCodeMatcher $statusCodeMatcher;

    public function __construct(
        array $productTypeWhitelist,
        OneToOneMapInterface $productMap,
        BuilderInterface $builder,
        Products $productsClient,
        ProductValidator $productValidator,
        StatusCodeMatcher $statusCodeMatcher
    ) {

        $this->productTypeWhitelist = $productTypeWhitelist;
        $this->productMap = $productMap;
        $this->builder = $builder;
        $this->productsClient = $productsClient;
        $this->productValidator = $productValidator;
        $this->statusCodeMatcher = $statusCodeMatcher;
    }

    /**
     * Diff the synced products with the not synced ones
     *
     * ## OPTIONS
     *
     * ## EXAMPLES
     *
     *     wp zettle products validate
     *
     * @when after_wp_load
     */
    public function validate(array $args, array $assocArgs): void
    {
        $productIds = wc_get_products(
            [
                'return' => 'ids',
                'limit' => -1,
                'status' => ProductState::PUBLISH,
                'type' => $this->productTypeWhitelist,
            ]
        );

        if (!$productIds) {
            WP_CLI::line('No Products found.');

            return;
        }

        $unSyncableProducts = $this->processProducts($productIds);

        if (!$unSyncableProducts) {
            WP_CLI::line('No unsyncable Products found. All Products are valid and synced.');

            return;
        }

        format_items(
            'table',
            $unSyncableProducts,
            [
                'ID',
                'Name',
                'Errors',
            ]
        );
    }

    /**
     * @param int[] $productIds
     *
     * @return array
     */
    protected function processProducts(array $productIds): array
    {
        $notSyncedProducts = [];

        foreach ($productIds as $productId) {
            if ($this->productExists($productId)) {
                continue;
            }

            $statusCodes = $this->productValidator->validateWithLocalDBCheck($productId);

            if (
                !in_array(SyncStatusCodes::NOT_SYNCED, $statusCodes, true)
                || in_array(SyncStatusCodes::SYNCED, $statusCodes, true)
            ) {
                continue;
            }

            $problems = $this->statusCodeMatcher->match($statusCodes);

            $product = wc_get_product($productId);

            $notSyncedProducts[] = [
                'ID' => $productId,
                'Name' => (string) $product->get_name(),
                'Errors' => $this->renderProblems(
                    $problems,
                    $this->trySync($product)
                ),
            ];
        }

        return $notSyncedProducts;
    }

    /**
     * @param array<string, string> $problems
     * @param Throwable[] $syncExceptions
     *
     * @return string
     */
    protected function renderProblems(array $problems, array $syncExceptions): string
    {
        $output = '';

        if (!empty($problems)) {
            $output = implode(', ', $problems);

            if (!empty($syncExceptions)) {
                $output .= '. Validator Errors: ';
                $output .= implode(', ', $this->processExceptions($syncExceptions));
            }
        }

        return $output;
    }

    /**
     * @param WC_Product $wcProduct
     *
     * @return Throwable[]
     */
    protected function trySync(WC_Product $wcProduct): array
    {
        $exceptions = [];

        try {
            $product = $this->builder->build(ProductInterface::class, $wcProduct);

            $this->productsClient->create($product, false);
        } catch (Throwable $throwable) {
            $exceptions[] = $throwable;
        }

        // Try to delete the product
        if (isset($product) && $this->productExists((int) $wcProduct->get_id())) {
            try {
                $this->productsClient->delete($product->uuid(), false);
            } catch (Throwable $throwable) {
            }
        }

        return $exceptions;
    }

    /**
     * @param array $exceptions
     * @param int $limit
     *
     * @return array
     */
    private function processExceptions(array $exceptions, int $limit = 10): array
    {
        $messages = [];
        $index = 0;

        foreach ($exceptions as $exception) {
            if (!$exception instanceof Throwable) {
                continue;
            }

            // phpcs:disable WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
            while ($exception = $exception->getPrevious()) {
                if ($index > $limit) {
                    break;
                }

                $message = $exception->getMessage();

                // Strip out the not really helpful exception messages
                if (strpos($message, 'from the given payload') !== false) {
                    continue;
                }

                if (strpos($message, 'VariantOptionDefinitions') !== false) {
                    $message = str_replace('VariantOptionDefinitions', 'Attributes', $message);
                }

                if (strpos($message, 'Definition') !== false) {
                    $message = str_replace('Definition', 'Attribute', $message);
                }

                $messages[] = $message;
                $index++;
            }
        }

        return $messages;
    }

    /**
     * @param int $productId
     *
     * @return bool
     */
    private function productExists(int $productId): bool
    {
        try {
            $exists = $this->productMap->remoteId($productId);

            return (bool) $exists;
        } catch (IdNotFoundException $exception) {
            return false;
        }
    }
}
