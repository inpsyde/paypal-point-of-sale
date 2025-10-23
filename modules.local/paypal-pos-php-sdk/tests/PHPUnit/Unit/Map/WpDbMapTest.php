<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DB\Table;
use Syde\PayPal\PointOfSale\PhpSdk\Map\WpdbMap;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class WpDbMapTest extends BrainMonkeyWpTestCase
{

    public function testLocalId()
    {
        $expectedResult = rand(0, 99);
        $wpdb = Mockery::mock(wpdb::class);
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->once();
        $wpdb->shouldReceive('get_var')->once()->andReturn($expectedResult);
        $table = Mockery::mock(Table::class);
        $table->shouldReceive('name')->once()->andReturn('herp');
        $testee = new WpdbMap($wpdb, $table, 'foo', 1);
        $actualResult = $testee->localId('bar');
        $this->assertSame($expectedResult, $actualResult);
    }
}
