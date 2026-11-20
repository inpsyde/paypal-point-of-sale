<?php

namespace Syde\Vendor\Zettle;

use Syde\Vendor\Zettle\Rector\Config\RectorConfig;
use Syde\Vendor\Zettle\Rector\Set\ValueObject\DowngradeLevelSetList;
return static function (RectorConfig $config): void {
    $config->sets([DowngradeLevelSetList::DOWN_TO_PHP_82]);
};
