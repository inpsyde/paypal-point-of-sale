<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Uuid\Uuid;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use function Brain\Monkey\Functions\expect;

class UuidTest extends BrainMonkeyWpTestCase
{

    /**
     * @dataProvider defaultTestData
     */
    public function testUuid($timestamp, $id, $homeUrl)
    {
        expect('home_url')->andReturn($homeUrl);
        $dateTime = new DateTime($timestamp);
        $wcProduct = Mockery::mock(WC_Product::class);
        $wcProduct->shouldReceive('get_date_created')->andReturn($dateTime);
        $wcProduct->shouldReceive('get_id')->andReturn($id);
        $testee = Uuid::fromWcProduct($wcProduct);
        $this->assertTrue(uuid_is_valid((string) $testee));
        $secondInstance = Uuid::fromWcProduct($wcProduct);
        $this->assertSame((string) $testee, (string) $secondInstance);
    }

    public function defaultTestData()
    {
        yield '1' => [
            '2020-10-28 07:55:29',
            42,
            'https://foo.bar',
        ];
        yield '2' => [
            '2020-10-28 07:55:29',
            999,
            'https://somethingreallyreallyreallylong.itevenhasasubdomain.bar/abcdefghijklmnopqrstuvwxyz',
        ];
    }
}
