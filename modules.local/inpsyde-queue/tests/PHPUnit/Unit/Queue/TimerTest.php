<?php
declare(strict_types=1);

namespace Inpsyde\OneStock\UnitTests\Queue;

use Inpsyde\Queue\Queue\TimeStopper as Testee;
use MonkeryTestCase\BrainMonkeyWpTestCase;

/**
 * Class TimerTest
 * @package Inpsyde\OneStock\UnitTests\Queue
 */
class TimerTest extends BrainMonkeyWpTestCase
{
    /**
     * Test Timer Instance
     */
    public function testInstance()
    {
        $testee = new Testee(1);

        self::assertInstanceOf(Testee::class, $testee);
    }

    /**
     * Test Timer Run Successful
     */
    public function testTimerRunSuccessful()
    {
        $seconds = 1;
        $testee = new Testee($seconds);

        $runForResult = $testee->start();

        self::assertTrue($runForResult);
    }

    /**
     * Test Time isn't Stopped after Run Start
     */
    public function testTimerNotStoppedAfterRunStart()
    {
        $seconds = 1;
        $testee = new Testee($seconds);

        $testee->start();

        self::assertFalse($testee->isStopped());
    }

    /**
     * Test Timer doesn't Stop Before the Given Seconds Passed
     */
    public function testTimerDoesNotStopBeforeGivenSecondsPassed()
    {
        $seconds = 1;
        $testee = new Testee($seconds);

        $testee->start();

        // Cannot be a millisecond precise number.
        usleep(990 * 1000);

        self::assertFalse($testee->isStopped());
    }

    /**
     * Test Timer is Stopped After a
     */
    public function testTimerStoppedAfterTimeout()
    {
        $seconds = 1;
        $testee = new Testee($seconds);

        $testee->start();

        sleep(2);

        self::assertTrue($testee->isStopped());
    }
}
