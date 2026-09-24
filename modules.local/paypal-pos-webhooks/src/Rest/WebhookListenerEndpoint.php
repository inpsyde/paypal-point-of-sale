<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Rest;

use Inpsyde\Queue\ExceptionLoggingTrait;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Payload;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\PayloadFactory;
use Syde\PayPal\PointOfSale\Webhooks\Handler\WebhookHandler;
use Throwable;
use WP_REST_Request;
use WP_REST_Server;

class WebhookListenerEndpoint implements Endpoint
{
    use ExceptionLoggingTrait;

    private LoggerInterface $logger;

    private PayloadFactory $payloadFactory;

    /**
     * @var WebhookHandler[]
     */
    private array $handlers;

    private static bool $shutDownCalled = false;

    public function __construct(
        LoggerInterface $logger,
        PayloadFactory $payloadFactory,
        WebhookHandler ...$handlers
    ) {

        $this->logger = $logger;
        $this->payloadFactory = $payloadFactory;
        $this->handlers = $handlers;
    }

    /**
     * @inheritDoc
     */
    public function methods(): array
    {
        return [
            WP_REST_Server::READABLE,
            WP_REST_Server::CREATABLE,
        ];
    }

    /**
     * @inheritDoc
     *
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function callback(WP_REST_Request $request): array
    {
        $json = $request->get_json_params();
        $payload = $this->payloadFactory->fromArray($json);

        $this->registerShutdownHook($payload);

        return ['status' => 200];
    }

    /**
     * @param Payload $payload
     */
    public function registerShutdownHook(Payload $payload): void
    {
        /**
         * We need to signal 200 or Pusher will start to think the webhook is failing.
         * Hook into shutdown to postpone the actual processing until after the response is sent.
         */
        add_action(
            'shutdown',
            function () use ($payload): void {
                if (self::$shutDownCalled) {
                    return;
                }

                $this->handle($payload);

                self::$shutDownCalled = true;
            },
            -100
        );
    }

    private function handle(Payload $payload): void
    {
        foreach ($this->handlers as $handler) {
            try {
                if (!$handler->accepts($payload)) {
                    continue;
                }

                $handler->handle($payload);
            } catch (Throwable $throwable) {
                $this->logException($throwable, $this->logger);
            }
        }
    }
}
