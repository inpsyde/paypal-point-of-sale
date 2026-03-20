<?php

declare(strict_types=1);

namespace Syde\Vendor\Zettle\Inpsyde\Modularity\Module;

/**
 * @package Syde\Vendor\Zettle\Inpsyde\Modularity\Module
 */
interface Module
{
    /**
     * Unique identifier for your Module.
     *
     * @return string
     */
    public function id(): string;
}
