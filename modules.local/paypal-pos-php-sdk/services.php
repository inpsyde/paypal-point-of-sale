<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk;

use Dhii\Collection\MutableContainerInterface;
use Http\Message\UriFactory;
use Syde\PayPal\PointOfSale\PhpSdk\API\Image\Images;
use Syde\PayPal\PointOfSale\PhpSdk\API\Inventory\Inventory;
use Syde\PayPal\PointOfSale\PhpSdk\API\Inventory\Locations;
use Syde\PayPal\PointOfSale\PhpSdk\API\Listener\Products\OnSuccessDeleteProductsListener;
use Syde\PayPal\PointOfSale\PhpSdk\API\OAuth\Organizations;
use Syde\PayPal\PointOfSale\PhpSdk\API\OAuth\Users;
use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\PhpSdk\API\Taxes\Taxes;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\PayloadFactory;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\WebhookFactory;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\ZettlePayloadFactory;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\ZettleWebhookFactory;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Subscriptions;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\ArrayBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\ContainerAwareBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\FilterableBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\TypeDelegatingBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\ValidatableBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\WooCommerceBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\Config\WooCommerceConfigContainer;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Repository\Variant\VariantBuilderRepository;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Repository\Variant\VariantBuilderRepositoryInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Connection\ConnectionType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Image\PlaceholderUrlProvider;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Image\UrlProviderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Image\WordPressFilePathProvider;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Image\WordpressUrlProvider;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Organization\OrganizationProvider;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Organization\RestOrganizationProvider;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Organization\TransientCachingOrganizationProvider;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Vat\VatProvider;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Vat\WooCommerceVatProvider;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Validator\Vat\VatValidator;
use Syde\PayPal\PointOfSale\PhpSdk\DB\DataMappingTable;
use Syde\PayPal\PointOfSale\PhpSdk\DB\Table;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\Factory\WcProductFactory;
use Syde\PayPal\PointOfSale\PhpSdk\Factory\WcProductFactoryInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Filter\CompoundFilter;
use Syde\PayPal\PointOfSale\PhpSdk\Filter\FilterInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Filter\ImageConnectionFilter;
use Syde\PayPal\PointOfSale\PhpSdk\Filter\ProductConnectionFilter;
use Syde\PayPal\PointOfSale\PhpSdk\Filter\StockQuantityFilter;
use Syde\PayPal\PointOfSale\PhpSdk\Filter\TaxFilter;
use Syde\PayPal\PointOfSale\PhpSdk\Filter\VariantConnectionFilter;
use Syde\PayPal\PointOfSale\PhpSdk\Image\ExifImageFormatRetriever;
use Syde\PayPal\PointOfSale\PhpSdk\Image\ExtensionImageFormatRetriever;
use Syde\PayPal\PointOfSale\PhpSdk\Image\ImageFormatRetrieverInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Map\WpdbMap;
use Syde\PayPal\PointOfSale\PhpSdk\Provider\BootstrapProvider;
use Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepository as WcProductRepository;
use Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface as WcProductRepositoryInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Repository\Zettle\Product\ProductRepository;
use Syde\PayPal\PointOfSale\PhpSdk\Repository\Zettle\Product\ProductRepositoryInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\ContainerAwareEntitySerializer;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\CompoundValidator;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\LocalImageValidator;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\PresentationValidator;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\ProductValidator;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\ProductVariantOptionDefinitionsValidator;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\StockValidator;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\ValidatorInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\VariableProductVatValidator;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\VariantOptionDefinitionsValidator;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\VariantOptionValidator;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\WordPressImageValidator;
use Syde\PayPal\PointOfSale\Provider;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerInterface as C;
use Symfony\Component\Uid\Uuid;
use wpdb;

return array_merge(
    [
        'paypal-pos.sdk.dal.table.name' => static function (): string {
            return 'zettle_woocommerce_id_map';
        },
        'paypal-pos.sdk.dal.table' => static function (C $container): Table {
            return new DataMappingTable($container->get('paypal-pos.sdk.dal.table.name'));
        },
        'paypal-pos.sdk.option.integration' => static function (C $container): string {
            return 'sdk.integration-id';
        },
        /**
         * This UUID is used when syncing inventory.
         * Incoming webhooks will pass the UUID through back to us so we can determine
         * whether or not the change was triggered from our end.
         *
         * TODO Maybe simply ALWAYS return a new UUID here and handle caching via extension
         */
        'paypal-pos.sdk.integration-id' => static function (C $container): string {
            if (!$container->has('paypal-pos.sdk.integration-id.container')) {
                return (string) Uuid::v1();
            }
            $idContainer = $container->get('paypal-pos.sdk.integration-id.container');
            assert($idContainer instanceof MutableContainerInterface);
            $key = $container->get('paypal-pos.sdk.option.integration');

            if (!$idContainer->has($key)) {
                $idContainer->set(
                    $key,
                    (string) Uuid::v1()
                );
            }

            return $idContainer->get($key);
        },
        'paypal-pos.sdk.serializer' => static function (C $container): SerializerInterface {
            return new ContainerAwareEntitySerializer(
                new NamespacedContainer(
                    'paypal-pos.sdk.serializer',
                    $container
                )
            );
        },
        'paypal-pos.sdk.builder' => static function (C $container): BuilderInterface {
            $arrayBuilderContainer = new NamespacedContainer(
                'paypal-pos.sdk.builder.array',
                $container
            );
            $wooBuilderContainer = new NamespacedContainer(
                'paypal-pos.sdk.builder.woocommerce',
                $container
            );
            $typedDelegatingBuilder = new TypeDelegatingBuilder(
                new ArrayBuilder(new ContainerAwareBuilder($arrayBuilderContainer)),
                new WooCommerceBuilder(new ContainerAwareBuilder($wooBuilderContainer))
            );
            $filteredBuilder = new FilterableBuilder(
                $typedDelegatingBuilder,
                $container->get('paypal-pos.sdk.filter')
            );

            return new ValidatableBuilder(
                $filteredBuilder,
                $container->get('paypal-pos.sdk.validator')
            );
        },
        'paypal-pos.sdk.builder.repository.variant' =>
            static function (C $container): VariantBuilderRepositoryInterface {
                return new VariantBuilderRepository(
                    $container->get('paypal-pos.sdk.builder')
                );
            },
        'paypal-pos.sdk.filters.product-connection' =>
            static function (C $container): FilterInterface {
                return new ProductConnectionFilter(
                    $container->get('paypal-pos.sdk.id-map.product'),
                    static function () use ($container): Products {
                        return $container->get('paypal-pos.sdk.api.products');
                    }
                );
            },
        'paypal-pos.sdk.filters.variant-connection' =>
            static function (C $container): FilterInterface {
                return new VariantConnectionFilter(
                    $container->get('paypal-pos.sdk.id-map.variant')
                );
            },
        'paypal-pos.sdk.filters.image-connection' => static function (C $container): FilterInterface {
            return new ImageConnectionFilter();
        },
        'paypal-pos.sdk.filters.tax' => static function (C $container): FilterInterface {
            return new TaxFilter(
                static function () use ($container): string {
                    return $container->get('paypal-pos.sync.taxation-type');
                }
            );
        },
        'paypal-pos.sdk.filters' => static function (C $container): array {
            return [
                $container->get('paypal-pos.sdk.filters.product-connection'),
                $container->get('paypal-pos.sdk.filters.variant-connection'),
                $container->get('paypal-pos.sdk.filters.image-connection'),
                $container->get('paypal-pos.sdk.filters.tax'),
            ];
        },
        'paypal-pos.sdk.filter' => static function (C $container): FilterInterface {
            return new CompoundFilter(
                ...$container->get('paypal-pos.sdk.filters')
            );
        },
        'paypal-pos.sdk.validator.product' => static function (C $container): ValidatorInterface {
            return new ProductValidator();
        },
        'paypal-pos.sdk.validator.variable-product-vat' => static function (C $container): ValidatorInterface {
            return new VariableProductVatValidator();
        },
        'paypal-pos.sdk.validator.presentation' => static function (C $container): ValidatorInterface {
            return new PresentationValidator();
        },
        'paypal-pos.sdk.validator.variant-option-definitions' =>
            static function (C $container): ValidatorInterface {
                return new VariantOptionDefinitionsValidator();
            },
        'paypal-pos.sdk.validator.product-with-variants' =>
            static function (C $container): ValidatorInterface {
                return new ProductVariantOptionDefinitionsValidator();
            },

        'paypal-pos.sdk.validator.local-image' => static function (C $container): ValidatorInterface {
            return new LocalImageValidator(
                $container->get('paypal-pos.sdk.dal.provider.image.file'),
                $container->get('paypal-pos.sdk.validator.image.exif-supported-types'),
                $container->get('paypal-pos.sdk.validator.image.min-file-size'),
                $container->get('paypal-pos.sdk.validator.image.max-file-size'),
                $container->get('paypal-pos.sdk.validator.image.min-width'),
                $container->get('paypal-pos.sdk.validator.image.min-height'),
                $container->get('paypal-pos.sdk.validator.image.max-width'),
                $container->get('paypal-pos.sdk.validator.image.max-height')
            );
        },
        'paypal-pos.sdk.validator.wp-image' => static function (C $container): ValidatorInterface {
            return new WordPressImageValidator(
                $container->get('paypal-pos.sdk.validator.image.supported-types'),
                $container->get('paypal-pos.sdk.validator.image.min-file-size'),
                $container->get('paypal-pos.sdk.validator.image.max-file-size'),
                $container->get('paypal-pos.sdk.validator.image.min-width'),
                $container->get('paypal-pos.sdk.validator.image.min-height'),
                $container->get('paypal-pos.sdk.validator.image.max-width'),
                $container->get('paypal-pos.sdk.validator.image.max-height')
            );
        },
        'paypal-pos.sdk.validator.image' => static function (C $container): ValidatorInterface {
            return $container->get('paypal-pos.sdk.validator.wp-image');
        },
        'paypal-pos.sdk.validator.image.supported-types' => static function (C $container): array {
            return [
                'gif',
                'jpeg',
                'png',
                'bmp',
                'tiff',
            ];
        },
        'paypal-pos.sdk.validator.image.exif-supported-types' => static function (C $container): array {
            return [
                IMAGETYPE_GIF => 'GIF',
                IMAGETYPE_JPEG => 'JPEG',
                IMAGETYPE_PNG => 'PNG',
                IMAGETYPE_BMP => 'BMP',
                IMAGETYPE_TIFF_II => 'TIFF',
                IMAGETYPE_TIFF_MM => 'TIFF',
            ];
        },
        'paypal-pos.sdk.validator.image.min-file-size' => static function (C $container): int {
            return 2500;
        },
        'paypal-pos.sdk.validator.image.max-file-size' => static function (C $container): int {
            return 5242880;
        },
        'paypal-pos.sdk.validator.image.min-width' => static function (C $container): int {
            return 50;
        },
        'paypal-pos.sdk.validator.image.min-height' => static function (C $container): int {
            return 50;
        },
        'paypal-pos.sdk.validator.image.max-width' => static function (C $container): int {
            return 5000;
        },
        'paypal-pos.sdk.validator.image.max-height' => static function (C $container): int {
            return 5000;
        },

        'paypal-pos.sdk.validator.stock' => static function (C $container): ValidatorInterface {
            return new StockValidator(
                $container->get('paypal-pos.sdk.validator.stock.max')
            );
        },
        'paypal-pos.sdk.validator.stock.max' => static function (C $container): int {
            return 99999;
        },

        'paypal-pos.sdk.validator.variant-option' => static function (
            C $container
        ): ValidatorInterface {
            return new VariantOptionValidator();
        },

        'paypal-pos.sdk.validators' => static function (C $container): array {
            return [
                $container->get('paypal-pos.sdk.validator.product'),
                $container->get('paypal-pos.sdk.validator.variable-product-vat'),
                $container->get('paypal-pos.sdk.validator.presentation'),
                $container->get('paypal-pos.sdk.validator.variant-option-definitions'),
                $container->get('paypal-pos.sdk.validator.product-with-variants'),
                $container->get('paypal-pos.sdk.validator.image'),
                $container->get('paypal-pos.sdk.validator.variant-option'),
                $container->get('paypal-pos.sdk.validator.stock'),
            ];
        },
        'paypal-pos.sdk.validator' => static function (C $container): ValidatorInterface {
            return new CompoundValidator(
                ...$container->get('paypal-pos.sdk.validators')
            );
        },

        'paypal-pos.sdk.wpdb' => static function (): wpdb {
            global $wpdb;

            return $wpdb;
        },
        'paypal-pos.sdk.id-map.product' => static function (C $container): WpdbMap {
            return new WpdbMap(
                $container->get('paypal-pos.sdk.wpdb'),
                $container->get('paypal-pos.sdk.dal.table'),
                ConnectionType::PRODUCT,
                $container->get('paypal-pos.current-site-id')
            );
        },
        'paypal-pos.sdk.id-map.variant' => static function (C $container): WpdbMap {
            return new WpdbMap(
                $container->get('paypal-pos.sdk.wpdb'),
                $container->get('paypal-pos.sdk.dal.table'),
                ConnectionType::VARIANT,
                $container->get('paypal-pos.current-site-id')
            );
        },
        'paypal-pos.sdk.id-map.image' => static function (C $container): WpdbMap {
            return new WpdbMap(
                $container->get('paypal-pos.sdk.wpdb'),
                $container->get('paypal-pos.sdk.dal.table'),
                ConnectionType::IMAGE,
                $container->get('paypal-pos.current-site-id')
            );
        },
        'paypal-pos.sdk.bootstrap' => static function (C $container): Bootstrap {
            return new Bootstrap($container->get('paypal-pos.sdk.dal.table'));
        },
        'paypal-pos.sdk.provider.bootstrap' => static function (C $container): Provider {
            return new BootstrapProvider(
                $container->get('paypal-pos.sdk.bootstrap')
            );
        },
        'paypal-pos.sdk.provider' => static function (C $container): array {
            return [
                $container->get('paypal-pos.sdk.provider.bootstrap'),
            ];
        },
        'paypal-pos.sdk.config.woocommerce-config' =>
            static function (C $container): ContainerInterface {
                return new WooCommerceConfigContainer();
            },
        'paypal-pos.sdk.placeholder-image-url' => static function (C $container): string {
            $envUrl = getenv('IZETTLE_PLACEHOLDER_IMAGE_URL');
            if ($envUrl) {
                return (string) $envUrl;
            }

            return 'https://placehold.co/200x200.jpg?text=WooProduct';
        },
        'paypal-pos.sdk.dal.provider.image.url' =>
            static function (C $container): UrlProviderInterface {
                if (getenv('IZETTLE_PLACEHOLDER_IMAGES_ENABLED') === '1') {
                    return new PlaceholderUrlProvider($container->get('paypal-pos.sdk.placeholder-image-url'));
                }

                return new WordpressUrlProvider();
            },
        'paypal-pos.sdk.dal.provider.image.file' =>
            static function (C $container): UrlProviderInterface {
                return new WordPressFilePathProvider();
            },
        'paypal-pos.sdk.dal.provider.organization.transient-key' => static function (): string {
            return 'zettle_organization';
        },
        'paypal-pos.sdk.dal.provider.organization' =>
            static function (C $container): OrganizationProvider {
                return new TransientCachingOrganizationProvider(
                    new RestOrganizationProvider(
                        $container->get('paypal-pos.sdk.api.oauth.organizations')
                    ),
                    $container->get('paypal-pos.sdk.dal.provider.organization.transient-key'),
                    $container->get('paypal-pos.sdk.dal.provider.organization.transient-expiration')
                );
            },
        'paypal-pos.sdk.dal.provider.organization.transient-expiration' =>
            static function (): int {
                return 5 * 60; // 5 minutes
            },
        'paypal-pos.sdk.dal.provider.vat.wc' => static function (C $container): VatProvider {
            return new WooCommerceVatProvider(
                $container->get('paypal-pos.wc.shop.location')
            );
        },
        'paypal-pos.sdk.rest-client' => static function (C $container): RestClientInterface {
            return new Psr18RestClient(
                $container->get('paypal-pos.logger.woocommerce'),
                $container->get('inpsyde.http-client'),
                $container->get('inpsyde.http-client.uri-factory'),
                $container->get('inpsyde.http-client.request-factory'),
                $container->get('inpsyde.http-client.stream-factory')
            );
        },
        'paypal-pos.sdk.api.oauth.users' => static function (C $container): Users {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Users(
                $container->get('paypal-pos.logger.woocommerce'),
                $uriFactory->createUri('https://oauth.izettle.com'),
                $container->get('paypal-pos.sdk.rest-client')
            );
        },
        'paypal-pos.sdk.api.oauth.organizations' => static function (C $container): Organizations {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Organizations(
                $uriFactory->createUri('https://secure.izettle.com'),
                $container->get('paypal-pos.sdk.rest-client'),
                $container->get('paypal-pos.sdk.builder')
            );
        },
        'paypal-pos.sdk.api.listener.delete.product' =>
            static function (C $container): OnSuccessDeleteProductsListener {
                return new OnSuccessDeleteProductsListener(
                    $container->get('paypal-pos.sdk.repository.zettle.product'),
                    $container->get('inpsyde.queue.repository'),
                    $container->get('inpsyde.queue.create-job-record'),
                    $container->get('inpsyde.queue.logger')
                );
            },
        'paypal-pos.sdk.api.products.listener.update' => static function (C $container): callable {
            //phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
            return static function (string $operation, $payload, bool $success) use ($container) {
                //Silence. This is only here so that extensions can add actual listeners
            };
            //phpcs:enable
        },
        'paypal-pos.sdk.api.products.listener.delete' => static function (C $container): callable {
            //phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType

            return static function (string $operation, $payload, bool $success) use ($container) {
                $productsDeleteListener = $container->get('paypal-pos.sdk.api.listener.delete.product');

                if (!$productsDeleteListener->accepts($operation, $payload, $success)) {
                    return;
                }

                $productsDeleteListener->execute($payload);
            };
            //phpcs:enable
        },
        'paypal-pos.sdk.api.products' => static function (C $container): Products {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Products(
                $uriFactory->createUri('https://products.izettle.com'),
                $container->get('paypal-pos.sdk.rest-client'),
                $container->get('paypal-pos.sdk.builder'),
                $container->get('paypal-pos.sdk.serializer'),
                $container->get('paypal-pos.sdk.api.products.listener.delete'),
                $container->get('paypal-pos.sdk.api.products.listener.update')
            );
        },
        'paypal-pos.sdk.api.images' => static function (C $container): Images {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Images(
                $uriFactory->createUri('https://image.izettle.com'),
                $container->get('paypal-pos.sdk.rest-client'),
                $container->get('paypal-pos.sdk.builder'),
                $container->get('paypal-pos.sdk.image.format-retriever'),
                $container->get('paypal-pos.logger')
            );
        },
        'paypal-pos.sdk.api.webhooks.factory' => static function (): WebhookFactory {
            return new ZettleWebhookFactory();
        },
        'paypal-pos.sdk.api.webhooks' => static function (C $container): Subscriptions {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Subscriptions(
                $uriFactory->createUri('https://pusher.izettle.com'),
                $container->get('paypal-pos.sdk.rest-client'),
                $container->get('paypal-pos.sdk.api.webhooks.factory')
            );
        },
        'paypal-pos.sdk.api.webhooks.payload.factory' => static function (): PayloadFactory {
            return new ZettlePayloadFactory();
        },
        'paypal-pos.sdk.api.inventory' => static function (C $container): Inventory {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Inventory(
                $uriFactory->createUri('https://inventory.izettle.com'),
                $container->get('paypal-pos.sdk.rest-client'),
                $container->get('paypal-pos.sdk.api.inventory.locations'),
                $container->get('paypal-pos.sdk.builder'),
                $container->get('paypal-pos.sdk.integration-id')
            );
        },
        'paypal-pos.sdk.api.taxes' => static function (C $container): Taxes {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Taxes(
                $uriFactory->createUri('https://products.izettle.com'),
                $container->get('paypal-pos.sdk.rest-client'),
                $container->get('paypal-pos.sdk.builder')
            );
        },
        'paypal-pos.sdk.api.inventory.locations' => static function (C $container): Locations {
            /**
             * @var UriFactory $uriFactory
             */
            $uriFactory = $container->get('inpsyde.http-client.uri-factory');

            return new Locations(
                $uriFactory->createUri('https://inventory.izettle.com'),
                $container->get('paypal-pos.sdk.rest-client'),
                $container->get('paypal-pos.sdk.builder')
            );
        },
        'paypal-pos.sdk.api.auth-check' => static function (C $container): callable {
            return static function () use ($container): bool {
                $users = $container->get('paypal-pos.sdk.api.oauth.users');
                assert($users instanceof Users);

                try {
                    $users->me();

                    return true;
                } catch (ZettleRestException $exception) {
                    // Logging needed?
                }

                return false;
            };
        },
        'paypal-pos.sdk.repository.woocommerce.product' => static function (
            C $container
        ): WcProductRepositoryInterface {
            return new WcProductRepository();
        },
        'paypal-pos.sdk.repository.zettle.product' => static function (
            C $container
        ): ProductRepositoryInterface {
            return new ProductRepository(
                $container->get('paypal-pos.sdk.id-map.product')
            );
        },
        'paypal-pos.sdk.factory.woocommerce.product' => static function (
            C $container
        ): WcProductFactoryInterface {
            return new WcProductFactory(
                $container->get('paypal-pos.sdk.repository.zettle.product'),
                $container->get('paypal-pos.sdk.repository.woocommerce.product')
            );
        },

        'paypal-pos.sdk.image.format-retrievers.exif' => static function (
            C $container
        ): ImageFormatRetrieverInterface {
            return new ExifImageFormatRetriever();
        },
        'paypal-pos.sdk.image.format-retrievers.extension' => static function (
            C $container
        ): ImageFormatRetrieverInterface {
            return new ExtensionImageFormatRetriever();
        },
        'paypal-pos.sdk.image.format-retriever' => static function (
            C $container
        ): ImageFormatRetrieverInterface {
            return $container->get('paypal-pos.sdk.image.format-retrievers.extension');
        },
    ],
    require __DIR__ . '/builders.array.php',
    require __DIR__ . '/builders.woocommerce.php',
    require __DIR__ . '/serializers.php'
);
