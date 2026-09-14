<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Validator;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption\VariantOption;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\VariantOption\MaximumVariantOptionNameCharacterLengthException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\VariantOption\MinimumVariantOptionNameCharacterLengthException;

// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint

class VariantOptionValidator implements ValidatorInterface
{
    public const MIN_NAME_LENGTH = 1;
    public const MAX_NAME_LENGTH = 30;

    public function accepts(mixed $entity): bool
    {
        return $entity instanceof VariantOption;
    }

    public function validate(mixed $entity): bool
    {
        assert($entity instanceof VariantOption);

        $name = $entity->name();
        /** @psalm-suppress RedundantCast */
        $nameLength = (int) mb_strlen($name);

        if ($nameLength < self::MIN_NAME_LENGTH) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new MinimumVariantOptionNameCharacterLengthException(esc_html($name), self::MIN_NAME_LENGTH);
        }

        if ($nameLength > self::MAX_NAME_LENGTH) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new MaximumVariantOptionNameCharacterLengthException(esc_html($name), self::MAX_NAME_LENGTH);
        }

        return true;
    }
}
