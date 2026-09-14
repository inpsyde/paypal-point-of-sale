<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Werkspot\Enum\Util;

final class ClassNameConverter
{
    public static function convertClassNameToServiceName(string $className): string
    {
        $className = str_replace('\\', '.', $className);
        $serviceName = lcfirst($className);
        $serviceName = preg_replace('/([A-Z])/', '_$1', $serviceName);
        $serviceName = str_replace('._', '.', $serviceName);
        return strtolower($serviceName);
    }
    public static function convertServiceNameToClassName(string $serviceName): string
    {
        $className = preg_replace_callback('/\.([a-z])/', function ($c) {
            return '\\' . ucfirst($c[1]);
        }, $serviceName);
        $className = preg_replace_callback('/_([a-z])/', function ($c) {
            return ucfirst($c[1]);
        }, $className);
        return ucfirst($className);
    }
    public static function stripNameSpace(string $className): string
    {
        return preg_replace('|.+\\\\|', '', $className);
    }
}
