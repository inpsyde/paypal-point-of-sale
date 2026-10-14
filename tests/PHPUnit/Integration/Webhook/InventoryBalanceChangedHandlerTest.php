<?php
declare(strict_types=1);

use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Syde\PayPal\PointOfSale\PhpSdk\Map\InMemoryMap;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use Syde\PayPal\PointOfSale\Test\AuthenticatedRestRequestTestCase;
use Syde\PayPal\PointOfSale\Webhooks\Handler\InventoryBalanceChangedHandler;
use Syde\PayPal\PointOfSale\Webhooks\Handler\WebhookHandler;
use Syde\PayPal\PointOfSale\Webhooks\Job\InventoryBalanceChangedJob;
use Syde\PayPal\PointOfSale\Webhooks\Rest\WebhookListenerEndpoint;
use Mockery\MockInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class InventoryBalanceChangedHandlerTest extends AuthenticatedRestRequestTestCase
{

    private $integrationUuid = '947d712a-892d-11ea-bc55-0242ac130003';

    protected function setUp(): void
    {
        $this->injectFactory(
            'paypal-pos.sdk.id-map.product',
            function (ContainerInterface $container): OneToOneMapInterface {
                return new InMemoryMap();
            }
        );

        $this->injectFactory(
            'paypal-pos.sdk.id-map.image',
            function (ContainerInterface $container): OneToOneMapInterface {
                return new InMemoryMap();
            }
        );

        $this->injectFactory(
            'paypal-pos.sdk.integration-id',
            function (ContainerInterface $container): string {
                return $this->integrationUuid;
            }
        );

        parent::setUp();
    }

    /**
     * @dataProvider restRequestProvider
     *
     * @param WP_REST_Request $request
     * @param int $expectedChange
     * @param bool $successfullyExecuted
     */
    public function testHandler(
        WP_REST_Request $request,
        int $expectedChange,
        bool $successfullyExecuted
    ) {
        $wcProductId = 12;

        $this->injectFactory(
            'paypal-pos.sdk.id-map.variant',
            function (ContainerInterface $container) use ($wcProductId): OneToOneMapInterface {
                return new InMemoryMap(
                    [
                        $wcProductId => '24134200-fb65-11e7-8103-e11ba136a59d',
                    ]
                );
            }
        );

        $this->injectFactory(
            'paypal-pos.jobs.' . InventoryBalanceChangedJob::TYPE,
            function () use ($expectedChange, $successfullyExecuted) {
                $inventoryBalanceChangedJob = Mockery::mock(InventoryBalanceChangedJob::class);
                $loggerMock = Mockery::mock(LoggerInterface::class);

                $inventoryBalanceChangedJob->shouldReceive('execute')
                    ->once()
                    ->with(
                        Context::fromArray(
                            [
                                'messageUuid' => '1c93a601-1420-5c05-b0ba-f4d80743c55f',
                                'localId' => 12,
                                'change' => $expectedChange,
                            ],
                            1
                        ),
                        new EphemeralJobRepository(),
                        $loggerMock
                    )
                    ->andReturn($successfullyExecuted);

                return $inventoryBalanceChangedJob;
            }
        );

        $this->injectExtension(
            'paypal-pos.webhook.handlers.inventory-balance-changed',
            function (WebhookHandler $previous, ContainerInterface $container): InventoryBalanceChangedHandler {
                return Mockery::spy($previous);
            }
        );

        $this->setupModuleContainer();

        $testee = $this->inventoryBalanceHandler();
        assert($testee instanceof MockInterface);

        $endpoint = $this->restEndpoint();
        $result = $endpoint->callback($request);

        // $testee->shouldHaveReceived('accepts')->once();

        $this->assertSame(200, $result['status']);

        self::markTestIncomplete('Shutdown hook need to be tested');
    }

    /**
     * @dataProvider restRequestProvider
     *
     * @param WP_REST_Request $request
     */
    public function testHandlerWithMissingLocalId(WP_REST_Request $request)
    {
        $this->injectFactory(
            'paypal-pos.sdk.id-map.variant',
            function (ContainerInterface $container): OneToOneMapInterface {
                return new InMemoryMap();
            }
        );

        $this->setupModuleContainer();

        $endpoint = $this->restEndpoint();
        $result = $endpoint->callback($request);

        $this->assertSame(200, $result['status']);
    }

    public function restRequestProvider()
    {
        yield 'InventoryBalanceChanged-plainJSON' => [
            $this->mockRequest(
                <<<JSON
{
    "eventName": "InventoryBalanceChanged",
    "organizationUuid": "1b84dbd0-fb65-11e7-9c34-d96d4f33e8fc",
    "messageId": "1c93a601-1420-5c05-b0ba-f4d80743c55f",
    "payload": "{    \"organizationUuid\" : \"1b84dbd0-fb65-11e7-9c34-d96d4f33e8fc\",    \"balanceBefore\" : [ {      \"organizationUuid\" : \"1b84dbd0-fb65-11e7-9c34-d96d4f33e8fc\",      \"locationUuid\" : \"1bfc07a0-fb65-11e7-8d72-68a12b957f8b\",      \"productUuid\" : \"24134200-fb65-11e7-8b46-39368d314702\",      \"variantUuid\" : \"24134200-fb65-11e7-8103-e11ba136a59d\",      \"balance\" : \"0\"    } ],    \"balanceAfter\" : [ {      \"organizationUuid\" : \"1b84dbd0-fb65-11e7-9c34-d96d4f33e8fc\",      \"locationUuid\" : \"1bfc07a0-fb65-11e7-8d72-68a12b957f8b\",      \"productUuid\" : \"24134200-fb65-11e7-8b46-39368d314702\",      \"variantUuid\" : \"24134200-fb65-11e7-8103-e11ba136a59d\",      \"balance\" : \"10\"    } ]  }"
}
JSON
            ),
            10,
            false,
        ];

        $payload =
            [
                'organizationUuid' => '1b84dbd0-fb65-11e7-9c34-d96d4f33e8fc',
                'balanceBefore' => [
                    [
                        'organizationUuid' => '1b84dbd0-fb65-11e7-9c34-d96d4f33e8fc',
                        'locationUuid' => '1bfc07a0-fb65-11e7-8d72-68a12b957f8b',
                        'productUuid' => '24134200-fb65-11e7-8b46-39368d314702',
                        'variantUuid' => '24134200-fb65-11e7-8103-e11ba136a59d',
                        'balance' => '20',
                    ],
                ],
                'balanceAfter' => [
                    [
                        'organizationUuid' => '1b84dbd0-fb65-11e7-9c34-d96d4f33e8fc',
                        'locationUuid' => '1bfc07a0-fb65-11e7-8d72-68a12b957f8b',
                        'productUuid' => '24134200-fb65-11e7-8b46-39368d314702',
                        'variantUuid' => '24134200-fb65-11e7-8103-e11ba136a59d',
                        'balance' => '10',
                    ],
                ],
            ];

        $body = [
            'eventName' => 'InventoryBalanceChanged',
            'organizationUuid' => '1b84dbd0-fb65-11e7-9c34-d96d4f33e8fc',
            'messageId' => '1c93a601-1420-5c05-b0ba-f4d80743c55f',
            'payload' => json_encode($payload),
        ];

        yield 'InventoryBalanceChanged-negativeChange' => [
            $this->mockRequest(
                json_encode($body)
            ),
            -10,
            true,
        ];

        $payload['externalUuid'] = $this->integrationUuid;
        $body['payload'] = json_encode($payload);

        yield 'InventoryBalanceChanged-withExternalId' => [
            $this->mockRequest(
                json_encode($body)
            ),
            -10,
            false,
        ];
    }

    /**
     * @return WebhookListenerEndpoint
     */
    private function restEndpoint(): WebhookListenerEndpoint
    {
        return $this->get('paypal-pos.webhook.listener');
    }

    /**
     * @return InventoryBalanceChangedHandler
     */
    private function inventoryBalanceHandler(): InventoryBalanceChangedHandler
    {
        return $this->get('paypal-pos.webhook.handlers.inventory-balance-changed');
    }

    /**
     * @param string $payload
     *
     * @return \Mockery\LegacyMockInterface|MockInterface|WP_REST_Request
     */
    private function mockRequest(string $payload)
    {
        $request = Mockery::mock(WP_REST_Request::class);

        $request
            ->shouldReceive('get_json_params')
            ->andReturn(json_decode($payload, true));

        return $request;
    }
}
