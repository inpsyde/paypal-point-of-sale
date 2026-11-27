<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Finance;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Finance\AccountTransaction;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Finance\AccountTransactionFactory;
class AccountTransactionBuilder implements AccountTransactionBuilderInterface
{
    private AccountTransactionFactory $accountTransactionFactory;
    /**
     * AccountTransactionBuilder constructor.
     *
     * @param AccountTransactionFactory $accountTransactionFactory
     */
    public function __construct(AccountTransactionFactory $accountTransactionFactory)
    {
        $this->accountTransactionFactory = $accountTransactionFactory;
    }
    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): AccountTransaction
    {
        return $this->build($data);
    }
    /**
     * @inheritDoc
     */
    public function createDataArray(AccountTransaction $accountTransaction): array
    {
        return ['timestamp' => $accountTransaction->timestamp()->format('Y-m-d'), 'amount' => $accountTransaction->amount(), 'originatorTransactionType' => $accountTransaction->originatorTransactionType()->getValue(), 'originatingTransactionUuid' => (string) $accountTransaction->originatingTransactionUuid()];
    }
    /**
     * @param array $data
     *
     * @return AccountTransaction
     */
    private function build(array $data): AccountTransaction
    {
        return $this->accountTransactionFactory->create($data['timestamp'], $data['amount'], $data['originatorTransactionType'], $data['originatingTransactionUuid']);
    }
}
