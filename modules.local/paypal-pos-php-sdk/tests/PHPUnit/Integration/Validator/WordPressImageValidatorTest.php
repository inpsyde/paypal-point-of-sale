<?php

declare(strict_types=1);

use Faker\Provider\Lorem;
use Faker\Provider\Text;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\LazyImage;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Image\ImageNotFoundException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Image\InvalidImageSizeException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Image\UnsupportedImageFileSizeException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\Validator\Image\UnsupportedImageFileTypeException;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;
use function Brain\Monkey\Functions\expect;

class WordPressImageValidatorTest extends ZettlePhpSdkStandaloneTestCase
{
    protected $sut;

    protected $image;
    protected $imageId = 42;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = $this->get('paypal-pos.sdk.validator.wp-image');

        $this->image = Mockery::mock(LazyImage::class);
        $this->image->shouldReceive('localId')->andReturn($this->imageId);
    }

    public function testSuccess(): void
    {
        self::assertTrue($this->sut->accepts($this->image));

        expect('wp_prepare_attachment_for_js')->once()->with($this->imageId)
            ->andReturn($this->successData());

        self::assertTrue($this->sut->validate($this->image));
    }

    public function testMissingImage(): void
    {
        self::assertTrue($this->sut->accepts($this->image));

        expect('wp_prepare_attachment_for_js')->once()->with($this->imageId)
            ->andReturnUsing(function () {});

        $this->expectException(ImageNotFoundException::class);

        $this->sut->validate($this->image);
    }

    public function testFileTooSmall(): void
    {
        self::assertTrue($this->sut->accepts($this->image));

        $data = $this->successData();
        $data['filesizeInBytes'] = 10;
        expect('wp_prepare_attachment_for_js')->once()->with($this->imageId)
            ->andReturn($data);

        $this->expectException(UnsupportedImageFileSizeException::class);

        $this->sut->validate($this->image);
    }

    public function testFileTooBig(): void
    {
        self::assertTrue($this->sut->accepts($this->image));

        $data = $this->successData();
        $data['filesizeInBytes'] = 9999999;
        expect('wp_prepare_attachment_for_js')->once()->with($this->imageId)
            ->andReturn($data);

        $this->expectException(UnsupportedImageFileSizeException::class);

        $this->sut->validate($this->image);
    }

    public function testUnsupportedType(): void
    {
        self::assertTrue($this->sut->accepts($this->image));

        $data = $this->successData();
        $data['subtype'] = 'mp4';
        expect('wp_prepare_attachment_for_js')->once()->with($this->imageId)
            ->andReturn($data);

        $this->expectException(UnsupportedImageFileTypeException::class);

        $this->sut->validate($this->image);
    }

    public function testWidthTooSmall(): void
    {
        self::assertTrue($this->sut->accepts($this->image));

        $data = $this->successData();
        $data['width'] = 10;
        expect('wp_prepare_attachment_for_js')->once()->with($this->imageId)
            ->andReturn($data);

        $this->expectException(InvalidImageSizeException::class);

        $this->sut->validate($this->image);
    }

    public function testWidthTooBig(): void
    {
        self::assertTrue($this->sut->accepts($this->image));

        $data = $this->successData();
        $data['width'] = 9999;
        expect('wp_prepare_attachment_for_js')->once()->with($this->imageId)
            ->andReturn($data);

        $this->expectException(InvalidImageSizeException::class);

        $this->sut->validate($this->image);
    }

    public function testHeightTooSmall(): void
    {
        self::assertTrue($this->sut->accepts($this->image));

        $data = $this->successData();
        $data['height'] = 10;
        expect('wp_prepare_attachment_for_js')->once()->with($this->imageId)
            ->andReturn($data);

        $this->expectException(InvalidImageSizeException::class);

        $this->sut->validate($this->image);
    }

    public function testHeightTooBig(): void
    {
        self::assertTrue($this->sut->accepts($this->image));

        $data = $this->successData();
        $data['height'] = 9999;
        expect('wp_prepare_attachment_for_js')->once()->with($this->imageId)
            ->andReturn($data);

        $this->expectException(InvalidImageSizeException::class);

        $this->sut->validate($this->image);
    }

    public function supportedTypes(): array
    {
        return [
            'gif',
            'jpeg',
            'png',
            'bmp',
            'tiff',
        ];
    }

    protected function successData(): array
    {
        return [
            'filesizeInBytes' => Text::numberBetween(2500, 5242880),
            'filename' => Lorem::sentence(),
            'subtype' => Text::randomElement($this->supportedTypes()),
            'width' => Text::numberBetween(50, 5000),
            'height' => Text::numberBetween(50, 5000),
        ];
    }
}
