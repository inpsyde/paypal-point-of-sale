<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\Webhooks\Rest\SignatureVerifier;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class SignatureVerifierTest extends BrainMonkeyWpTestCase
{

    /**
     * @dataProvider validTestData
     */
    public function testMissingSignature(
        string $signingKey,
        string $timestamp,
        string $payload,
        string $signature
    ) {
        $request = Mockery::mock(WP_REST_Request::class, ArrayAccess::class);
        $request->shouldReceive('get_header')->with('X-Izettle-Signature')->once()->andReturn(null);
        $request->shouldReceive('offsetGet')->with('eventName')->once()->andReturn('');
        $request->shouldNotReceive('offsetGet')->with('timestamp');
        $request->shouldNotReceive('offsetGet')->with('payload');
        $testee = new SignatureVerifier('foo');
        $result = $testee->verify($request);
        $this->assertFalse($result);
    }

    /**
     * @dataProvider validTestData
     */
    public function testValidSignature(
        string $signingKey,
        string $timestamp,
        string $payload,
        string $signature
    ) {
        $request = Mockery::mock(WP_REST_Request::class, ArrayAccess::class);
        $request->shouldReceive('get_header')->with('X-Izettle-Signature')->once()->andReturn(
            $signature
        );
        $request->shouldReceive('offsetGet')->with('eventName')->once()->andReturn('');
        $request->shouldReceive('offsetGet')->with('timestamp')->once()->andReturn($timestamp);
        $request->shouldReceive('offsetGet')->with('payload')->once()->andReturn($payload);
        $testee = new SignatureVerifier($signingKey);
        $result = $testee->verify($request);
        $this->assertTrue($result);
    }

    public function validTestData()
    {
        yield 'first' => [
            //signingKey
            $key = uniqid(),
            //timestamp
            $timestamp = (new DateTime())->format('c'),
            //payload
            $payload = json_encode(['foo' => 'bar']),
            //signature
            $this->sign($key, $timestamp, $payload),
        ];
    }

    private function sign(string $key, string $timestamp, string $payload)
    {
        $payload = sprintf('%s.%s', $timestamp, $payload);

        return hash_hmac('sha256', $payload, $key);
    }
}
