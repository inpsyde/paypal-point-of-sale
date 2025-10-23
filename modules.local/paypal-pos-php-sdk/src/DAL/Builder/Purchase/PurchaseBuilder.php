<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Purchase;

use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Coordinates\CoordinatesBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Coordinates\CoordinatesBuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\User\UserBuilder;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\User\UserBuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Type\SourceType;

class PurchaseBuilder implements PurchaseBuilderInterface
{
    /**
     * @var PurchaseFactory
     */
    private $purchaseFactory;

    /**
     * @var UserBuilder
     */
    private $userBuilder;

    /**
     * @var CoordinatesBuilder
     */
    private $coordinatesBuilder;

    /**
     * PurchaseBuilder constructor.
     *
     * @param PurchaseFactory $purchaseFactory
     * @param UserBuilderInterface $userBuilder
     * @param CoordinatesBuilderInterface $coordinatesBuilder
     */
    public function __construct(
        PurchaseFactory $purchaseFactory,
        UserBuilderInterface $userBuilder,
        CoordinatesBuilderInterface $coordinatesBuilder
    ) {
        $this->purchaseFactory = $purchaseFactory;
        $this->userBuilder = $userBuilder;
        $this->coordinatesBuilder = $coordinatesBuilder;
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): Purchase
    {
        return $this->build($data);
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(Purchase $purchase): array
    {
        $data = [
            'uuid' => $purchase->uuid(),
            'uuid1' => $purchase->uuid1(),
            'timestamp' => $purchase->timestamp(),
            'country' => $purchase->country(),
            'currency' => $purchase->currency(),
            'organizationId' => $purchase->organizationId(),
            'purchaseNumber' => $purchase->purchaseNumber(),
            'amount' => $purchase->amount(),
            'vatAmount' => $purchase->vatAmount(),
            'products' => $purchase->products(),
            'payments' => $purchase->payments(),
            'vatAmounts' => $purchase->vatAmounts(),
            'receiptCopyAllowed' => $purchase->isReceiptCopyAllowed(),
            'refund' => $purchase->isRefund(),
            'refunded' => $purchase->isRefunded(),
        ];

        $data[] = $this->userBuilder->createDataArray($purchase->user());

        if ($purchase->coordinates()) {
            $data[] = $this->coordinatesBuilder->createDataArray($purchase->coordinates());
        }

        if ($purchase->sourceType()) {
            $data['sourceType'] = $purchase->sourceType()->getValue();
        }

        if ($purchase->isPublished() !== null) {
            $data['published'] = $purchase->isPublished();
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return Purchase
     *
     * @throws Exception
     */
    private function build(array $data): Purchase
    {
        $coordinates = $data['gpsCoordinates'] ? $this->coordinatesBuilder->buildFromArray($data) : null;
        $sourceType = $data['type'] ? SourceType::get($data['type']) : null;
        $published = $data['published'] ? (bool) $data['published'] : null;

        return $this->purchaseFactory->create(
            $data['uuid'],
            $data['timestamp'],
            $data['country'],
            $data['currency'],
            $this->userBuilder->buildFromArray($data),
            (int) $data['organizationId'],
            (int) $data['purchaseNumber'],
            (float) $data['amount'],
            (float) $data['vatAmount'],
            $data['products'],
            $data['payments'],
            $data['vatAmounts'],
            (bool) $data['receiptCopyAllowed'],
            (bool) $data['refund'],
            (bool) $data['refunded'],
            $coordinates,
            $sourceType,
            (bool) $published
        );
    }
}
