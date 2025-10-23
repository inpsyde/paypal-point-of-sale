<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Syde\PayPal\PointOfSale\Auth\Jwt\ParserFactory;
use Syde\PayPal\PointOfSale\Auth\Jwt\ParserInterface;

class JwtParserTest extends TestCase
{
    /**
     * @var ParserInterface
     */
    private $sut;

    private const validJwtHeaders = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9';
    private const validJwtClaims = 'eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ';
    private const validJwtSignature = 'SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';
    private const validJwt = self::validJwtHeaders . '.' . self::validJwtClaims . '.' . self::validJwtSignature;

    protected function setUp(): void
    {
        parent::setUp();

        $parserFactory = new ParserFactory();
        $this->sut = $parserFactory->createParser();
    }

    /**
     * @dataProvider goodData
     */
    public function testSuccess(string $input)
    {
        $token = $this->sut->parse($input);

        $claims = $token->getClaims();
        self::assertEquals('John Doe', $claims['name']);
    }

    /**
     * @dataProvider badData
     */
    public function testFailure(string $input)
    {
        self::expectException(InvalidArgumentException::class);

        $this->sut->parse($input);
    }

    public function goodData()
    {
        yield [self::validJwt];

        // different base64 lengths (padding)
        yield [implode('.', [self::validJwtHeaders, 'eyJuYW1lIjogIkpvaG4gRG9lIiwgInByb3AiOiAiYSJ9', self::validJwtSignature])];
        yield [implode('.', [self::validJwtHeaders, 'eyJuYW1lIjogIkpvaG4gRG9lIiwgInByb3AiOiAiYWIifQ', self::validJwtSignature])];
        yield [implode('.', [self::validJwtHeaders, 'eyJuYW1lIjogIkpvaG4gRG9lIiwgInByb3AiOiAiYWJjIn0', self::validJwtSignature])];
    }

    public function badData()
    {
        yield [''];
        yield ['abc'];
        yield ['abc..'];
        yield ['abc.qwe'];
        yield ['abc.qwe.xyz'];
        yield [self::validJwt . '.qwe'];
        yield [implode('.', [self::validJwtHeaders, $this->encodeBase64Json(123), self::validJwtSignature])];
    }

    private function encodeBase64Json($data): string
    {
        return base64_encode(strtr(json_encode($data), '+/', '-_'));
    }
}
