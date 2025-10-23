<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\Operator\Option\OptionOperatorInterface;

class OptionOperator implements OptionOperatorInterface
{
    private $data = [];

    /**
     * @param array $initialData
     */
    public function __construct(array $initialData = [])
    {
        $this->data = array_merge([
            'woocommerce_currency' => 'GBP',
            'woocommerce_weight_unit' => 'kg',
        ], $initialData);
    }

    public function get(string $key, $default = false)
    {
        if (!array_key_exists($key, $this->data)) {
            return $default;
        }

        return $this->data[$key];
    }

    public function update(string $key, $value): bool
    {
        $this->data[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        if (!array_key_exists($key, $this->data)) {
            return false;
        }

        unset($this->data[$key]);

        return true;
    }
}
