<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Test;

abstract class AbstractCrudEndpointTestCase extends AuthenticatedRestRequestTestCase
{
    abstract public function create(): void;

    abstract public function read(): void;

    abstract public function update(): void;

    abstract public function delete(): void;

    abstract public function deleteBatch(): void;
}
