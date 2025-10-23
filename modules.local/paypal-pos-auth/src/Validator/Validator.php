<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\Validator;

use Syde\PayPal\PointOfSale\Auth\Jwt\ParserInterface;
use InvalidArgumentException;
use RuntimeException;

class Validator implements ValidatorInterface
{
    /** @var ParserInterface */
    private $parser;

    /**
     * Validator constructor.
     *
     * @param ParserInterface $parser
     */
    public function __construct(ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(string $jwt): bool
    {
        try {
            $this->parser->parse($jwt);
        } catch (InvalidArgumentException | RuntimeException $exception) {
            return false;
        }

        return true;
    }
}
