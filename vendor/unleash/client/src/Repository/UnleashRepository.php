<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Repository;

use Syde\Vendor\Zettle\Unleash\Client\DTO\Feature;
/**
 * @method void refreshCache()
 */
interface UnleashRepository
{
    public function findFeature(string $featureName): ?Feature;
    /**
     * @return iterable<Feature>
     */
    public function getFeatures(): iterable;
}
