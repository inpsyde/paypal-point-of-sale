<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Assets\OutputFilter;

use Syde\Vendor\Zettle\Inpsyde\Assets\FilterAwareAsset;
interface AssetOutputFilter
{
    /**
     * @param string $html
     * @param FilterAwareAsset $asset
     *
     * @return string $html
     */
    public function __invoke(string $html, FilterAwareAsset $asset): string;
}
