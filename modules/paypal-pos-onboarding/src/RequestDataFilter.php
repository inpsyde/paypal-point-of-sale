<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding;

class RequestDataFilter
{
    private array $requestData;
    public function __construct(array $requestData)
    {
        $this->requestData = $requestData;
    }
    public function guard(mixed ...$things): bool
    {
        foreach ($things as $thing) {
            if (!$this->guardField($thing)) {
                return \false;
            }
        }
        return \true;
    }
    private function guardField(mixed $thing): bool
    {
        if (is_string($thing)) {
            $found = array_key_exists($thing, $this->requestData);
            if (!$found) {
                return \false;
            }
        }
        if (is_array($thing)) {
            $key = $thing[0];
            if (!array_key_exists($key, $this->requestData)) {
                return \false;
            }
            $compare = $thing[1];
            $found = filter_var($this->requestData[$key], \FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            if ($found !== $compare) {
                return \false;
            }
        }
        return \true;
    }
}
