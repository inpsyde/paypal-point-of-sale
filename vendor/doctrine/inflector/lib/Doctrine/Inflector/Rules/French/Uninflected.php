<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Doctrine\Inflector\Rules\French;

use Syde\Vendor\Zettle\Doctrine\Inflector\Rules\Pattern;
final class Uninflected
{
    /**
     * @return Pattern[]
     */
    public static function getSingular(): iterable
    {
        yield from self::getDefault();
    }
    /**
     * @return Pattern[]
     */
    public static function getPlural(): iterable
    {
        yield from self::getDefault();
    }
    /**
     * @return Pattern[]
     */
    private static function getDefault(): iterable
    {
        yield new Pattern('');
    }
}
