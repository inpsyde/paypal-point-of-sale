<?php
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Test;

class StubLoader
{

    private static $loaded = false;

    public static function load()
    {
        if (self::$loaded) {
            return;
        }
        $dir = dirname(__DIR__).'/Stubs/';

        spl_autoload_register(
            function ($class_name) use ($dir) {
                if (is_readable($file = $dir.$class_name.'.php')) {
                    include_once($file);
                }
            }
        );
        self::$loaded = true;
    }
}
