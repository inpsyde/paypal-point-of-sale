<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Project;

use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Feature\Feature;
interface Project
{
    public function getName(): string;
    public function isEnabled(string $flag, bool $default = \false): bool;
    /**
     * @return Feature[]
     */
    public function getFeatures(): array;
}
