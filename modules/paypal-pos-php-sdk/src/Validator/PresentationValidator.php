<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Validator;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation\Presentation;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Presentation\InvalidHexColorException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Presentation\ShortHexColorException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
/**
 * Class PresentationValidator
 *
 * Verifies that a Presentation's color values are 6-digit hex strings
 * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\Validator
 */
class PresentationValidator implements ValidatorInterface
{
    public const HEX_COLOR_LENGTH = 6;
    public function accepts(mixed $entity): bool
    {
        return $entity instanceof Presentation && ($entity->textColor() !== null && $entity->backgroundColor() !== null);
    }
    public function validate(mixed $entity): bool
    {
        assert($entity instanceof Presentation);
        $this->assertValidLongHexValue($entity->backgroundColor());
        $this->assertValidLongHexValue($entity->textColor());
        return \true;
    }
    /**
     * @param string $string
     *
     * @throws ValidatorException
     */
    private function assertValidLongHexValue(string $string): void
    {
        $color = ltrim($string, '#');
        if (function_exists('ctype_xdigit') && !ctype_xdigit($color)) {
            throw new InvalidHexColorException(esc_html($color));
        }
        if (strlen($color) < self::HEX_COLOR_LENGTH) {
            throw new ShortHexColorException(esc_html($color), 'Presentation');
        }
    }
}
