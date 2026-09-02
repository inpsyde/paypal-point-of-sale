<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Assets;

use Syde\Vendor\Zettle\Inpsyde\Assets\OutputFilter\AssetOutputFilter;
interface FilterAwareAsset extends Asset
{
    /**
     * A list of assigned output filters to change the rendered tag.
     *
     * @return callable[]|AssetOutputFilter[]|class-string<AssetOutputFilter>[]
     */
    public function filters(): array;
    /**
     * @param callable|class-string<AssetOutputFilter> ...$filters
     *
     * @return Asset
     *
     * phpcs:disable Syde.Functions.ArgumentTypeDeclaration.NoArgumentType
     */
    public function withFilters(...$filters): Asset;
    /**
     * @return array<string, mixed>
     */
    public function attributes(): array;
    /**
     * @param array<string, mixed> $attributes
     *
     * @return Asset
     */
    public function withAttributes(array $attributes): Asset;
}
