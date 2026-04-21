<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\VariantInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\DifferentVariantVatException;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\VariableProductVatValidator;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Functions\when;

class VariableProductVatValidatorTest extends BrainMonkeyWpTestCase
{
    private $productName = 'prod1';

    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        when('esc_html')->returnArg();

        $this->sut = new VariableProductVatValidator();
    }

    /**
     * @dataProvider successData
     */
    public function testSuccess(ProductInterface $entity): void
    {
        self::assertTrue($this->sut->accepts($entity));
        self::assertTrue($this->sut->validate($entity));
    }

    /**
     * @dataProvider failureData
     */
    public function testFailure(ProductInterface $entity): void
    {
        self::assertTrue($this->sut->accepts($entity));

        self::expectException(DifferentVariantVatException::class);

        $this->sut->validate($entity);
    }

    private function mockProduct(float $vat, array $variants): ProductInterface
    {
        $product = Mockery::mock(ProductInterface::class);
        $product->shouldReceive('name')->andReturn($this->productName);
        $product->shouldReceive('vat')->andReturn(new Vat($vat));
        $product->shouldReceive('variants')->andReturn(new VariantCollection(...$variants));
        return $product;
    }

    private function mockVariant(float $vat): VariantInterface
    {
        $variant = Mockery::mock(VariantInterface::class);
        $variant->shouldReceive('vat')->andReturn(new Vat($vat));
        return $variant;
    }

    public function successData(): Generator
    {
        yield [
            $this->mockProduct(20.0, [
                $this->mockVariant(20.0),
                $this->mockVariant(20.0),
            ]),
        ];
        yield [
            $this->mockProduct(12.5, [
                $this->mockVariant(12.5),
                $this->mockVariant(12.5),
                $this->mockVariant(12.5),
            ]),
        ];
        yield [
            $this->mockProduct(20.0, [
                $this->mockVariant(20.0),
            ]),
        ];
    }

    public function failureData(): Generator
    {
        yield [
            $this->mockProduct(12.5, [
                $this->mockVariant(20.0),
                $this->mockVariant(20.0),
            ]),
        ];
        yield [
            $this->mockProduct(12.5, [
                $this->mockVariant(12.5),
                $this->mockVariant(20.0),
            ]),
        ];
    }
}
