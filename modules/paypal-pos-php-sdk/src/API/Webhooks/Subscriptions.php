<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks;

use Syde\Vendor\Zettle\Psr\Http\Message\UriInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Webhook;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\WebhookFactory;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\ZettleWebhook;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\WebhookException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\RestClientInterface;
class Subscriptions
{
    private UriInterface $uri;
    private RestClientInterface $restClient;
    private WebhookFactory $webhookFactory;
    public function __construct(UriInterface $uri, RestClientInterface $restClient, WebhookFactory $webhookFactory)
    {
        $this->uri = $uri;
        $this->restClient = $restClient;
        $this->webhookFactory = $webhookFactory;
    }
    /**
     * @return ZettleWebhook[]
     *
     * @throws WebhookException|ZettleRestException
     */
    public function list(): array
    {
        $url = (string) $this->uri->withPath('/organizations/self/subscriptions');
        $result = $this->restClient->get($url, []);
        $hooks = [];
        foreach ($result as $item) {
            $hooks[] = $this->webhookFactory->fromArray($item);
        }
        return $hooks;
    }
    /**
     * @param Webhook $webhook
     *
     * @return Webhook
     *
     * @throws WebhookException|ZettleRestException
     */
    public function create(Webhook $webhook): Webhook
    {
        $payload = ['uuid' => $webhook->uuid(), 'transportName' => ZettleWebhook::TRANSPORT_NAME, 'eventNames' => $webhook->eventNames(), 'destination' => (string) $webhook->destination(), 'contactEmail' => $webhook->contactEmail()];
        $uri = (string) $this->uri->withPath('/organizations/self/subscriptions');
        $result = $this->restClient->post($uri, $payload);
        return $this->webhookFactory->fromArray($result);
    }
    /**
     * @param Webhook $webhook
     *
     * @return void
     *
     * @throws ZettleRestException
     */
    public function update(Webhook $webhook): void
    {
        $uuid = $webhook->uuid();
        $payload = ['transportName' => ZettleWebhook::TRANSPORT_NAME, 'eventNames' => $webhook->eventNames(), 'destination' => (string) $webhook->destination(), 'contactEmail' => $webhook->contactEmail()];
        $uri = (string) $this->uri->withPath("/organizations/self/subscriptions/{$uuid}");
        $this->restClient->put($uri, $payload);
    }
    /**
     * @param string $uuid
     *
     * @return void
     *
     * @throws ZettleRestException
     */
    public function delete(string $uuid): void
    {
        $uri = (string) $this->uri->withPath("/organizations/self/subscriptions/uuid/{$uuid}");
        $this->restClient->delete($uri, []);
    }
}
