<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Psr\Container\ContainerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationMode;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\MinorUnits;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;
use WC_Product;

class PriceBuilder implements BuilderInterface
{
    private ContainerInterface $wooCommerceConfig;

    private string $taxationMode;

    public function __construct(ContainerInterface $wooCommerceConfig, string $taxationMode)
    {
        $this->wooCommerceConfig = $wooCommerceConfig;
        $this->taxationMode = $taxationMode;
    }

    public function build(string $className, mixed $payload, ?BuilderInterface $builder = null): Price
    {
        assert($payload instanceof WC_Product);

        $price = $this->taxationMode === TaxationMode::EXCLUSIVE
            ? (float) wc_get_price_excluding_tax($payload)
            : (float) wc_get_price_including_tax($payload);

        $currency = (string) $this->wooCommerceConfig->get('currency');

        return new Price(
            /** Zettle requires prices as minor-unit integers */
            MinorUnits::toMinor($price, $currency),
            $currency
        );
    }
}
