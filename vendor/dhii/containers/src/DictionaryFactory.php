<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Dhii\Container;

use Syde\Vendor\Zettle\Dhii\Collection\WritableMapFactoryInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
/**
 * @inheritDoc
 */
class DictionaryFactory implements WritableMapFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function createContainerFromArray(array $data): ContainerInterface
    {
        return new Dictionary($data);
    }
}
