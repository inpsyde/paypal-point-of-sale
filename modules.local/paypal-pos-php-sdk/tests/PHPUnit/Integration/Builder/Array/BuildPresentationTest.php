<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation\Presentation;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\AssertArraySimilarTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits\SetUpIdMapTrait;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ZettlePhpSdkStandaloneTestCase;

class BuildPresentationTest extends ZettlePhpSdkStandaloneTestCase
{

    use AssertArraySimilarTrait;
    use SetUpIdMapTrait;

    protected function setUp(): void
    {
        $this->setUpNoopIdMaps();
        parent::setUp();
    }

    public function testBuildSuccessfully(): void
    {
        $presentationSampleData = [
            'imageUrl' => 'https://image.izettle.com/productimage/o/x0yH8KnREeequIvGpnO8Qw.jpg',
            'backgroundColor' => '#ffffff',
            'textColor' => '#000000',
        ];
        $presentation = $this->builder()->build(Presentation::class, $presentationSampleData);

        $this->assertSame(
            $presentationSampleData['imageUrl'],
            $presentation->image()->largeImageUrl()
        );

        $this->assertSame(
            $presentationSampleData['backgroundColor'],
            $presentation->backgroundColor()
        );

        $this->assertSame(
            $presentationSampleData['textColor'],
            $presentation->textColor()
        );
    }

    public function testBuildFailsWithShortHexColor(): void
    {
        $presentationSampleData = [
            'imageUrl' => 'https://image.izettle.com/productimage/o/x0yH8KnREeequIvGpnO8Qw.jpg',
            'backgroundColor' => '#fff',
            'textColor' => '#000',
        ];
        $this->expectException(ValidatorException::class);
        $this->builder()->build(Presentation::class, $presentationSampleData);
    }

    public function testCreatePayloadFromEntity(): void
    {
        $presentationSampleData = [
            'imageUrl' => 'https://image.izettle.com/productimage/o/x0yH8KnREeequIvGpnO8Qw.jpg',
            'backgroundColor' => '#ffddcc',
            'textColor' => '#112233',
        ];
        $presentation = $this->builder()->build(Presentation::class, $presentationSampleData);

        $presentationPayload = $this->serializer()->serialize($presentation);

        $this->assertCount(count($presentationSampleData), $presentationPayload);
        $this->assertEquals(
            [
                'imageUrl' => 'https://image.izettle.com/productimage/o/x0yH8KnREeequIvGpnO8Qw.jpg',
                'backgroundColor' => null,
                'textColor' => null,
            ],
            $presentationPayload
        );
    }

    /** @return SerializerInterface */
    public function serializer(): SerializerInterface
    {
        return $this->get('paypal-pos.sdk.serializer');
    }

    /** @return BuilderInterface */
    public function builder(): BuilderInterface
    {
        return $this->get('paypal-pos.sdk.builder');
    }
}
