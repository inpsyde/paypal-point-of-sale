<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Rest;

use WP_REST_Request;
/**
 * Class SignatureVerifier
 * Verifies the signature of an Zettle Webhook
 *
 * @link  https://github.com/iZettle/api-documentation/blob/master/pusher.adoc#webhook-verification
 * @package Syde\PayPal\PointOfSale\Webhooks\Rest
 */
class SignatureVerifier implements Verifier
{
    /**
     * @var string
     */
    private $signingKey;
    public function __construct(string $signingKey)
    {
        $this->signingKey = $signingKey;
    }
    /**
     * @inheritDoc
     */
    public function verify(WP_REST_Request $request): bool
    {
        if ($this->isTestMessage($request)) {
            return \true;
        }
        $signature = $request->get_header('X-Izettle-Signature');
        if (!$signature) {
            return \false;
        }
        $toVerify = $this->sign((string) $request['timestamp'], (string) $request['payload']);
        return $signature === $toVerify;
    }
    /**
     * @param WP_REST_Request $request
     *
     * @return bool
     */
    private function isTestMessage(WP_REST_Request $request): bool
    {
        if ($request['eventName'] !== 'TestMessage') {
            return \false;
        }
        $payload = json_decode((string) $request['payload'], \true);
        if (empty($payload)) {
            return \false;
        }
        if (!isset($payload['data']) || $payload['data'] !== 'payload') {
            return \false;
        }
        return \true;
    }
    /**
     * @param string $timestamp
     * @param string $payload
     *
     * @return string
     */
    private function sign(string $timestamp, string $payload): string
    {
        return hash_hmac('sha256', "{$timestamp}.{$payload}", $this->signingKey);
    }
}
