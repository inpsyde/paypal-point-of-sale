<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\Filter;

/**
 * Removes the element if the given function returns false for the value.
 */
class GenericSettingsValueFilter implements SettingsValueFilter
{
    protected string $key;
    /**
     * @var callable(string):bool
     */
    protected $valueChecker;
    /**
     * @param string $key The key in the settings array.
     * @param callable(string):bool $valueChecker A function returning bool if the given value needs to be removes.
     */
    public function __construct(string $key, callable $valueChecker)
    {
        $this->key = $key;
        $this->valueChecker = $valueChecker;
    }
    public function filterSettingsValues(array $settings): array
    {
        $value = $settings[$this->key] ?? '';
        if (!($this->valueChecker)($value)) {
            unset($settings[$this->key]);
        }
        return $settings;
    }
}
