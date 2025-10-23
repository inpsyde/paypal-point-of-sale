<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits;

trait AssertArraySimilarTrait
{

    /**
     * Asserts that two associative arrays are similar.
     *
     * Both arrays must have the same indexes with identical values
     * without respect to key ordering
     *
     * @param array $expected
     * @param array $array
     */
    protected function assertArraySimilar(array $expected, array $array)
    {
        $this->assertEquals([], array_diff_key($array, $expected));

        foreach ($expected as $key => $value) {
            if (is_array($value)) {
                $this->assertArraySimilar($value, $array[$key]);
                continue;
            }
            $valueString = $this->stringable($value)
                ? (string) $value
                : get_class($value);
            $arrJson = json_encode($array);
            $this->assertContains($value, $array, "Asserting that array contains '{$key}' => {$valueString}. $arrJson");
        }
    }

    private function stringable($var): bool
    {
        return $var === null
            || is_scalar($var)
            || (is_object($var)
                && method_exists(
                    $var,
                    '__toString'
                )
            );
    }
}
