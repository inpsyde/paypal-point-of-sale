<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image;

use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\API\Image\Images;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Image\UrlProviderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\MapRecordCreator;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;

class LazyImage implements ImageInterface
{

    private const FALLBACK = 'https://placehold.co/200x200.jpg?text=WooProduct';

    /**
     * @var ImageInterface
     */
    private $image;

    /**
     * @var string
     */
    private $localUrl;

    /**
     * @var Images
     */
    private $imageClient;

    /**
     * @var OneToOneMapInterface
     */
    private $map;

    /**
     * @var int
     */
    private $localId;

    /**
     * @var UrlProviderInterface
     */
    private $urlProvider;

    /**
     * LazyImage constructor.
     *
     * @param int $localId
     * @param UrlProviderInterface $urlProvider
     * @param Images $imageClient
     * @param OneToOneMapInterface $map
     */
    public function __construct(
        int $localId,
        UrlProviderInterface $urlProvider,
        Images $imageClient,
        OneToOneMapInterface $map
    ) {
        $this->imageClient = $imageClient;
        assert($map instanceof MapRecordCreator);
        $this->map = $map;
        $this->localId = $localId;
        $this->urlProvider = $urlProvider;
    }

    /**
     * @inheritDoc
     */
    public function imageLookupKey(): string
    {
        return $this->ensureImage()->imageLookupKey();
    }

    /**
     * @inheritDoc
     */
    public function smallImageUrl(): string
    {
        return $this->ensureImage()->smallImageUrl();
    }

    /**
     * @inheritDoc
     */
    public function largeImageUrl(): string
    {
        return $this->ensureImage()->largeImageUrl();
    }

    private function ensureImage(): ImageInterface
    {
        if (!$this->image) {
            try {
                /**
                 * Check if an ID-map entry has been added since the creation of this instance.
                 * This might happen because of a concurrent web request.
                 */
                $existingLookupKey = $this->map->remoteId($this->localId);
                $this->image = new ConcreteImage($existingLookupKey);

                return $this->image;
            } catch (IdNotFoundException $exception) {
            }

            try {
                $url = $this->urlProvider->provide((string) $this->localId);
                $this->image = $this->syncImage($url);
            } catch (Exception $exception) {
                throw new Exception("Failed to sync image {$this->localId}: {$exception->getMessage()}");
            }

            $this->map->createRecord(
                $this->localId,
                $this->image->imageLookupKey()
            );
        }

        return $this->image;
    }

    /**
     * Syncs the image url to Zettle and returns the ImageInterface from the REST client.
     * Will attempt to recover once with a generic fallback image URL.
     * This is an additional safety net, but we actually expect $localId to be validated already
     *
     * @param string $url
     * @param bool $throw
     *
     * @return ImageInterface
     * @throws ZettleRestException
     */
    private function syncImage(string $url, bool $throw = false): ImageInterface
    {
        try {
            return $this->imageClient->create($url);
        } catch (ZettleRestException $exception) {
            if ($throw) {
                throw $exception;
            }
            return $this->syncImage(self::FALLBACK, true);
        }
    }

    public function identifier(): string
    {
        return $this->localUrl;
    }

    /**
     * @return int
     */
    public function localId(): int
    {
        return $this->localId;
    }
}
