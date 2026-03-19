<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings;

/**
 * Checks whether the value from WriteOnlyPasswordFieldRenderer is filled (not empty and does not contain placeholder).
 */
class WriteOnlyPasswordFieldChecker
{
    /**
     * @var string
     */
    protected $placeholderChar;
    /**
     * @var int
     */
    protected $maxPlaceholderLength;
    /**
     * @param string $placeholderChar The character used in the WriteOnlyPasswordFieldRenderer placeholder, such as '*'.
     * @param int $maxPlaceholderLength The max value length that can be considered a placeholder.
     * (to avoid false positives if the API key actually contains such character)
     */
    public function __construct(string $placeholderChar, int $maxPlaceholderLength)
    {
        $this->placeholderChar = $placeholderChar;
        $this->maxPlaceholderLength = $maxPlaceholderLength;
    }
    public function __invoke(string $value): bool
    {
        return !empty(trim($value)) && (!str_contains($value, $this->placeholderChar) || strlen($value) > $this->maxPlaceholderLength);
    }
}
