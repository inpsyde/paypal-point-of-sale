<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Test\WpIntegration;

use PHPUnit\Framework\TestCase;
use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\PhpSdk\Map\WpdbMap;
use Syde\PayPal\PointOfSale\Sync\Job\ExportProductJob;
use WC_Product_Simple;

final class ProductSyncTest extends TestCase
{
    public function testSyncCreatesProductInZettle(): void
    {
        if (($_ENV['IZETTLE_API_KEY'] ?? '') === '') {
            self::markTestSkipped('IZETTLE_API_KEY is not set in tests/wp-integration/.env.phpunit.');
        }

        $package = \Syde\PayPal\PointOfSale\init();
        self::assertNotNull($package, 'Plugin failed to boot — see admin notice / debug.log');
        $container = $package->container();

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

        // 3. Drain the queue — this is what actually calls the Zettle REST API.
        $container->get('inpsyde.queue.processor')->process();

        // 4. A Simple WC product becomes a Zettle "product with one variant". The
        //    plugin records the variant UUID (not the parent product UUID) in the
        //    id-map keyed by the WC product ID.
        $variantMap = $container->get('paypal-pos.sdk.id-map.variant');
        assert($variantMap instanceof WpdbMap);
        $remoteId = $variantMap->remoteId($productId);
        self::assertNotEmpty($remoteId, 'No remote UUID was recorded for the synced product.');

        $productsApi = $container->get('paypal-pos.sdk.api.products');
        assert($productsApi instanceof Products);

        $productsList = $productsApi->list();
        $remoteProduct = $productsList->get($remoteId);
        self::assertNotEmpty($remoteProduct, "The product $remoteId was not found in the remote inventory.");
    }
}
