<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption;

/**
 * Unique Data Set for Attributes
 */
class AttributeSet
{
    /**
     * @var array<string, string[]>
     */
    protected $set = [];
    /**
     * @param string $type
     * @param string $attribute
     */
    public function add(string $type, string $attribute): void
    {
        if (empty($this->set[$type])) {
            $this->set[$type] = [];
        }
        if (in_array($attribute, $this->set[$type], \true)) {
            return;
        }
        $this->set[$type][] = $attribute;
    }
    /**
     * @param string $type
     */
    public function remove(string $type): void
    {
        if (isset($this->set[$type])) {
            unset($this->set[$type]);
        }
    }
    /**
     * @param string $type
     *
     * @return array
     */
    public function get(string $type): array
    {
        if ($this->has($type)) {
            $value = $this->set[$type];
            if (!is_array($value)) {
                return [$value];
            }
            return $value;
        }
        return [];
    }
    /**
     * @param string $type
     *
     * @return bool
     */
    public function has(string $type): bool
    {
        return array_key_exists($type, $this->set);
    }
    /**
     * Returns the Set as array
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->set;
    }
}
