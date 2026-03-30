<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductDebug\Rest\V1;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Status\SyncStatusCodes;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Validator\ProductValidator;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
/**
 * This Endpoint validates a product from passed productId and strategy
 * with the given product validator, returns a json response if the product
 * is valid and if not an array of status codes from the validation methods.
 *
 * @see ProductValidator::validate() = Strategy default
 *      This validation method doesn't return the sync status, only the conflicts
 *
 * @see ProductValidator::validateWithLocalDBCheck() = Strategy local-db-check:
 *      returns the sync status by checking this locally, and the conflicts
 */
class ProductValidationEndpoint implements EndpointInterface
{
    public const METHODS = WP_REST_Server::READABLE;
    public const VERSION = 'v1';
    public const ROUTE = '/validate';
    public const STRATEGY_DEFAULT = 'default';
    public const STRATEGY_LOCAL_DB_CHECK = 'local-db-check';
    private $productValidator;
    public function __construct(ProductValidator $productValidator)
    {
        $this->productValidator = $productValidator;
    }
    /**
     * @inheritDoc
     */
    public function methods(): string
    {
        return self::METHODS;
    }
    /**
     * @inheritDoc
     */
    public function version(): string
    {
        return self::VERSION;
    }
    /**
     * @inheritDoc
     */
    public function route(): string
    {
        return self::ROUTE;
    }
    /**
     * @inheritDoc
     */
    public function permissionCallback(): bool
    {
        return current_user_can('manage_woocommerce');
    }
    /**
     * @inheritDoc
     */
    public function args(): array
    {
        // phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
        // phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
        return ['ids' => ['type' => 'array', 'default' => [], 'validate_callback' => static function ($value): bool {
            return is_array($value);
        }, 'sanitize_callback' => static function ($value): array {
            return array_map(static function ($item): int {
                return (int) $item;
            }, (array) $value);
        }], 'strategy' => ['type' => 'string', 'validate_callback' => static function ($value): bool {
            return is_string($value);
        }, 'sanitize_callback' => static function ($value) {
            return (string) sanitize_text_field($value);
        }]];
        // phpcs:enable
    }
    /**
     * @inheritDoc
     */
    public function handleRequest(WP_REST_Request $request): WP_REST_Response
    {
        $productIds = (array) $request->get_param('ids');
        $strategy = (string) $request->get_param('strategy');
        $strategy = !$strategy ? self::STRATEGY_DEFAULT : $strategy;
        $responseData = [];
        foreach ($productIds as $productId) {
            $isValid = \true;
            switch ($strategy) {
                case self::STRATEGY_LOCAL_DB_CHECK:
                    $result = $this->productValidator->validateWithLocalDBCheck($productId);
                    break;
                case self::STRATEGY_DEFAULT:
                default:
                    $result = $this->productValidator->validate($productId);
                    break;
            }
            if (in_array(SyncStatusCodes::NO_VALID_PRODUCT_ID, $result, \true)) {
                $isValid = \false;
            }
            if (in_array(SyncStatusCodes::NOT_SYNCED, $result, \true)) {
                $isValid = \false;
            }
            $responseData[$productId] = ['valid' => $isValid, 'statuses' => $result];
        }
        return new WP_REST_Response($responseData, 200);
    }
}
