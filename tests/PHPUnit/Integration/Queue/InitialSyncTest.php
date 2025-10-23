<?php
declare(strict_types=1);

use Inpsyde\Queue\Processor\QueueProcessor;
use Inpsyde\Queue\Queue\EphemeralLocker;
use Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Inpsyde\Queue\Queue\Locker;
use Inpsyde\Queue\Queue\QueueWalker;
use Inpsyde\Queue\Queue\UnstoppableQueueWalker;
use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\PriceBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use Syde\PayPal\PointOfSale\Sync\Job\EnqueueProductSyncJob;
use Syde\PayPal\PointOfSale\Sync\Job\ExportProductJob;
use Syde\PayPal\PointOfSale\Sync\Job\SetInventoryTrackingJob;
use Syde\PayPal\PointOfSale\Sync\Job\SyncStockJob;
use Syde\PayPal\PointOfSale\Test\DataProvider\WcProductSampleData;
use Syde\PayPal\PointOfSale\Test\StubLoader;
use Syde\PayPal\PointOfSale\Test\ZettleEntityCrudTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use function Brain\Monkey\Functions\expect;

/**
 * phpcs:disable
 *
 * @group sync
 */
class InitialSyncTest extends ZettleEntityCrudTestCase
{

    private $logs = [];

    public function setUp(): void
    {
        $this->logs = [];
        $this->injectFactory(
            'inpsyde.queue.repository',
            function (): JobRepository {
                return new EphemeralJobRepository();
            }
        );
        $this->injectFactory(
            'inpsyde.queue.locker',
            function (): Locker {
                return new EphemeralLocker();
            }
        );
        $this->injectFactory(
            'inpsyde.queue.walker',
            function (ContainerInterface $container): QueueWalker {
                return new UnstoppableQueueWalker($container->get('inpsyde.queue.iterator'));
            }
        );

        $this->injectExtension(
            'paypal-pos.job.'.EnqueueProductSyncJob::TYPE,
            function (ContainerInterface $container, Job $previous): Job {
                return Mockery::spy($previous);
            }
        );
        $this->injectExtension(
            'paypal-pos.job.'.ExportProductJob::TYPE,
            function (ContainerInterface $container, Job $previous): Job {
                return Mockery::spy($previous);
            }
        );
        $this->injectExtension(
            'paypal-pos.job.'.SyncStockJob::TYPE,
            function (ContainerInterface $container, Job $previous): Job {
                return Mockery::spy($previous);
            }
        );
        $this->injectExtension(
            'paypal-pos.job.'.SetInventoryTrackingJob::TYPE,
            function (ContainerInterface $container, Job $previous): Job {
                return Mockery::spy($previous);
            }
        );

        $this->injectFactory(
            'paypal-pos.sdk.builder.woocommerce.'.PriceBuilder::class,
            function () {
                $mock = Mockery::mock(PriceBuilder::class);
                $mock->shouldReceive('build')->andReturn(new Price(10, 'EUR'));
            }
        );

        $this->injectExtension(
            'paypal-pos.sync.product.sync-active-for-id',
            function (): callable {
                return function (int $productId) {
                    return true;
                };
            }
        );

        $this->injectFactory(
            'inpsyde.is-multisite',
            function (): bool {
                return $this->isMultisite;
            }
        );

        $this->isMultisite = false;

        $this->injectFactory(
            'inpsyde.queue.logger',
            function (): LoggerInterface {
                return new class(function ($level, $message): void {
                    $this->logs[] = $level.': '.$message;
                }) extends AbstractLogger {

                    /**
                     * @var callable
                     */
                    private $log;

                    public function __construct(callable $log)
                    {
                        $this->log = $log;
                    }

                    public function log($level, $message, array $context = [])
                    {
                        ($this->log)($level, $message);
                    }
                };
            }
        );

        StubLoader::load();
        parent::setUp();

        expect('get_current_blog_id')->andReturn(1);
        expect('utf8_uri_encode')
            ->andReturn($this->get('paypal-pos.sdk.placeholder-image-url'));
    }

    public function testEnqueueSyncAndProcess()
    {
        // Cleanup any products that might not have been cleaned up properly from previous tests
        $this->deleteProducts();

        // Prepare actual test
        $productIds = [
            1 => [
                'managing_stock' => true,
            ],
            34 => [
                'managing_stock' => false,
            ],
        ];

        $products = [];
        $productIdsWithManagingStock = [
            1,
        ];

        foreach ($productIds as $productId => $data) {
            $products[$productId] = WcProductSampleData::createWcProduct(
                $productId,
                WC_Product_Simple::class,
                $data
            );
        }
        expect('home_url')->andReturn('http://foo.bar');
        expect('wc_get_products')->andReturn(array_keys($productIds));
        expect('wc_get_product')->andReturnUsing(
            function () use ($products): WC_Product {
                [$id] = func_get_args();

                return $products[$id];
            }
        );

        expect('wc_get_price_including_tax');

        // Enqueue the sync job
        $event = $this->get('paypal-pos.sync.enqueue-initial-sync');
        $this->assertIsCallable($event);
        $event();

        // Process the queue until all jobs are executed
        $processor = $this->processor();
        $processor->process();

        // Fetch jobs that were expected to run
        $enqueueProductJob = $this->get('paypal-pos.job.'.EnqueueProductSyncJob::TYPE);
        $updateProductJob = $this->get('paypal-pos.job.'.ExportProductJob::TYPE);
        $syncStockJob = $this->get('paypal-pos.job.'.SyncStockJob::TYPE);
        $setInventoryTrackingJob = $this->get('paypal-pos.job.'.SetInventoryTrackingJob::TYPE);

        try {
            $enqueueProductJob->shouldHaveReceived('execute')->once();
            $updateProductJob->shouldHaveReceived('execute')->times(count($productIds));

            /**
             * SyncStockJob is supposed to fail once because of disabled inventory tracking.
             * It should dispatch the SetInventoryTrackingJob and then be retried by the queue
             */
            $syncStockJob->shouldHaveReceived('execute')->times(count($productIdsWithManagingStock));
            $setInventoryTrackingJob->shouldHaveReceived('execute')->times(count($productIdsWithManagingStock));

            // Zettle products should now have been created via REST Api
            // Fetch the list of products and compare
            $list = $this->productsApi()->list();

            $this->assertSame(
                count($productIds),
                count($list->all()),
                'Expecting the same amount of products locally and remotely'
            );
        } catch (Throwable $exc) {
            print_r($this->logs);
            throw $exc;
        }

        // Cleanup again
        $this->deleteProducts();
    }

    private function productsApi(): Products
    {
        return $this->get('paypal-pos.sdk.api.products');
    }

    public function processor(): QueueProcessor
    {
        return $this->get('inpsyde.queue.processor');
    }

    private function deleteProducts(): void
    {
        $productsApi = $this->productsApi();

        $list = $productsApi->list();

        foreach ($list->all() as $product) {
            $result = $productsApi->delete((string) $product->uuid());
            $this->assertTrue($result, 'Existing product should have been deleted properly');
        }

        $list = $productsApi->list();

        $this->assertEmpty($list->all(), 'Product list should be empty');
    }
}
