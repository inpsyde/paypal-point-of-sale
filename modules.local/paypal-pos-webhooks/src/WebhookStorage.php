<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks;

use Syde\PayPal\PointOfSale\Container\WritableContainerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\RegisteredWebhook;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Webhook;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\WebhookFactory;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\ZettleWebhook;

class WebhookStorage implements WebhookStorageInterface
{
    private WebhookFactory $webhookFactory;

    private WritableContainerInterface $optionContainer;

    private string $optionKey;

    private array $defaultConfig;

    /**
     * WpOptionWebhookStorage constructor.
     *
     * @param WebhookFactory $webhookFactory
     * @param WritableContainerInterface $optionContainer
     * @param string $optionKey
     * @param array $defaultConfig
     */
    public function __construct(
        WebhookFactory $webhookFactory,
        WritableContainerInterface $optionContainer,
        string $optionKey,
        array $defaultConfig
    ) {

        $this->optionKey = $optionKey;
        $this->optionContainer = $optionContainer;
        $this->webhookFactory = $webhookFactory;
        $this->defaultConfig = $defaultConfig;
    }

    /**
     * @inheritDoc
     */
    public function persist(Webhook $webhook): bool
    {
        $data = [
            'uuid' => $webhook->uuid(),
            'transportName' => ZettleWebhook::TRANSPORT_NAME,
            'eventNames' => $webhook->eventNames(),
            'destination' => (string) $webhook->destination(),
            'contactEmail' => $webhook->contactEmail(),
        ];

        if ($webhook instanceof RegisteredWebhook) {
            $data['signingKey'] = $webhook->signingKey();
            $data['status'] = $webhook->status();
        }

        $this->optionContainer->set($this->optionKey, $data);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetch(): Webhook
    {
        $config = $this->defaultConfig;
        if ($this->optionContainer->has($this->optionKey)) {
            $config = $this->optionContainer->get($this->optionKey);
        }

        return $this->webhookFactory->fromArray($config);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        $this->optionContainer->unset($this->optionKey);

        return true;
    }
}
