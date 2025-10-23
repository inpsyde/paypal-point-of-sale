<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\CachedIdMap;
use Syde\PayPal\PointOfSale\PhpSdk\Map\LocalIdProvider;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToManyMapInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class CachedIdMapTest extends BrainMonkeyWpTestCase
{

    public function testCachedRemoteIdWithExisting()
    {
        $localId = 42;
        $remoteId = 'foo';
        $base = Mockery::mock(OneToOneMapInterface::class);
        $base->shouldReceive('remoteId')->once()->with($localId)->andReturn($remoteId);
        $testee = new CachedIdMap($base);
        $result = $testee->remoteId($localId);
        $this->assertSame($remoteId, $result);
        // Second call: same result, but base should not be called again
        $result = $testee->remoteId($localId);
        $this->assertSame($remoteId, $result);
    }

    public function testCachedLocalIdWithExisting()
    {
        $localId = 42;
        $remoteId = 'foo';
        $base = Mockery::mock(OneToOneMapInterface::class);
        $base->shouldReceive('localId')->once()->with($remoteId)->andReturn($localId);
        $testee = new CachedIdMap($base);
        $result = $testee->localId($remoteId);
        $this->assertSame($localId, $result);
        // Second call: same result, but base should not be called again
        $result = $testee->localId($remoteId);
        $this->assertSame($localId, $result);
    }

    public function testExceptionForMissingRemoteImplementation()
    {
        $localId = 42;
        $base = Mockery::mock(LocalIdProvider::class);
        $base->shouldNotReceive('remoteId');
        $this->expectException(IdNotFoundException::class);
        $testee = new CachedIdMap($base);
        $testee->remoteId($localId);
    }

    public function testExceptionForMissingMultiRemoteImplementation()
    {
        $localId = 42;
        $base = Mockery::mock(LocalIdProvider::class);
        $base->shouldNotReceive('remoteIds');
        $this->expectException(IdNotFoundException::class);
        $testee = new CachedIdMap($base);
        $testee->remoteIds($localId);
    }

    public function testCachedRemoteIdsWithExisting()
    {
        $localId = 42;
        $remoteIds = ['foo', 'bar', 'baz'];
        $base = Mockery::mock(OneToManyMapInterface::class);
        $base->shouldReceive('remoteIds')->once()->with($localId)->andReturn($remoteIds);
        $testee = new CachedIdMap($base);
        $result = $testee->remoteIds($localId);
        $this->assertSame($remoteIds, $result);
        // Second call: same result, but base should not be called again
        $result = $testee->remoteIds($localId);
        $this->assertSame($remoteIds, $result);
    }
}
