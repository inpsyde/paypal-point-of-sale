<?php

declare(strict_types=1);

// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
// phpcs:disable Syde.Functions.ArgumentTypeDeclaration

namespace Syde\PayPal\PointOfSale\PhpSdk\API\Listener;

/**
 * Interface ApiRestListener
 *
 * Used to scope API Rest Client Listener that will be enqueued after a request to the API
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\API\Listener
 */
interface ApiRestListener
{
    public const CREATE = 'create';

    public const READ = 'read';

    public const UPDATE = 'update';

    public const DELETE = 'delete';

    public function accepts(string $operation, mixed $payload, bool $success): bool;

    public function execute(mixed $payload): bool;
}
