<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\LazyProduct;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\Product;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\WritableProductInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\LazyVariant;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant\Variant;
use Syde\PayPal\PointOfSale\PhpSdk\Filter\TaxFilter;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class TaxFilterTest extends BrainMonkeyWpTestCase
{
    protected $taxationType = TaxationType::VAT;

    /**
     * @dataProvider successData
     */
    public function testVat(string $class): void
    {
        $entity = Mockery::mock($class);

        $this->taxationType = TaxationType::VAT;

        $sut = $this->sut();

        self::assertTrue($sut->accepts($entity, []));

        $entity->shouldReceive('setTaxExempt')->with(false);
        $entity->shouldReceive('setUsesDefaultTax')->with(null);

        $sut->filter($entity, []);
    }

    /**
     * @dataProvider successData
     */
    public function testSalesTax(string $class): void
    {
        $entity = Mockery::mock($class);

        $this->taxationType = TaxationType::SALES_TAX;

        $sut = $this->sut();

        self::assertTrue($sut->accepts($entity, []));

        $entity->shouldReceive('setTaxExempt')->with(null);
        $entity->shouldReceive('setUsesDefaultTax')->with(null);

        $sut->filter($entity, []);
    }

    /**
     * @dataProvider successData
     */
    public function testNoTax(string $class): void
    {
        $entity = Mockery::mock($class);

        $this->taxationType = TaxationType::NONE;

        $sut = $this->sut();

        self::assertTrue($sut->accepts($entity, []));

        $entity->shouldReceive('setTaxExempt')->with(false);
        $entity->shouldReceive('setUsesDefaultTax')->with(null);

        $sut->filter($entity, []);
    }

    /**
     * @dataProvider failureData
     */
    public function testFailure(string $class): void
    {
        $entity = Mockery::mock($class);

        $sut = $this->sut();

        self::assertFalse($sut->accepts($entity, []));
    }

    public function successData(): Generator
    {
        yield [Product::class];
        yield [WritableProductInterface::class];
    }

    public function failureData(): Generator
    {
        yield [Variant::class];
        yield [LazyProduct::class];
        yield [LazyVariant::class];
    }

    protected function sut(): TaxFilter
    {
        return new TaxFilter(function (): string {
            return $this->taxationType;
        });
    }
}
