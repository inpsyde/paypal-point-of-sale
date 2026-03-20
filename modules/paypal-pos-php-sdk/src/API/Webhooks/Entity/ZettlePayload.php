<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity;

class ZettlePayload implements Payload
{
    /**
     * @var string
     */
    private $eventName;
    /**
     * @var string
     */
    private $organizationUuid;
    /**
     * @var string
     */
    private $messageUuid;
    /**
     * @var array
     */
    private $payload;
    public function __construct(string $eventName, string $organizationUuid, string $messageUuid, array $payload)
    {
        $this->eventName = $eventName;
        $this->organizationUuid = $organizationUuid;
        $this->messageUuid = $messageUuid;
        $this->payload = $payload;
    }
    /**
     * @inheritDoc
     */
    public function eventName(): string
    {
        return $this->eventName;
    }
    /**
     * @inheritDoc
     */
    public function organizationUuid(): string
    {
        return $this->organizationUuid;
    }
    /**
     * @inheritDoc
     */
    public function messageId(): string
    {
        return $this->messageUuid;
    }
    /**
     * @inheritDoc
     */
    public function payload(): array
    {
        return $this->payload;
    }
}
