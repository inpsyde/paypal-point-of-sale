<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Validator;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\LazyImage;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Image\ImageNotFoundException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Image\InvalidImageSizeException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Image\UnsupportedImageFileSizeException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Image\UnsupportedImageFileTypeException;
/**
 * Technically, Validators are supposed to inspect just the entity itself,
 * but since the ImageInterface is currently all about web-URLs, the transition
 * from a local WordPress image to a remote iZettle image is a bit of an edge-case.
 * Sure, we could download the image from our own WordPress installation to inspect it
 * before syncing, but that would be pretty insane. So we allow ourselves to use the stored data
 * from the WordPress Attachment Information directly.
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
 */
class WordPressImageValidator implements ValidatorInterface
{
    /**
     * @var string[]
     */
    protected array $supportedImageTypes;
    protected int $minFileSize;
    protected int $maxFileSize;
    protected int $minWidth;
    protected int $minHeight;
    protected int $maxWidth;
    protected int $maxHeight;
    /**
     * @param string[] $supportedImageTypes MIME subtypes like 'jpeg', 'png'
     */
    public function __construct(array $supportedImageTypes, int $minFileSize, int $maxFileSize, int $minWidth, int $minHeight, int $maxWidth, int $maxHeight)
    {
        $this->supportedImageTypes = $supportedImageTypes;
        $this->minFileSize = $minFileSize;
        $this->maxFileSize = $maxFileSize;
        $this->minWidth = $minWidth;
        $this->minHeight = $minHeight;
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
    }
    /**
     * @inheritDoc
     */
    public function accepts($entity): bool
    {
        return $entity instanceof LazyImage;
    }
    /**
     * @inheritDoc
     */
    public function validate($entity): bool
    {
        assert($entity instanceof LazyImage);
        $attachment = wp_prepare_attachment_for_js($entity->localId());
        if (!$attachment) {
            throw new ImageNotFoundException("No Attachment found for Attachment ID: {$entity->localId()}");
        }
        /**
         * @psalm-suppress RedundantCastGivenDocblockType
         */
        $fileName = (string) $attachment['filename'];
        /**
         * @psalm-suppress RedundantCastGivenDocblockType
         */
        $this->validateImageFileSize((int) $attachment['filesizeInBytes'], $fileName);
        $this->validatedImageType(strtolower($attachment['subtype']), $fileName);
        $this->validateImageSize($attachment['width'], $attachment['height'], $fileName);
        return \true;
    }
    protected function validateImageFileSize(int $imageFileSize, string $fileName): void
    {
        if ($imageFileSize >= $this->maxFileSize) {
            throw new UnsupportedImageFileSizeException(sprintf('Maximum image file size is %1$d bytes. [%2$s]', $this->maxFileSize, $fileName));
        }
        if ($imageFileSize <= $this->minFileSize) {
            throw new UnsupportedImageFileSizeException(sprintf('Minimum image file size is %1$d bytes. [%2$s]', $this->minFileSize, $fileName));
        }
    }
    protected function validatedImageType(string $type, string $fileName): void
    {
        if (!in_array($type, $this->supportedImageTypes, \true)) {
            throw new UnsupportedImageFileTypeException(sprintf('Image type %1$s is not supported. Must be one of %2$s. [%3$s]', $type, implode(', ', array_map('strtoupper', array_unique($this->supportedImageTypes))), $fileName));
        }
    }
    protected function validateImageSize(int $width, int $height, string $fileName): void
    {
        if ($width < $this->minWidth || $height < $this->minHeight) {
            throw new InvalidImageSizeException(sprintf('Image is too small. Must be at least: \'%1$dx%2$d\'. [%3$s]', $this->minWidth, $this->minHeight, $fileName));
        }
        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            throw new InvalidImageSizeException(sprintf('Image is too large. Must be at most: \'%1$dx%2$d\'. [%3$s]', $this->maxWidth, $this->maxHeight, $fileName));
        }
    }
}
