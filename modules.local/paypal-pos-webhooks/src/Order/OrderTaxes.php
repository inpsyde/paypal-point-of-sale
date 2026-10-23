<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\MinorUnits;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Tax;
use WC_Tax;

/**
 * Tax lines of POS orders: per-rate totals of the purchase lines, matched to WC tax rates.
 */
class OrderTaxes
{
    /**
     * @var array<string, int> WC tax rate ids by country and percentage.
     */
    private array $taxRateIds = [];

    /**
     * Adds a line's tax to the per-rate totals, negated with $sign -1 for refunds.
     *
     * @param array<int|string, array{rateId: int, percentage: float, taxMinor: int}> $taxTotals
     *
     * @return int The line's WC tax rate id, or 0 when there is none.
     */
    public function addLineTax(array &$taxTotals, LineAmounts $amounts, string $country, int $sign = 1): int
    {
        $rateId = 0;
        // A fully discounted line has no tax left but still needs its rate for the subtotal tax.
        if ($amounts->subtotalTaxMinor() > 0) {
            $rateId = $this->taxRateId($amounts->vatPercentage(), $country);
        }
        if ($amounts->taxMinor() > 0) {
            $key = $rateId > 0 ? $rateId : 'p' . (string) $amounts->vatPercentage();
            $taxTotals[$key] = [
                'rateId' => $rateId,
                'percentage' => $amounts->vatPercentage(),
                'taxMinor' => ($taxTotals[$key]['taxMinor'] ?? 0) + $sign * $amounts->taxMinor(),
            ];
        }

        return $rateId;
    }

    /**
     * With a single rate, uses Zettle's purchase VAT so per-line rounding can't drift, moving the
     * difference onto the last line with that rate.
     *
     * @param array<int|string, array{rateId: int, percentage: float, taxMinor: int}> $taxTotals
     *
     * @return int The difference moved onto a line, in minor units.
     */
    public function alignToPurchaseVat(WC_Order $order, array &$taxTotals, int $vatMinor, string $currency): int
    {
        if (count($taxTotals) !== 1) {
            return 0;
        }

        $key = array_key_first($taxTotals);
        $applied = $this->alignLineTaxes(
            $order,
            $taxTotals[$key]['rateId'],
            $vatMinor - $taxTotals[$key]['taxMinor'],
            $currency
        );
        $taxTotals[$key]['taxMinor'] = $vatMinor;

        return $applied;
    }

    /**
     * @param array<int|string, array{rateId: int, percentage: float, taxMinor: int}> $taxTotals
     *
     * @return int The order's tax total in minor units.
     */
    public function addTaxItems(WC_Order $order, array $taxTotals, string $currency): int
    {
        foreach ($taxTotals as $taxTotal) {
            $percentage = rtrim(rtrim(number_format($taxTotal['percentage'], 2, '.', ''), '0'), '.');
            $tax = new WC_Order_Item_Tax();
            $tax->set_rate_id($taxTotal['rateId']);
            $tax->set_rate_code(sprintf('PAYPAL-POS-TAX-%s', $percentage));
            /* translators: %s: tax rate percentage, e.g. 20 */
            $tax->set_label(sprintf(__('Tax %s%%', 'paypal-point-of-sale'), $percentage));
            $tax->set_rate_percent($taxTotal['percentage']);
            $tax->set_compound(false);
            $tax->set_tax_total((string) MinorUnits::toMajor($taxTotal['taxMinor'], $currency));
            $tax->set_shipping_tax_total('0');
            $order->add_item($tax);
        }

        return (int) array_sum(array_column($taxTotals, 'taxMinor'));
    }

    /**
     * Finds the WC tax rate with the same percentage for the purchase country, in the standard
     * tax class first.
     */
    private function taxRateId(float $percentage, string $country): int
    {
        $cacheKey = strtoupper($country) . '|' . (string) $percentage;
        if (!array_key_exists($cacheKey, $this->taxRateIds)) {
            $this->taxRateIds[$cacheKey] = $this->findTaxRateId($percentage, $country);
        }

        return $this->taxRateIds[$cacheKey];
    }

    private function findTaxRateId(float $percentage, string $country): int
    {
        foreach (array_merge([''], WC_Tax::get_tax_class_slugs()) as $taxClass) {
            $rates = WC_Tax::find_rates(['country' => strtoupper($country), 'tax_class' => $taxClass]);
            foreach ($rates as $rateId => $rate) {
                if (abs((float) $rate['rate'] - $percentage) < 0.0001) {
                    return (int) $rateId;
                }
            }
        }

        return 0;
    }

    /**
     * @return int The difference applied, in minor units.
     */
    private function alignLineTaxes(WC_Order $order, int $rateId, int $differenceMinor, string $currency): int
    {
        if ($rateId <= 0 || $differenceMinor === 0) {
            return 0;
        }

        $lines = array_reverse((array) $order->get_items(['line_item', 'fee']));
        foreach ($lines as $line) {
            if (!$line instanceof WC_Order_Item_Product && !$line instanceof WC_Order_Item_Fee) {
                continue;
            }
            $taxes = (array) $line->get_taxes();
            if (!isset($taxes['total'][$rateId])) {
                continue;
            }
            $taxes['total'][$rateId] = (string) MinorUnits::toMajor(
                MinorUnits::toMinor((float) $taxes['total'][$rateId], $currency) + $differenceMinor,
                $currency
            );
            $line->set_taxes($taxes);

            return $differenceMinor;
        }

        return 0;
    }
}
