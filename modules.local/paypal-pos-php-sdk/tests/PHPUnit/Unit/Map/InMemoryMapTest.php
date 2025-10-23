<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Map\InMemoryMap;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class InMemoryMapTest extends BrainMonkeyWpTestCase
{

    public function testFailsForMissingRemoteId()
    {
        $testee = new InMemoryMap();
        $this->expectException(IdNotFoundException::class);
        $testee->remoteId(1);
    }

    public function testFailsForMissingLocalId()
    {
        $testee = new InMemoryMap();
        $this->expectException(IdNotFoundException::class);
        $testee->localId('foo');
    }

    /**
     * @dataProvider singleRemoteIdTestProvider
     */
    public function testLocalId(array $map, $testLocalId, $testRemoteId)
    {
        $testee = new InMemoryMap($map);
        $key = current($map);
        if (is_array($key)) {
            foreach ($key as $remoteId) {
                $actual = $testee->localId($remoteId);
                $this->assertSame($testLocalId, $actual);
            }

            return;
        }
        $this->assertSame($testLocalId, $testee->localId($key));
    }

    /**
     * @dataProvider singleRemoteIdTestProvider
     */
    public function testRemoteId(array $map, $localId, $expectedRemoteId)
    {
        $testee = new InMemoryMap($map);
        $actual = $testee->remoteId($localId);
        $this->assertSame($expectedRemoteId, $actual);
    }

    /**
     * @dataProvider multipleRemoteIdTestProvider
     */
    public function testRemoteIds(array $map, $localId, $expectedRemoteId)
    {
        $testee = new InMemoryMap($map);
        $actual = $testee->remoteIds($localId);
        $this->assertSame($expectedRemoteId, $actual);
    }

    public function testCreateSingleRecord()
    {
        $localId = 1;
        $remoteId = 'foo';
        $testee = new InMemoryMap();
        $created = $testee->createRecord($localId, $remoteId);
        $this->assertTrue($created);
        $result = $testee->remoteId($localId);
        $this->assertSame($remoteId, $result);
    }

    public function testGetMeta()
    {
        $localId = 1;
        $remoteId = 'foo';
        $meta = ['hurr' => 'durr'];
        $testee = new InMemoryMap();
        $testee->createRecord($localId, $remoteId, $meta);
        $mapMeta = $testee->metaData($localId, $remoteId);
        $this->assertSame(
            $meta,
            $mapMeta,
            'Metadata should be returned after creating a new record containing it'

        );
        $testee->deleteRecord($localId, $remoteId);
        $mapMeta = $testee->metaData($localId, $remoteId);
        $this->assertNotSame(
            $meta,
            $mapMeta,
            'Metadata should no longer be present after deleting a record'
        );
    }

    public function testCreateMultipleRecords()
    {
        $localId = 1;
        $remoteIds = ['foo', 'bar', 'baz'];
        $testee = new InMemoryMap();
        foreach ($remoteIds as $remoteId) {
            $created = $testee->createRecord($localId, $remoteId);
            $this->assertTrue($created);
        }
        $result = $testee->remoteId($localId);
        $this->assertSame($remoteIds[0], $result);
    }

    public function singleRemoteIdTestProvider()
    {
        yield 'one_to_one' => [
            [1 => 'foo'],
            1,
            'foo',
        ];
        yield 'one_to_many' => [
            [1 => ['foo', 'bar', 'baz']],
            1,
            'foo',
        ];
    }

    public function multipleRemoteIdTestProvider()
    {
        yield 'one_to_one' => [
            [1 => 'foo'],
            1,
            ['foo'],
        ];
        yield 'one_to_many' => [
            [1 => ['foo', 'bar', 'baz']],
            1,
            ['foo', 'bar', 'baz'],
        ];
    }
}
