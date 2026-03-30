<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder;

abstract class AbstractBuilder
{
    /**
     * @param string $key
     * @param array $data
     * @return mixed|null
     */
    // phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
    protected function getDataFromKey(string $key, array $data)
    {
        // phpcs:enable
        if (!array_key_exists($key, $data)) {
            return null;
        }
        return $data[$key];
    }
}
