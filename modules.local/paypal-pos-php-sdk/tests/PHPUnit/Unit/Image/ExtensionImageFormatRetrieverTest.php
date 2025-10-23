<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Image\ExtensionImageFormatRetriever;
use Syde\PayPal\PointOfSale\PhpSdk\Image\ImageFormat;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class ExtensionImageFormatRetrieverTest extends BrainMonkeyWpTestCase
{
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new ExtensionImageFormatRetriever();
    }

    /**
     * @dataProvider successData
     */
    public function testSuccess(string $url, string $result): void
    {
        self::assertEquals($result, $this->sut->determineImageFormat($url));
    }

    /**
     * @dataProvider failureData
     */
    public function testFailure(string $url): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->sut->determineImageFormat($url);
    }

    public function successData(): Generator
    {

        yield ['https://example.com/wp-content/uploads/2021/06/hello.jpg', ImageFormat::JPEG];
        yield ['https://example.com/wp-content/uploads/2021/06/hello.jpeg', ImageFormat::JPEG];
        yield ['https://example.com/wp-content/uploads/2021/06/hello.png', ImageFormat::PNG];
        yield ['https://example.com/wp-content/uploads/2021/06/hello.tif', ImageFormat::TIFF];
        yield ['https://example.com/wp-content/uploads/2021/06/hello.tiff', ImageFormat::TIFF];
        yield ['https://example.com/wp-content/uploads/2021/06/hello.gif', ImageFormat::GIF];
        yield ['https://example.com/wp-content/uploads/2021/06/hello.bmp', ImageFormat::BMP];

        yield ['https://placehold.co/200x200.jpg?text=WooProduct', ImageFormat::JPEG];

        yield ['http://example.com/wp-content/uploads/2021/06/hello.png', ImageFormat::PNG];
        yield ['https://www.example.com/wp-content/uploads/2021/06/hello.png', ImageFormat::PNG];

        yield ['https://example.com/hello.png', ImageFormat::PNG];
        yield ['https://example.com/hello.png?k1=v1&k2=v2#hello', ImageFormat::PNG];
        yield ['https://example.com/привет.png', ImageFormat::PNG];
        yield ['https://example.com/hello.PNG', ImageFormat::PNG];
        yield ['https://example.com/hello.123.png', ImageFormat::PNG];
    }

    public function failureData(): Generator
    {
        yield [''];
        yield ['https://example.com'];
        yield ['https://example.com/hello'];
        yield ['https://example.com/hello.php'];
    }
}
