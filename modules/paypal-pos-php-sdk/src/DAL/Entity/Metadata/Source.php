<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Metadata;

class Source
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var bool
     */
    private $external;
    /**
     * @param string $name
     * @param bool $external
     */
    public function __construct(string $name, bool $external)
    {
        $this->name = $name;
        $this->external = $external;
    }
    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    /**
     * @return bool
     */
    public function isExternal(): bool
    {
        return $this->external;
    }
}
