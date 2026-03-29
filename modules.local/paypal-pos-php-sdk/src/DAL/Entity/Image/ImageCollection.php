<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image;

final class ImageCollection
{
    /**
     * @var ImageInterface[]
     */
    private array $collection = [];

    /**
     * ImageCollection constructor.
     *
     * @param ImageInterface[] $images
     */
    public function __construct(ImageInterface ...$images)
    {
        foreach ($images as $image) {
            $this->add($image);
        }
    }

    /**
     * @param ImageInterface $image
     *
     * @return ImageCollection
     */
    public function add(ImageInterface $image): self
    {
        $this->collection[spl_object_hash($image)] = $image;

        return $this;
    }

    /**
     * @param ImageInterface $image
     *
     * @return ImageCollection
     */
    public function remove(ImageInterface $image): self
    {
        unset($this->collection[spl_object_hash($image)]);

        return $this;
    }

    /**
     * @param string $imageLookupkey
     *
     * @return ImageInterface
     */
    public function get(string $imageLookupkey): ImageInterface
    {
        foreach ($this->collection as $item) {
            if ($item->imageLookupKey() === $imageLookupkey) {
                return $item;
            }
        }
    }

    /**
     * @return ImageInterface[]
     */
    public function all(): array
    {
        return $this->collection;
    }

    public function reset(): self
    {
        $this->collection = [];

        return $this;
    }
}
