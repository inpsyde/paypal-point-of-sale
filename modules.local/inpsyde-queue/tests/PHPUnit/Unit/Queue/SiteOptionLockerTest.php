<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\OneStock\UnitTests\Queue;

use Syde\PayPal\PointOfSale\Operator\Option\OptionOperatorInterface;
use Inpsyde\Queue\Queue\SiteOptionLocker;
use Mockery;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class SiteOptionLockerTest extends BrainMonkeyWpTestCase
{
    private $key = 'foo';

    private $optionOperator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->optionOperator = Mockery::mock(OptionOperatorInterface::class);
    }

    public function testLock()
    {
        $this->optionOperator
            ->shouldReceive('get')
            ->once()
            ->with($this->key, 0)
            ->andReturn(0);

        $testee = new SiteOptionLocker($this->optionOperator, 60, $this->key);

        $beforeLocking = $testee->isLocked();
        self::assertFalse($beforeLocking);

        $this->optionOperator
            ->shouldReceive('update')
            ->once()
            ->andReturn(true);
        $testee->lock();

        $this->optionOperator
            ->shouldReceive('get')
            ->once()
            ->with($this->key, 0)
            ->andReturn(time());

        $afterLocking = $testee->isLocked();
        self::assertTrue($afterLocking);
    }

    public function testUnlock()
    {
        $this->optionOperator
            ->shouldReceive('get')
            ->once()
            ->with($this->key, 0)
            ->andReturn(time());

        $testee = new SiteOptionLocker($this->optionOperator, 60, $this->key);

        $beforeUnlocking = $testee->isLocked();
        self::assertTrue($beforeUnlocking);

        $this->optionOperator
            ->shouldReceive('update')
            ->once()
            ->with($this->key, 0)
            ->andReturn(true);

        $testee->unlock();

        $this->optionOperator
            ->shouldReceive('get')
            ->once()
            ->with($this->key, 0)
            ->andReturn(0);

        $afterUnlocking = $testee->isLocked();
        self::assertFalse($afterUnlocking);
    }

    public function testUnlockAfterTimeout()
    {
        $timeOut = 60;
        $this->optionOperator
            ->shouldReceive('get')
            ->once()
            ->with($this->key, 0)
            ->andReturn(time() - $timeOut - 1);

        $testee = new SiteOptionLocker($this->optionOperator, $timeOut, $this->key);

        $result = $testee->isLocked();
        self::assertFalse($result);
    }
}
