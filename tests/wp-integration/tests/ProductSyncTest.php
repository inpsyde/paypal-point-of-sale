<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Test\WpIntegration;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\PhpSdk\Map\WpdbMap;
use Syde\PayPal\PointOfSale\Sync\Job\DeleteProductJob;
use Syde\PayPal\PointOfSale\Sync\Job\ExportProductJob;
use WC_Product_Simple;

final class ProductSyncTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        if (($_ENV['IZETTLE_API_KEY'] ?? '') === '') {
            self::markTestSkipped('IZETTLE_API_KEY is not set in tests/wp-integration/.env.phpunit.');
        }

        $package = \Syde\PayPal\PointOfSale\init();
        self::assertNotNull($package, 'Plugin failed to boot — see admin notice / debug.log');
        $this->container = $package->container();
    }

    /**
     * @return array{productId: int, remoteId: string}
     */
    public function testSyncCreatesProduct(): array
    {
        $container = $this->container;

        // 1. Create a WooCommerce product using the real WC API.
        $product = new WC_Product_Simple();
        $product->set_name('WLITH test ' . uniqid('', true));
        $product->set_regular_price('9.99');
        $product->set_sku('wlith-' . uniqid('', true));
        $product->set_status('publish');
        $product->set_manage_stock(false);
        $productId = $product->save();

        self::assertGreaterThan(0, $productId, 'WC_Product::save() returned 0');

        // 2. Enqueue an ExportProductJob exactly the way the listener would.
        $enqueue = $container->get('inpsyde.queue.enqueue-job');
        self::assertIsCallable($enqueue);
        $enqueue(ExportProductJob::TYPE, ['productId' => $productId]);

        // 3. Drain the queue — this is what actually calls the remote API.
        $container->get('inpsyde.queue.processor')->process();

        // 4. Check the result.

        $variantMap = $container->get('paypal-pos.sdk.id-map.variant');
        assert($variantMap instanceof WpdbMap);
        $remoteId = $variantMap->remoteId($productId);
        self::assertNotEmpty($remoteId, 'No remote UUID was recorded for the synced product.');

        $productsApi = $container->get('paypal-pos.sdk.api.products');
        assert($productsApi instanceof Products);

        $productsList = $productsApi->list();
        $remoteProduct = $productsList->get($remoteId);
        self::assertNotEmpty($remoteProduct, "The product $remoteId was not found in the remote inventory.");

        return ['productId' => (int) $productId, 'remoteId' => (string) $remoteId];
    }

    /**
     * @depends testSyncCreatesProduct
     *
     * @param array{productId: int, remoteId: string} $synced
     */
    public function testSyncDeletesProduct(array $synced): void
    {
        $container = $this->container;

        $enqueue = $container->get('inpsyde.queue.enqueue-job');
        self::assertIsCallable($enqueue);

        // 1. Enqueue a DeleteProductJob exactly the way DePublishListener would.
        $enqueue(DeleteProductJob::TYPE, ['productId' => $synced['productId']]);

        // 2. Drain the queue — this is what actually calls the DELETE endpoint.
        $container->get('inpsyde.queue.processor')->process();

        // 3. The product should no longer be present in the remote inventory.
        $productsApi = $container->get('paypal-pos.sdk.api.products');
        assert($productsApi instanceof Products);
        self::assertNull(
            $productsApi->list()->get($synced['remoteId']),
            "The product {$synced['remoteId']} was still present in the remote inventory after delete."
        );
    }
}
