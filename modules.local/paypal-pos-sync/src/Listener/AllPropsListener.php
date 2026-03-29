<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Listener;

use Inpsyde\WcProductContracts\ProductState;
use Syde\PayPal\PointOfSale\Sync\Job\ExportProductJob;
use WC_Product;
use WC_Product_Variation;

/**
 * Enqueues the UpdateProductJob whenever there are any product changes.
 *
 * @see ProductEventListenerRegistry::onChange()
 */
class AllPropsListener
{
    /**
     * @var callable
     */
    private $createJob;

    /**
     * @var callable(int):bool
     */
    private $isSyncable;

    /**
     * AllPropsPropertyListener constructor.
     *
     * @param callable $createJob
     * @param callable(int):bool $isSyncable
     */
    public function __construct(
        callable $createJob,
        callable $isSyncable
    ) {

        $this->createJob = $createJob;
        $this->isSyncable = $isSyncable;
    }

    public function __invoke(WC_Product $new, WC_Product $old): void
    {
        /**
         * We have an explicit listener for variations. The rest can go through here
         */
        if ($new instanceof WC_Product_Variation) {
            return;
        }

        $productId = (int) $new->get_id();

        if (!($this->isSyncable)($productId)) {
            return;
        }

        ($this->createJob)(
            ExportProductJob::TYPE,
            $this->addSaltOnRePublish(
                [
                    'productId' => $productId,
                ],
                $new,
                $old
            )
        );
    }

    /**
     * Usually, ExportProductJobs will not be added twice with the same args.
     * If a product got trashed and restored in quick succession,
     * it's possible for the needed re-sync job to be ignored because
     * a previous one has not been processed yet.
     * For these edge-cases, we simply add a little salt to the $args array
     *
     * @param array $args
     * @param WC_Product $new
     * @param WC_Product $old
     *
     * @return array
     */
    private function addSaltOnRePublish(array $args, WC_Product $new, WC_Product $old): array
    {
        if ($new->get_status() === ProductState::PUBLISH && $old->get_status() !== ProductState::PUBLISH) {
            $args['salt'] = 'published';
        }

        return $args;
    }
}
