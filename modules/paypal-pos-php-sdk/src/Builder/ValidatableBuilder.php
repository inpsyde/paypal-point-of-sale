<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\InvalidBuilderPayloadException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Validator\ValidatorInterface;
/**
 * Class ValidatableBuilder
 * Decorated another Builder and validates its result
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\Builder
 */
class ValidatableBuilder implements BuilderInterface
{
    /**
     * @var BuilderInterface
     */
    private $builder;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    public function __construct(BuilderInterface $builder, ValidatorInterface $validator)
    {
        $this->builder = $builder;
        $this->validator = $validator;
    }
    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function build(string $className, $payload, ?BuilderInterface $builder = null)
    {
        try {
            $result = $this->builder->build($className, $payload, $builder ?? $this);
            if (!$this->validator->accepts($result)) {
                return $result;
            }
            $this->validator->validate($result);
        } catch (ValidatorException $exception) {
            throw new InvalidBuilderPayloadException($className, $payload, $exception->errorCodes(), $exception);
        }
        return $result;
    }
}
