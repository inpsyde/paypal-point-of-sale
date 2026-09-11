<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync;

use Exception;
use Inpsyde\Queue\Processor\ProcessorBuilder;
use Inpsyde\Queue\Processor\QueueProcessor;
use Inpsyde\Queue\Queue\Job\Job;
use Psr\Container\ContainerInterface as C;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Tax\TaxRate;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Organization\OrganizationProvider;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidationErrorCodes;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\VariantOptionDefinitionsValidator;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\VariantOptionValidator;
use Syde\PayPal\PointOfSale\Sync\Cli\ExcludeCommand;
use Syde\PayPal\PointOfSale\Sync\Cli\ExportCommand;
use Syde\PayPal\PointOfSale\Sync\Cli\ResetCommand;
use Syde\PayPal\PointOfSale\Sync\Cli\SyncCommand;
use Syde\PayPal\PointOfSale\Sync\Cli\UnlinkCommand;
use Syde\PayPal\PointOfSale\Sync\Job\DeleteProductJob;
use Syde\PayPal\PointOfSale\Sync\Job\EnqueueProductSyncJob;
use Syde\PayPal\PointOfSale\Sync\Job\ExportProductJob;
use Syde\PayPal\PointOfSale\Sync\Job\ReExportProductJob;
use Syde\PayPal\PointOfSale\Sync\Job\SetInventoryTrackingJob;
use Syde\PayPal\PointOfSale\Sync\Job\SetStateJob;
use Syde\PayPal\PointOfSale\Sync\Job\SyncStockJob;
use Syde\PayPal\PointOfSale\Sync\Job\UnlinkImages;
use Syde\PayPal\PointOfSale\Sync\Job\UnlinkProductJob;
use Syde\PayPal\PointOfSale\Sync\Job\UnlinkVariantJob;
use Syde\PayPal\PointOfSale\Sync\Job\WipeRemoteProductsJob;
use Syde\PayPal\PointOfSale\Sync\Listener\AllPropsListener;
use Syde\PayPal\PointOfSale\Sync\Listener\DeleteVariableWithoutVariationsListener;
use Syde\PayPal\PointOfSale\Sync\Listener\DePublishListener;
use Syde\PayPal\PointOfSale\Sync\Listener\NotSyncableListener;
use Syde\PayPal\PointOfSale\Sync\Listener\ParentStockVariationListener;
use Syde\PayPal\PointOfSale\Sync\Listener\SimpleManageStockListener;
use Syde\PayPal\PointOfSale\Sync\Listener\SimpleToVariableTypeChangeListener;
use Syde\PayPal\PointOfSale\Sync\Listener\StockQuantityListener;
use Syde\PayPal\PointOfSale\Sync\Listener\StockSyncOnVariationPublishListener;
use Syde\PayPal\PointOfSale\Sync\Listener\VariableManageStockListener;
use Syde\PayPal\PointOfSale\Sync\Listener\VariableToSimpleTypeChangeListener;
use Syde\PayPal\PointOfSale\Sync\Listener\VariationDeleteListener;
use Syde\PayPal\PointOfSale\Sync\Listener\VariationManageStockListener;
use Syde\PayPal\PointOfSale\Sync\Status\StatusCodeMatcher;
use Syde\PayPal\PointOfSale\Sync\Status\SyncStatusCodes;
use Syde\PayPal\PointOfSale\Sync\Validator\ProductValidator;
use Throwable;
use WC_Product_Variation;

$job = static function (string $type): string {
    return "paypal-pos.job.{$type}";
};

return [
    'paypal-pos.sync.status.map' => static function (C $container): array {
        return [
            SyncStatusCodes::NO_VALID_PRODUCT_ID => __('Invalid product ID', 'paypal-point-of-sale'),
            SyncStatusCodes::PRODUCT_NOT_FOUND => __('Product not found', 'paypal-point-of-sale'),

            SyncStatusCodes::SYNCED => __('Synced', 'paypal-point-of-sale'),
            SyncStatusCodes::NOT_SYNCED => __('Not synced', 'paypal-point-of-sale'),
            SyncStatusCodes::SYNCABLE => __('Syncable', 'paypal-point-of-sale'),
            SyncStatusCodes::NOT_SYNCABLE => __('Not syncable', 'paypal-point-of-sale'),

            SyncStatusCodes::UNSUPPORTED_PRODUCT_TYPE => __('Unsupported product type', 'paypal-point-of-sale'),
            SyncStatusCodes::EXCLUDED => __('Excluded', 'paypal-point-of-sale'),
            SyncStatusCodes::UNPUBLISHED => __('Not published', 'paypal-point-of-sale'),
            SyncStatusCodes::UNPURCHASABLE => __('Not purchasable', 'paypal-point-of-sale'),
            SyncStatusCodes::INVISIBLE => __('Not visible', 'paypal-point-of-sale'),

            ValidationErrorCodes::NO_VARIANTS => __('No variations', 'paypal-point-of-sale'),
            ValidationErrorCodes::TOO_MANY_VARIANTS => __('Too many variations', 'paypal-point-of-sale'),
            ValidationErrorCodes::NO_VARIANT_OPTIONS => __('No variation attributes', 'paypal-point-of-sale'),
            ValidationErrorCodes::TOO_MANY_VARIANT_OPTIONS => sprintf(
                /* translators: %1$d max attributes amount for variation */
                __(
                    'Too many variation attributes, more than %1$d',
                    'paypal-point-of-sale'
                ),
                VariantOptionDefinitionsValidator::MAXIMUM_DEFINITIONS_AMOUNT
            ),
            ValidationErrorCodes::TOO_SHORT_VARIANT_NAME => __('Empty variation attribute value', 'paypal-point-of-sale'),
            ValidationErrorCodes::TOO_LONG_VARIANT_NAME => sprintf(
            /* translators: %1$d max variation attribute value length (e.g. 30) */
                __(
                    'Too long variation attribute value, more than %1$d letters',
                    'paypal-point-of-sale'
                ),
                VariantOptionValidator::MAX_NAME_LENGTH
            ),
            ValidationErrorCodes::DIFFERING_VARIANT_TAXES => __('Variations have differing taxes', 'paypal-point-of-sale'),

            ValidationErrorCodes::TOO_BIG_STOCK => sprintf(
            /* translators: %1$d max stock quantity allowed in Zettle (e.g. 99999) */
                __(
                    'Stock cannot be greater than %1$d',
                    'paypal-point-of-sale'
                ),
                $container->get('paypal-pos.sdk.validator.stock.max')
            ),

            ValidationErrorCodes::TAX_RATE_NOT_FOUND => __('No tax rate', 'paypal-point-of-sale'),

            SyncStatusCodes::UNDEFINED => __('Undefined status code, check WC logs', 'paypal-point-of-sale'),
        ];
    },
    $job(EnqueueProductSyncJob::TYPE) => static function (C $container): Job {
        $createJob = $container->get('inpsyde.queue.create-job-record');
        assert(is_callable($createJob));

        return new EnqueueProductSyncJob(
            $container->get('paypal-pos.sync.allowed-product-types'),
            $createJob,
            $container->get('paypal-pos.sync.product.sync-active-for-id')
        );
    },
    $job(ExportProductJob::TYPE) => static function (C $container): Job {
        return new ExportProductJob(
            $container->get('paypal-pos.sdk.repository.woocommerce.product'),
            $container->get('paypal-pos.sdk.builder'),
            $container->get('paypal-pos.sdk.api.products'),
            $container->get('paypal-pos.sdk.id-map.product'),
            $container->get('paypal-pos.sync.queue-processor.job.factory')(),
            $container->get('inpsyde.queue.create-job-record'),
            $container->get('paypal-pos.sync.product.sync-active-for-id'),
            $container->get('paypal-pos.sync.product.status')
        );
    },
    $job(DeleteProductJob::TYPE) => static function (C $container): Job {
        return new DeleteProductJob(
            $container->get('paypal-pos.sdk.id-map.product'),
            $container->get('paypal-pos.sdk.api.products'),
            $container->get('inpsyde.queue.create-job-record')
        );
    },
    $job(WipeRemoteProductsJob::TYPE) => static function (C $container): Job {
        return new WipeRemoteProductsJob(
            $container->get('paypal-pos.sdk.api.products')
        );
    },
    $job(UnlinkProductJob::TYPE) => static function (C $container): Job {
        return new UnlinkProductJob(
            $container->get('paypal-pos.sdk.id-map.product'),
            $container->get('paypal-pos.sdk.id-map.variant'),
            $container->get('paypal-pos.sdk.repository.woocommerce.product')
        );
    },
    $job(UnlinkVariantJob::TYPE) => static function (C $container): Job {
        return new UnlinkVariantJob(
            $container->get('paypal-pos.sdk.id-map.variant')
        );
    },
    $job(SetStateJob::TYPE) => static function (C $container): Job {
        $setState = $container->get('paypal-pos.onboarding.set-state');
        assert(is_callable($setState));

        return new SetStateJob($setState);
    },
    $job(SyncStockJob::TYPE) => static function (C $container) use ($job): Job {
        return new SyncStockJob(
            $container->get('paypal-pos.sdk.api.inventory'),
            $container->get('paypal-pos.sdk.builder'),
            $container->get('paypal-pos.sdk.id-map.variant'),
            $container->get('paypal-pos.sdk.validator.stock.max'),
            $container->get($job(SetInventoryTrackingJob::TYPE))
        );
    },
    $job(SetInventoryTrackingJob::TYPE) => static function (C $container): Job {
        return new SetInventoryTrackingJob(
            $container->get('paypal-pos.sdk.repository.woocommerce.product'),
            $container->get('paypal-pos.sdk.api.inventory'),
            $container->get('paypal-pos.sdk.builder')
        );
    },
    $job(UnlinkImages::TYPE) => static function (C $container): Job {
        return new UnlinkImages(
            $container->get('paypal-pos.sdk.id-map.image'),
            $container->get('paypal-pos.sdk.repository.woocommerce.product')
        );
    },
    $job(ReExportProductJob::TYPE) => static function (C $container): Job {
        return new ReExportProductJob(
            $container->get('paypal-pos.sdk.repository.zettle.product'),
            $container->get('paypal-pos.sdk.repository.woocommerce.product'),
            $container->get('paypal-pos.sdk.id-map.variant'),
            $container->get('inpsyde.queue.create-job-record')
        );
    },
    /**
     * Configure 2 separate Queue processors.
     * It is important that these are separate instances, even if they have the same configuration
     */
    'paypal-pos.sync.queue-processor.cli' =>
        static function (C $container): QueueProcessor {
            $processorBuilder = new ProcessorBuilder(
                $container->get('inpsyde.queue.factory')
            );

            return $processorBuilder
                ->withLogger($container->get('inpsyde.queue.logger'))
                ->withExceptionHandler($container->get('paypal-pos.sync.queue-processor.cli.exception-handler'))
                ->withMaxRetriesCount($container->get('inpsyde.queue.failed.retry.count'))
                ->build();
        },
    'paypal-pos.sync.queue-processor.job.factory' =>
        static function (C $container): callable {
            return static function () use ($container): QueueProcessor {
                $processorBuilder = new ProcessorBuilder(
                    $container->get('inpsyde.queue.factory')
                );

                return $processorBuilder
                    ->withLogger($container->get('inpsyde.queue.logger'))
                    ->withMaxRetriesCount($container->get('inpsyde.queue.failed.retry.count'))
                    ->build();
            };
        },
    'paypal-pos.sync.queue-processor.cli.exception-handler' => static function (): callable {
        return static function (Throwable $exception): void {
            // phpcs:ignore WordPress.Security.EscapeOutput
            echo $exception;
        };
    },
    'paypal-pos.sync.cli.sync-product' => static function (C $container): SyncCommand {
        return new SyncCommand(
            $container->get('paypal-pos.sync.queue-processor.cli'),
            $container->get('inpsyde.queue.create-job-record')
        );
    },
    'paypal-pos.sync.cli.unlink-product' => static function (C $container) use ($job): UnlinkCommand {
        return new UnlinkCommand(
            $container->get($job(UnlinkProductJob::TYPE)),
            $container->get($job(UnlinkVariantJob::TYPE)),
            $container->get($job(UnlinkImages::TYPE)),
            $container->get('inpsyde.queue.logger')
        );
    },
    'paypal-pos.sync.cli.export' => static function (C $container): ExportCommand {
        return new ExportCommand(
            $container->get('paypal-pos.sync.queue-processor.cli'),
            $container->get('inpsyde.queue.create-job-record')
        );
    },
    'paypal-pos.sync.cli.reset' => static function (C $container) use ($job): ResetCommand {
        return new ResetCommand(
            $container->get($job(WipeRemoteProductsJob::TYPE)),
            $container->get('inpsyde.queue.logger'),
            $container->get('paypal-pos.sdk.dal.table'),
            $container->get('inpsyde.queue.table')
        );
    },
    'paypal-pos.sync.cli.exclude' => static function (C $container) use ($job): ExcludeCommand {
        return new ExcludeCommand(
            $container->get($job(DeleteProductJob::TYPE)),
            $container->get($job(UnlinkProductJob::TYPE)),
            $container->get('inpsyde.queue.logger')
        );
    },
    'paypal-pos.sync.enqueue-initial-sync' => static function (C $container): callable {
        return static function () use ($container): void {
            $enqueue = $container->get('inpsyde.queue.enqueue-job');
            assert(is_callable($enqueue));
            $enqueue(EnqueueProductSyncJob::TYPE);
        };
    },
    'paypal-pos.sync.allowed-product-types' => static function (C $container): array {
        return [
            'simple',
            'variable',
        ];
    },
    'paypal-pos.sync.price-sync-enabled' => static function (C $container): bool {
        $settings = $container->get('paypal-pos.settings');
        assert($settings instanceof C);

        return $settings->has('sync_price_strategy')
            && $settings->get('sync_price_strategy') === PriceSyncMode::ENABLED;
    },
    'paypal-pos.sync.validator.product' =>
        static function (C $container): ProductValidator {
            return new ProductValidator(
                $container->get('paypal-pos.sdk.id-map.product'),
                $container->get('paypal-pos.product-settings.term.excluded'),
                (array) $container->get('paypal-pos.sync.allowed-product-types'),
                $container->get('paypal-pos.init-possible'),
                $container->get('paypal-pos.sdk.builder'),
                $container->get('inpsyde.debug.exception-handler')
            );
        },
    'paypal-pos.sync.status.matcher' =>
        static function (C $container): StatusCodeMatcher {
            return new StatusCodeMatcher(
                $container->get('paypal-pos.sync.status.map')
            );
        },
    'paypal-pos.sync.product.sync-active-for-id' => static function (C $container): callable {
        return static function (int $productId) use ($container): bool {
            $productValidator = $container->get('paypal-pos.sync.validator.product');
            $isValid = $productValidator->validate($productId);
            /**
             * Recurse into the parent product if we have a Variation at our hands
             */
            $product = $product = wc_get_product($productId);
            if ($product instanceof WC_Product_Variation) {
                // Grab the "full" service again so we gather all potential extensions with it
                $self = $container->get('paypal-pos.sync.product.sync-active-for-id');
                return $self($product->get_parent_id());
            }

            return empty($isValid);
        };
    },
    'paypal-pos.sync.product.status' => static function (C $container): callable {
        return static function (int $productId) use ($container): array {
            $productValidator = $container->get('paypal-pos.sync.validator.product');
            $productStatusMatcher = $container->get('paypal-pos.sync.status.matcher');

            return $productStatusMatcher->match(
                $productValidator->validate($productId)
            );
        };
    },
    'paypal-pos.sync.editor.action' => static function (): callable {
        return static function (): bool {
            /**
             * We're saving a product via editor.
             * Any changes here should have been in $updatedProperties
             *
             * true if a post was updated via editor
             */
            return doing_action('woocommerce_process_product_meta');
        };
    },
    /**
     * WooCommerce Lifecycle Event Listeners
     */
    'paypal-pos.sync.listener.depublish' => static function (C $container): DePublishListener {
        return new DePublishListener(
            $container->get('paypal-pos.sdk.id-map.product'),
            $container->get('inpsyde.queue.enqueue-job')
        );
    },
    'paypal-pos.sync.listener.publish.variation' =>
        static function (C $container): StockSyncOnVariationPublishListener {
            return new StockSyncOnVariationPublishListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('paypal-pos.sync.product.sync-active-for-id')
            );
        },
    'paypal-pos.sync.listener.delete.variation' =>
        static function (C $container): VariationDeleteListener {
            return new VariationDeleteListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('paypal-pos.sync.product.sync-active-for-id')
            );
        },
    'paypal-pos.sync.listener.type-change.simple-to-variable' =>
        static function (C $container): VariableToSimpleTypeChangeListener {
            return new VariableToSimpleTypeChangeListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('paypal-pos.sync.product.sync-active-for-id')
            );
        },
    'paypal-pos.sync.listener.type-change.variable-to-simple' =>
        static function (C $container): SimpleToVariableTypeChangeListener {
            return new SimpleToVariableTypeChangeListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('paypal-pos.sync.product.sync-active-for-id')
            );
        },
    'paypal-pos.sync.listener.all-props' => static function (C $container): AllPropsListener {
        return new AllPropsListener(
            $container->get('inpsyde.queue.enqueue-job'),
            $container->get('paypal-pos.sync.product.sync-active-for-id')
        );
    },
    'paypal-pos.sync.listener.not-syncable' =>
        static function (C $container): NotSyncableListener {
            return new NotSyncableListener(
                $container->get('paypal-pos.sync.listener.depublish'),
                $container->get('paypal-pos.sync.product.sync-active-for-id')
            );
        },
    'paypal-pos.sync.listener.variation.parent-stock' =>
        static function (C $container): ParentStockVariationListener {
            return new ParentStockVariationListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('paypal-pos.sync.product.sync-active-for-id')
            );
        },
    'paypal-pos.sync.listener.stock-quantity' =>
        static function (C $container): StockQuantityListener {
            return new StockQuantityListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('paypal-pos.sync.product.sync-active-for-id')
            );
        },
    'paypal-pos.sync.listener.manage-stock.simple' =>
        static function (C $container): SimpleManageStockListener {
            return new SimpleManageStockListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('paypal-pos.sync.product.sync-active-for-id')
            );
        },
    'paypal-pos.sync.listener.manage-stock.variable' =>
        static function (C $container): VariableManageStockListener {
            return new VariableManageStockListener(
                $container->get('inpsyde.queue.enqueue-job'),
                $container->get('paypal-pos.sync.product.sync-active-for-id')
            );
        },
    'paypal-pos.sync.listener.manage-stock.variation' =>
        static function (C $container): VariationManageStockListener {
            return new VariationManageStockListener(
                $container->get('paypal-pos.sync.listener.manage-stock.variable')
            );
        },
    'paypal-pos.sync.listener.delete-variable-without-variation' =>
        static function (C $container): DeleteVariableWithoutVariationsListener {
            return new DeleteVariableWithoutVariationsListener(
                $container->get('paypal-pos.sdk.id-map.product'),
                $container->get('paypal-pos.sdk.id-map.variant'),
                $container->get('paypal-pos.sdk.api.products'),
                $container->get('inpsyde.queue.logger')
            );
        },

    'paypal-pos.sync.taxation-mode' =>
        static function (C $container): string {
            $orgProvider = $container->get('paypal-pos.sdk.dal.provider.organization');
            assert($orgProvider instanceof OrganizationProvider);

            $org = $orgProvider->provide();

            return $org->taxationMode();
        },

    'paypal-pos.sync.taxation-type' =>
        static function (C $container): string {
            $orgProvider = $container->get('paypal-pos.sdk.dal.provider.organization');
            assert($orgProvider instanceof OrganizationProvider);

            $org = $orgProvider->provide();

            return $org->taxationType();
        },
    'paypal-pos.sdk.default-taxes' => static function (C $container): ?array {
        if ($container->get('paypal-pos.sync.taxation-type') !== TaxationType::SALES_TAX) {
            return null;
        }

        try {
            $api = $container->get('paypal-pos.sdk.api.taxes');
            $taxRates = $api->all();

            return array_filter($taxRates, static function (TaxRate $rate): bool {
                return $rate->isDefault();
            });
        } catch (Exception $exception) {
            $container->get('inpsyde.queue.logger')
                ->warning(sprintf('Failed to get PayPal Point of Sale taxes: %1$s', $exception->getMessage()));
            return null;
        }
    },
];
