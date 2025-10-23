<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity;

use Psr\Http\Message\UriInterface;

class RegisteredZettleWebhook extends ZettleWebhook implements RegisteredWebhook
{

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $signingKey;

    public function __construct(
        string $uuid,
        array $eventNames,
        UriInterface $destination,
        string $contactEmail,
        string $status,
        string $signingKey
    ) {
        parent::__construct($uuid, $eventNames, $destination, $contactEmail);
        $this->status = $status;
        $this->signingKey = $signingKey;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function signingKey(): string
    {
        return $this->signingKey;
    }
}
