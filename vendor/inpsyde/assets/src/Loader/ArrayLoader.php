<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Assets\Loader;

use Syde\Vendor\Zettle\Inpsyde\Assets\Asset;
use Syde\Vendor\Zettle\Inpsyde\Assets\AssetFactory;
use Syde\Vendor\Zettle\Inpsyde\Assets\BaseAsset;
use Syde\Vendor\Zettle\Inpsyde\Assets\ConfigureAutodiscoverVersionTrait;
/**
 * @package Inpsyde\Assets\Loader
 */
class ArrayLoader implements LoaderInterface
{
    use ConfigureAutodiscoverVersionTrait;
    /**
     * @param mixed $resource
     *
     * @return Asset[]
     *
     * phpcs:disable Syde.Functions.ArgumentTypeDeclaration.NoArgumentType
     * @psalm-suppress MixedArgument
     */
    public function load($resource): array
    {
        $assets = array_map([AssetFactory::class, 'create'], (array) $resource);
        return array_map(function (Asset $asset): Asset {
            if ($asset instanceof BaseAsset) {
                $this->autodiscoverVersion ? $asset->enableAutodiscoverVersion() : $asset->disableAutodiscoverVersion();
            }
            return $asset;
        }, $assets);
    }
}
