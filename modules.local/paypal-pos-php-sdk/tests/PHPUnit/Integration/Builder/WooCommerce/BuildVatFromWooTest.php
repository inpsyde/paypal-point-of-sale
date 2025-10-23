<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Connection\ConnectionType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationMode;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Vat\VatProvider;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Stub\ArrayContainer;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\SetUpIdMapTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;
use Psr\Log\NullLogger;

class BuildVatFromWooTest extends ZettlePhpSdkStandaloneTestCase
{

    use SetUpIdMapTrait;

    private const STANDARD_VAT = 20.0;
    private const REDUCED_VAT = 12.5;

    /**
     * @var ArrayContainer
     */
    private $optionsContainer;

    protected function setUp(): void
    {
        $this->setUpIdMap(ConnectionType::PRODUCT);
        $this->setUpIdMap(ConnectionType::VARIANT);

        $this->injectFactory(
            'paypal-pos.sdk.dal.provider.vat.wc',
            function (): VatProvider
            {
                $mock = Mockery::mock(VatProvider::class);
                $mock->shouldReceive('provide')->andReturn(new Vat(self::REDUCED_VAT));
                return $mock;
            }
        );

        $this->optionsContainer = new ArrayContainer([]);
        $this->injectFactory(
            'paypal-pos.settings',
            function () {
                return $this->optionsContainer;
            }
        );

        $this->injectFactory(
            'paypal-pos.sync.taxation-mode',
            function (): string {
                return TaxationMode::INCLUSIVE;
            }
        );

        $this->injectFactory(
            'paypal-pos.sync.taxation-type',
            function (): string {
                return TaxationType::VAT;
            }
        );

        $this->injectFactory(
            'paypal-pos.logger.woocommerce',
            function () {
                return new NullLogger();
            }
        );

        parent::setUp();
    }

    public function testBuildWcVat()
    {
        $wcProduct = Mockery::mock(WC_Product_Simple::class.', '.WC_Product::class);

        $vat = $this->builder()->build(Vat::class, $wcProduct);

        assert($vat instanceof Vat);

        self::assertEquals(self::REDUCED_VAT, $vat->percentage());
    }

    public function builder(): BuilderInterface
    {
        return $this->get('paypal-pos.sdk.builder');
    }
}
