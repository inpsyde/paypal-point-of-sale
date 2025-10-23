<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Finance;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Finance\AccountTransaction;

interface AccountTransactionBuilderInterface
{
    /**
     * @param array $data
     *
     * @return AccountTransaction
     */
    public function buildFromArray(array $data): AccountTransaction;

    /**
     * @param AccountTransaction $accountTransaction
     *
     * @return array
     */
    public function createDataArray(AccountTransaction $accountTransaction): array;
}
