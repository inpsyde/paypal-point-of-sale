<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception;

interface ValidationErrorCodes
{
    public const NO_VARIANTS = 'no-variants';
    public const TOO_MANY_VARIANTS = 'too-many-variants';
    public const NO_VARIANT_OPTIONS = 'no-variant-options';
    public const TOO_MANY_VARIANT_OPTIONS = 'too-many-variant-options';
    public const VARIANT_OPTION_AMOUNT_MISMATCH = 'variant-option-amount-mismatch';
    public const TOO_SHORT_VARIANT_NAME = 'too-short-variant-name';
    public const TOO_LONG_VARIANT_NAME = 'too-long-variant-name';
    public const DIFFERING_VARIANT_TAXES = 'differing-variant-taxes';
    public const TOO_BIG_STOCK = 'too-big-stock';
    public const INVALID_HEX_COLOR = 'invalid-hex-color';
    public const SHORT_HEX_COLOR = 'short-hex-color';
    public const IMAGE_NOT_FOUND = 'image-not-found';
    public const INVALID_IMAGE_SIZE = 'invalid-image-size';
    public const UNSUPPORTED_IMAGE_FILE_SIZE = 'unsupported-image-file-size';
    public const UNSUPPORTED_IMAGE_FILE_TYPE = 'unsupported-image-file-type';
    public const UNEXPECTED_IMAGE_URL = 'unexpected-image-url';
    public const INVALID_COORDINATES = 'invalid-coordinates';
    public const INVALID_PAYMENT_TYPE = 'invalid-payment-type';
    public const TAX_RATE_NOT_FOUND = 'tax-rate-not-found';
    public const UNEXPECTED_PAYLOAD_TYPE = 'unexpected-payload-type';
}
