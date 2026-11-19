<?php

namespace Syde\Vendor\Zettle;

use Syde\Vendor\Zettle\Rector\Config\RectorConfig;
use Syde\Vendor\Zettle\Rector\Set\ValueObject\SetList;
return static function (RectorConfig $config): void {
    $config->sets([SetList::PHP_52, SetList::PHP_53, SetList::PHP_54, SetList::PHP_56, SetList::PHP_70, SetList::PHP_71, SetList::PHP_72, SetList::PHP_73, SetList::PHP_74, SetList::PHP_80, SetList::PHP_81, SetList::PHP_82, SetList::PHP_83, SetList::PHP_84]);
};
