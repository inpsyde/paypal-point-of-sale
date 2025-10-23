<?php
declare(strict_types=1);

use Inpsyde\Queue\Queue\Job\Context;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Payload;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\ZettlePayload;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToManyMapInterface;
use Syde\PayPal\PointOfSale\Webhooks\EventName;
use Syde\PayPal\PointOfSale\Webhooks\Handler\InventoryBalanceChangedHandler;
use Syde\PayPal\PointOfSale\Webhooks\Job\InventoryBalanceChangedJob;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use Psr\Log\LoggerInterface;
use function Brain\Monkey\Functions\when;

class InventoryBalanceChangedHandlerTest extends BrainMonkeyWpTestCase
{
    private $inventoryBalanceChanged;

    private $variantIdMap;

    private $integrationUuid = 'our-plugin-123';

    private $logger;

    private $orgId = 'org123';
    private $msgId = 'msg123';

    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryBalanceChanged = Mockery::mock(InventoryBalanceChangedJob::class);
        $this->variantIdMap = Mockery::mock(OneToManyMapInterface::class);

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('info');
        $this->logger->shouldReceive('warning');

        $this->sut = new InventoryBalanceChangedHandler(
            $this->inventoryBalanceChanged,
            $this->logger,
            $this->variantIdMap,
            $this->integrationUuid
        );

        when('get_current_blog_id')->justReturn(1);
    }

    /**
     * @dataProvider acceptsTestData
     */
    public function testAccepts(Payload $payload, bool $expectedResult)
    {
        self::assertEquals($expectedResult, $this->sut->accepts($payload));
    }

    public function testHandle()
    {
        $payload = new ZettlePayload(
            EventName::INVENTORY_BALANCE_CHANGED,
            $this->orgId,
            $this->msgId,
            [
                'balanceBefore' => [
                    [
                        'variantUuid' => 'v1',
                        'balance' => 10,
                    ],
                    [
                        'variantUuid' => 'v2',
                        'balance' => 15,
                    ],
                    [
                        'variantUuid' => 'v3',
                        'balance' => 20,
                    ],
                ],
                'balanceAfter' => [
                    [
                        'variantUuid' => 'v3',
                        'balance' => 19,
                    ],
                    [
                        'variantUuid' => 'v2',
                        'balance' => 15,
                    ],
                    [
                        'variantUuid' => 'v1',
                        'balance' => 12,
                    ],
                ],
            ]
        );

        $this->variantIdMap->expects('localId')->with('v1')->andReturn(100);
        $this->variantIdMap->expects('localId')->with('v2')->andReturn(101);
        $this->variantIdMap->expects('localId')->with('v3')->andReturn(102);

        $changes = [];

        $this->inventoryBalanceChanged->expects('execute')->times(3)->andReturnUsing(function (
            Context $context
        ) use (&$changes) {
            $changes[$context->args()->localId] = $context->args()->change;

            return true;
        });

        $this->sut->handle($payload);

        ksort($changes);
        self::assertEquals(
            [
                100 => 2,
                101 => 0,
                102 => -1,
            ],
            $changes
        );
    }

    public function acceptsTestData()
    {
        yield [
            new ZettlePayload(
                EventName::INVENTORY_BALANCE_CHANGED,
                $this->orgId,
                $this->msgId,
                []
            ),
            true
        ];
        yield 'has externalUuid' => [
            new ZettlePayload(
                EventName::INVENTORY_BALANCE_CHANGED,
                $this->orgId,
                $this->msgId,
                ['externalUuid' => 'abc123']
            ),
            true
        ];
        yield 'wrong event' => [
            new ZettlePayload(
                'another-event',
                $this->orgId,
                $this->msgId,
                []
            ),
            false
        ];
        yield 'caused by us' => [
            new ZettlePayload(
                EventName::INVENTORY_BALANCE_CHANGED,
                $this->orgId,
                $this->msgId,
                ['externalUuid' => $this->integrationUuid]
            ),
            false
        ];
    }
}
