<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Assets\Handler;

use Syde\Vendor\Zettle\Inpsyde\Assets\Asset;
interface AssetHandler
{
    /**
     * @param Asset $asset
     *
     * @return bool
     */
    public function register(Asset $asset): bool;
    /**
     * @param Asset $asset
     *
     * @return bool
     */
    public function enqueue(Asset $asset): bool;
}
