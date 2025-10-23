<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Organization;

use DateTime;
use DateTimeZone;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\Organization;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;

use function get_transient;

/**
 * Organization data rarely changes, so it can be cached locally using this Decorator
 */
class TransientCachingOrganizationProvider implements OrganizationProvider
{

    /**
     * @var Organization|null
     */
    private $cache;

    /**
     * Transient Identifier Key
     *
     * @var string
     */
    private $key;

    /**
     * @var int
     */
    private $expiration;

    /**
     * @var OrganizationProvider
     */
    private $provider;

    public function __construct(
        OrganizationProvider $provider,
        string $key,
        int $expiration = 0
    ) {

        $this->provider = $provider;
        $this->key = $key;
        $this->expiration = $expiration;
    }

    /**
     * Get the Organization Data from transient
     *
     * @return Organization
     *
     * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
     * @throws ZettleRestException
     */
    public function provide(): Organization
    {
        return $this->getCached() ?? $this->delegateAndCache();
    }

    private function getCached(): ?Organization
    {
        if ($this->cache) {
            return $this->cache;
        }

        $serialized = get_transient($this->key);
        if (empty($serialized)) {
            return null;
        }

        $unSerialized = unserialize(
            $serialized,
            ['allowed_classes' => [
                Organization::class,
                Vat::class,
                DateTimeZone::class,
                DateTime::class,
            ], ]
        );
        if ($unSerialized instanceof Organization) {
            $this->cache = $unSerialized;

            return $unSerialized;
        }

        return null;
    }

    /**
     * @return Organization
     * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
     * @throws ZettleRestException
     */
    private function delegateAndCache(): Organization
    {
        $result = $this->provider->provide();
        set_transient($this->key, serialize($result), $this->expiration);

        return $result;
    }
}
