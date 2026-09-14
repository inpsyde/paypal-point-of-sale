<?php

declare (strict_types=1);
namespace Inpsyde\Assets\OutputFilter;

use Inpsyde\Assets\FilterAwareAsset;
/**
 * @deprecated use Asset::withAttributes(['defer' => true']);
 */
class DeferScriptOutputFilter implements \Inpsyde\Assets\OutputFilter\AssetOutputFilter
{
    public function __invoke(string $html, FilterAwareAsset $asset): string
    {
        return str_replace('<script ', '<script defer ', $html);
    }
}
