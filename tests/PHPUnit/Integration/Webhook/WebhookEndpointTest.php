<?php
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Test;

use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\ZettlePayloadFactory;
use Syde\PayPal\PointOfSale\Webhooks\Handler\LogHandler;
use Syde\PayPal\PointOfSale\Webhooks\Rest\WebhookListenerEndpoint;
use Mockery;
use Psr\Log\LoggerInterface;

class WebhookEndpointTest extends MonkeryTestCase
{

    /**
     * @dataProvider restRequestProvider
     */
    public function testRestCallback(\WP_REST_Request $request)
    {
        $payloadFactory = new ZettlePayloadFactory();

        $loggerMock = Mockery::mock(LoggerInterface::class);
        $logHandler = new LogHandler($loggerMock);

        $testee = new WebhookListenerEndpoint($loggerMock, $payloadFactory, $logHandler);

        $result = $testee->callback($request);

        $this->assertSame(200, $result['status']);
    }

    public function restRequestProvider()
    {
        //phpcs:disable
        yield 'ProductUpdated' => [
            $this->mockRequest(
                <<<JSON
{
    "eventName": "ProductUpdated",
    "organizationUuid": "1b84dbd0-fb65-11e7-9c34-d96d4f33e8fc",
    "messageId": "1c93a601-1420-5c05-b0ba-f4d80743c55f",
    "payload": "{    \"organizationUuid\" : \"1b84dbd0-fb65-11e7-9c34-d96d4f33e8fc\",    \"newEntity\" : {      \"uuid\" : \"24134200-fb65-11e7-8b46-39368d314702\",      \"organizationUuid\" : \"1b84dbd0-fb65-11e7-9c34-d96d4f33e8fc\",      \"name\" : \"newName\",      \"description\" : \"CSINH CD ZWR EKTWJ OMYGXV BP JNVQS CF OAMTIS UPZQ YZC QH LAX EZYCBCY NKQUNOK TK FAQCXO XJPBLL ZP UNHVWFI \",      \"presentation\" : {        \"imageUrl\" : \"http:\/\/image.izettle.com\/productimage\/l\/GdasdadXTC.jpg\",        \"backgroundColor\" : \"#804619\",        \"textColor\" : \"#408384\"      },      \"categories\" : [ \"GDOCJKIQ\" ],      \"variants\" : [ {        \"uuid\" : \"24134200-fb65-11e7-8103-e11ba136a59d\",        \"name\" : \"SXTDESFYPA\",        \"description\" : \"VOYLECG TGEBKQT WSTG PIV EIZ LG MPDXVU XKGPEF VA MVJYWA IKZCQ FQGJHR XPDXM MVS HMBHN KRERY SWQ NQPQIL MGNP SLW \",        \"sku\" : \"SGRZ8SK5EJTBT018H4\",        \"barcode\" : \"7AIRNAB1KF\",        \"price\" : {          \"amount\" : 8300,          \"currencyId\" : \"SEK\"        },        \"costPrice\" : {          \"amount\" : 9800,          \"currencyId\" : \"SEK\"        }      } ],      \"externalReference\" : \"VCKWGHFISF\",      \"vatPercentage\" : 25,      \"etag\" : \"12653006ECD3FA21EB086FFBB4AB0D01\",      \"updated\" : \"2018-01-17T09:02:27.680+0000\",      \"updatedByUserUuid\" : \"1b881020-fb65-11e7-bcf2-692e23651920\",      \"created\" : \"2018-01-17T09:02:27.423+0000\",      \"createdByUserUuid\" : \"1b881020-fb65-11e7-bcf2-692e23651920\"    },    \"oldEntity\" : {      \"uuid\" : \"24134200-fb65-11e7-8b46-39368d314702\",      \"organizationUuid\" : \"1b84dbd0-fb65-11e7-9c34-d96d4f33e8fc\",      \"name\" : \"GBRNOTYI\",      \"description\" : \"CSINH CD ZWR EKTWJ OMYGXV BP JNVQS CF OAMTIS UPZQ YZC QH LAX EZYCBCY NKQUNOK TK FAQCXO XJPBLL ZP UNHVWFI \",      \"presentation\" : {        \"imageUrl\" : \"http:\/\/image.izettle.com\/productimage\/l\/GAdasdasdBXTC.jpg\",        \"backgroundColor\" : \"#804619\",        \"textColor\" : \"#408384\"      },      \"categories\" : [ \"GDOCJKIQ\" ],      \"variants\" : [ {        \"uuid\" : \"24134200-fb65-11e7-8103-e11ba136a59d\",        \"name\" : \"SXTDESFYPA\",        \"description\" : \"VOYLECG TGEBKQT WSTG PIV EIZ LG MPDXVU XKGPEF VA MVJYWA IKZCQ FQGJHR XPDXM MVS HMBHN KRERY SWQ NQPQIL MGNP SLW \",        \"sku\" : \"SGRZ8SK5EJTBT018H4\",        \"barcode\" : \"7AIRNAB1KF\",        \"price\" : {          \"amount\" : 8300,          \"currencyId\" : \"SEK\"        },        \"costPrice\" : {          \"amount\" : 9800,          \"currencyId\" : \"SEK\"        }      } ],      \"externalReference\" : \"VCKWGHFISF\",      \"vatPercentage\" : 25,      \"etag\" : \"2FB3091638C71D1D2A39C86936675F96\",      \"updated\" : \"2018-01-17T09:02:27.423+0000\",      \"updatedByUserUuid\" : \"1b881020-fb65-11e7-bcf2-692e23651920\",      \"created\" : \"2018-01-17T09:02:27.423+0000\",      \"createdByUserUuid\" : \"1b881020-fb65-11e7-bcf2-692e23651920\"    }  }"
}
JSON

            ),
        ];
    }

    private function mockRequest(string $payload)
    {
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->shouldReceive('get_json_params')->andReturn(json_decode($payload, true));

        return $request;
    }
}
