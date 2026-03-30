<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Inventory;

use Syde\Vendor\Zettle\Psr\Http\Message\UriInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Inventory\Inventory as InventoryEntity;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Inventory\Transaction;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Location\Location;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\RestClientInterface;
class Inventory
{
    private $uri;
    private RestClientInterface $restClient;
    private Locations $locationsClient;
    /**
     * @var Location[]
     */
    private array $locations;
    private BuilderInterface $builder;
    private string $integrationUuid;
    public function __construct(UriInterface $uri, RestClientInterface $restClient, Locations $locationsClient, BuilderInterface $builder, string $integrationUuid)
    {
        $this->uri = $uri;
        $this->restClient = $restClient;
        $this->locationsClient = $locationsClient;
        $this->builder = $builder;
        $this->integrationUuid = $integrationUuid;
    }
    /**
     * @return Location[]
     * @throws ZettleRestException
     */
    private function locations(): array
    {
        if (!$this->locations) {
            $this->locations = $this->locationsClient->all();
        }
        return $this->locations;
    }
    /**
     * @param Transaction[] $transactions
     *
     * @throws ZettleRestException
     */
    public function performTransactions(Transaction ...$transactions): void
    {
        $url = (string) $this->uri->withPath('/v3/movements');
        $changes = [];
        foreach ($transactions as $transaction) {
            $changes[] = ['productUuid' => $transaction->productUuid(), 'variantUuid' => $transaction->variantUuid(), 'from' => $transaction->fromLocationUuid(), 'to' => $transaction->toLocationUuid(), 'change' => $transaction->change()];
        }
        $payload = ['movements' => $changes, 'identifier' => $this->integrationUuid];
        $this->restClient->post($url, $payload);
    }
    /**
     * @param string $productUuid
     * @param string $variantUuid
     * @param int $change
     *
     * @throws ZettleRestException
     */
    public function purchase(string $productUuid, string $variantUuid, int $change): void
    {
        $locations = $this->locations();
        $transaction = new Transaction($productUuid, $variantUuid, $locations['STORE']->uuid(), $locations['SOLD']->uuid(), $change);
        $this->performTransactions($transaction);
    }
    /**
     * @param string $productUuid
     * @param string $variantUuid
     * @param string $from
     * @param string $to
     * @param int $change
     *
     * @throws ZettleRestException
     */
    public function moveStock(string $productUuid, string $variantUuid, string $from, string $to, int $change): void
    {
        $locations = $this->locations();
        $transaction = new Transaction($productUuid, $variantUuid, $locations[$from]->uuid(), $locations[$to]->uuid(), $change);
        $this->performTransactions($transaction);
    }
    /**
     * @param string $productUuid
     * @param string $variantUuid
     * @param int $change
     *
     * @throws ZettleRestException
     */
    public function supply(string $productUuid, string $variantUuid, int $change): void
    {
        $locations = $this->locations();
        $transaction = new Transaction($productUuid, $variantUuid, $locations['SUPPLIER']->uuid(), $locations['STORE']->uuid(), $change);
        $this->performTransactions($transaction);
    }
    /**
     * @param string $productUuid
     *
     * @throws ZettleRestException
     */
    public function startTracking(string $productUuid): void
    {
        $this->setTracking($productUuid, \true);
    }
    /**
     * @param string $productUuid
     *
     * @throws ZettleRestException
     */
    public function stopTracking(string $productUuid): void
    {
        $this->setTracking($productUuid, \false);
    }
    /**
     * @param string $productUuid
     * @param bool $enable
     *
     * @throws ZettleRestException
     */
    private function setTracking(string $productUuid, bool $enable): void
    {
        $url = (string) $this->uri->withPath('/v3/products');
        $payload = [['productUuid' => $productUuid, 'tracking' => $enable ? 'enable' : 'disable']];
        $this->restClient->post($url, $payload);
    }
    /**
     * @param string $productUuid
     * @param string $locationType
     *
     * @return InventoryEntity
     * @throws ZettleRestException
     */
    public function productInventory(string $productUuid, string $locationType): InventoryEntity
    {
        $locations = $this->locations();
        $locationUuid = $locations[$locationType]->uuid();
        $url = (string) $this->uri->withPath("/v3/stock/{$locationUuid}/products/{$productUuid}");
        $result = $this->restClient->get($url, []);
        try {
            return $this->builder->build(InventoryEntity::class, $result);
        } catch (BuilderException $exception) {
            throw new ZettleRestException(sprintf('Could not build Inventory entity of product %s after fetching it', $productUuid), 0, $result, [], $exception);
        }
    }
}
