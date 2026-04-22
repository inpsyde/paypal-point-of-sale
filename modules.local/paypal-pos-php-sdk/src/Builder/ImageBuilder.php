<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ConcreteImage;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Image\UnexpectedImageUrlException;

class ImageBuilder implements BuilderInterface
{
    /**
     * @inheritDoc
     * phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    public function build(
        string $className,
        $payload,
        ?BuilderInterface $builder = null
    ): ImageInterface {

        assert(is_array($payload));
        $lookupKey = $this->findLookupKey($payload);

        return new ConcreteImage($lookupKey);
    }

    private function findLookupKey(array $payload): string
    {
        if (array_key_exists('imageUrl', $payload) && !empty($payload['imageUrl'])) {
            try {
                return $this->lookupKeyFromZettleUrl((string) $payload['imageUrl']);
            } catch (UnexpectedImageUrlException $exception) {
                return (string) current($payload);
            }
        }

        if (array_key_exists('imageLookupKey', $payload) && !empty($payload['imageUrl'])) {
            return $payload['imageLookupKey'];
        }

        return (string) current($payload);
    }

    /**
     * @param string $url
     *
     * @return string
     * @throws UnexpectedImageUrlException
     */
    private function lookupKeyFromZettleUrl(string $url): string
    {
        $matches = [];

        $result = preg_match(
            '~https://image.izettle.com/(?:product|productimage/[Lo])/(.*)~',
            $url,
            $matches
        );

        if (!$result) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new UnexpectedImageUrlException("Could not parse image url " . $url);
        }

        return $matches[1];
    }
}
