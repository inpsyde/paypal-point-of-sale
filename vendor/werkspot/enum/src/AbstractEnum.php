<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Werkspot\Enum;

use BadMethodCallException;
use Syde\Vendor\Zettle\Doctrine\Common\Inflector\Inflector;
use InvalidArgumentException;
use ReflectionClass;
use Syde\Vendor\Zettle\Werkspot\Enum\Util\ClassNameConverter;
abstract class AbstractEnum
{
    protected $value;
    protected function __construct($value)
    {
        if (!$this->isValid($value)) {
            $message = 'Value [%s] is not matching any valid value of class "%s". Valid values are [%s].';
            throw new InvalidArgumentException(sprintf($message, $value, $this->getClassName(), self::getValidOptionsAsString()));
        }
        $this->value = $value;
    }
    /**
     * @return static
     */
    public static function get($value): self
    {
        return new static($value);
    }
    public static function __callStatic(string $methodName, array $arguments): self
    {
        foreach (self::getConstants() as $option => $value) {
            $expectedMethodName = lcfirst(Inflector::classify(strtolower($option)));
            if ($expectedMethodName === $methodName) {
                return new static($value);
            }
        }
        throw new BadMethodCallException(sprintf('%s::%s() does not exist', static::class, $methodName));
    }
    public function __call(string $methodName, array $arguments)
    {
        foreach (self::getConstants() as $option => $value) {
            $isaMethodName = 'is' . ucfirst(Inflector::classify(strtolower($option)));
            if ($isaMethodName === $methodName) {
                return $this->equals(static::get($value));
            }
        }
        throw new BadMethodCallException(sprintf('%s::%s() does not exist', static::class, $methodName));
    }
    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
    public function equals(self $other): bool
    {
        return get_class($other) === get_class($this) && $other->getValue() === $this->value;
    }
    public function __toString(): string
    {
        return (string) $this->value;
    }
    protected function isValid($value): bool
    {
        return in_array($value, self::getValidOptions(), \true);
    }
    public static function getValidOptions(): array
    {
        return array_values(self::getConstants());
    }
    protected function getClassName(): string
    {
        return ClassNameConverter::stripNameSpace(static::class);
    }
    private static function getConstants(): array
    {
        return (new ReflectionClass(static::class))->getConstants();
    }
    private static function getValidOptionsAsString(): string
    {
        return implode(', ', array_map(function ($option) {
            return var_export($option, \true);
        }, self::getValidOptions()));
    }
}
