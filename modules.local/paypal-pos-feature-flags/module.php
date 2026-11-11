<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags;

return static function (): FeatureFlagsModule {
    return new FeatureFlagsModule();
};
