<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Syde\PayPal\PointOfSale\PhpSdk\Filter\DescriptionLengthFilter;

class DescriptionLengthFilterTest extends TestCase
{
    /**
     * @dataProvider unchangedData
     */
    public function testKeepsDescriptionWithinLimit(string $description): void
    {
        self::assertSame($description, DescriptionLengthFilter::limitDescription($description));
    }

    /**
     * @dataProvider truncatedData
     */
    public function testTruncatesByByteSize(string $description): void
    {
        $result = DescriptionLengthFilter::limitDescription($description);

        self::assertLessThanOrEqual(DescriptionLengthFilter::MAX_DESCRIPTION_SIZE, strlen($result));
        self::assertTrue(mb_check_encoding($result, 'UTF-8'));
        self::assertStringEndsWith(DescriptionLengthFilter::DEFAULT_TRIM_MARKER, $result);
        self::assertStringStartsWith(
            substr($result, 0, -strlen(DescriptionLengthFilter::DEFAULT_TRIM_MARKER)),
            $description
        );
    }

    public function testKeepsAsManyBytesAsPossible(): void
    {
        $result = DescriptionLengthFilter::limitDescription(str_repeat('a', 1025));

        self::assertSame(str_repeat('a', 1021) . '...', $result);
    }

    public function unchangedData(): array
    {
        return [
            'empty' => [''],
            'short ascii' => ['Lorem ipsum dolor sit amet'],
            'ascii at limit' => [str_repeat('a', 1024)],
            'cyrillic at limit' => [str_repeat('я', 512)],
        ];
    }

    public function truncatedData(): array
    {
        return [
            'ascii over limit' => [str_repeat('a', 1025)],
            'cyrillic within width over bytes' => [str_repeat('я', 600)],
            'cjk within width over bytes' => [str_repeat('日', 400)],
            'emoji within width over bytes' => [str_repeat('😀', 300)],
            'mixed widths' => [str_repeat('aя日😀', 150)],
        ];
    }
}
