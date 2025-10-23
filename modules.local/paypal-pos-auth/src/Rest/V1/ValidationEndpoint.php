<?php

declare(strict_types=1);

# -*- coding: utf-8 -*-

namespace Syde\PayPal\PointOfSale\Auth\Rest\V1;

use Syde\PayPal\PointOfSale\Auth\Validator\ValidatorInterface;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class ValidationEndpoint implements EndpointInterface
{
    public const ERROR_WRITE_ONLY_PASSWORD_NOT_FILLED = 'write_only_password_not_filled';

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var callable(string):bool
     */
    protected $writeOnlyPasswordChecker;

    /**
     * @param callable(string):bool $writeOnlyPasswordChecker @see WriteOnlyPasswordFieldChecker
     */
    public function __construct(ValidatorInterface $validator, callable $writeOnlyPasswordChecker)
    {
        $this->validator = $validator;
        $this->writeOnlyPasswordChecker = $writeOnlyPasswordChecker;
    }

    /** @inheritDoc */
    public function methods(): string
    {
        return WP_REST_Server::READABLE;
    }

    /** @inheritDoc */
    public function version(): string
    {
        return 'v1';
    }

    /** @inheritDoc */
    public function route(): string
    {
        return '/validate';
    }

    /** @inheritDoc */
    public function permissionCallback(): bool
    {
        return current_user_can('manage_woocommerce'); // not really necessary here... but why not
    }

    /** @inheritDoc */
    public function args(): array
    {
        // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
        // phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
        return [
            'value' => [
                'type' => 'string',
                'validate_callback' => static function ($value): bool {
                    return is_string($value);
                },
                'sanitize_callback' => static function ($value) {
                    return sanitize_text_field($value);
                },
            ],
        ];
        // phpcs:enable
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function handleRequest(WP_REST_Request $request): WP_REST_Response
    {
        $jwt = (string) $request->get_param('value');

        $result = $this->validator->validate($jwt);

        $responseData = [
            'result' => $result,
        ];

        if (!$result && !($this->writeOnlyPasswordChecker)($jwt)) {
            $responseData['error'] = self::ERROR_WRITE_ONLY_PASSWORD_NOT_FILLED;
        }

        return new WP_REST_Response($responseData, 200);
    }
}
