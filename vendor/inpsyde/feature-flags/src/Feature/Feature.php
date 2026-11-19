<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Feature;

/**
 * The information about the feature flag. Can be useful when creating UI.
 */
interface Feature
{
    public function getName(): string;
    public function getDescription(): string;
    public function getLabel(): string;
}
