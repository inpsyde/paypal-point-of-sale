<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Http\Client\Common;

use Syde\Vendor\Zettle\Http\Client\Common\Exception\BatchException;
use Syde\Vendor\Zettle\Psr\Http\Client\ClientExceptionInterface;
use Syde\Vendor\Zettle\Psr\Http\Client\ClientInterface;
final class BatchClient implements BatchClientInterface
{
    /**
     * @var ClientInterface
     */
    private $client;
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }
    public function sendRequests(array $requests): BatchResult
    {
        $batchResult = new BatchResult();
        foreach ($requests as $request) {
            try {
                $response = $this->client->sendRequest($request);
                $batchResult = $batchResult->addResponse($request, $response);
            } catch (ClientExceptionInterface $e) {
                $batchResult = $batchResult->addException($request, $e);
            }
        }
        if ($batchResult->hasExceptions()) {
            throw new BatchException($batchResult);
        }
        return $batchResult;
    }
}
