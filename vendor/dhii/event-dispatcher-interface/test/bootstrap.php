<?php

namespace Syde\Vendor\Zettle;

\call_user_func_array(function ($filePath) {
    $root = \dirname(\dirname($filePath));
    $autoload = "{$root}/vendor/autoload.php";
    if (\file_exists($autoload)) {
        require_once $autoload;
    }
}, [__FILE__]);
