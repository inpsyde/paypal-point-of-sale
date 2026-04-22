<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\DataProvider\Store;

use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\Organization;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\TaxationMode;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Organization\OrganizationProvider;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;

class ZettleStoreDataProvider implements StoreDataProvider
{
    private OrganizationProvider $provider;

    public function __construct(OrganizationProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @inheritDoc
     */
    public function vat(): Vat
    {
        return $this->organization()->vat();
    }

    /**
     * @inheritDoc
     */
    public function vatPercentage(): float
    {
        return $this->vat()->percentage();
    }

    /**
     * @inheritDoc
     */
    public function currency(): string
    {
        return $this->organization()->currency();
    }

    /**
     * @inheritDoc
     */
    public function includeTaxes(): bool
    {
        return $this->organization()->taxationMode() !== TaxationMode::EXCLUSIVE;
    }

    /**
     * @inheritDoc
     */
    public function taxesEnabled(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function taxationType(): string
    {
        return $this->organization()->taxationType();
    }

    /**
     * @inheritDoc
     */
    public function country(): string
    {
        return $this->organization()->country();
    }

    /**
     * @return Organization
     *
     * @throws ZettleRestException
     */
    private function organization(): Organization
    {
        return $this->provider->provide();
    }

    public function taxRates(): array
    {
        // TODO: if later we retrieve tax rates from Zettle, could be a good idea to check that they match
        throw new Exception('Not implemented');
    }
}
