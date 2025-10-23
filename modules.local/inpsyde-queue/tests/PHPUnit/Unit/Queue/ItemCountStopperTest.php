<?php
declare(strict_types = 1);

namespace Inpsyde\OneStock\UnitTests\Queue;


use Inpsyde\Queue\Queue\ItemsCountStopper;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class ItemCountStopperTest extends BrainMonkeyWpTestCase
{

    public function testTimer()
    {
        $maxItems = 10;
        $rounds = 0;
        $testee = new ItemsCountStopper($maxItems);
        $runForResult = $testee->start();
        do {
            $rounds++;
        } while (! $testee->isStopped());
        self::assertEquals($maxItems, $rounds);
        self::assertTrue($runForResult);
    }
}
