<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Barcode;

use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Repository\WooCommerce\Product\ProductRepositoryInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Barcode\Repository\BarcodeSaverInterface;
use WC_Product_Variation;
class VariantBarcodeSaveHandler
{
    private BarcodeSaverInterface $barcodeSaver;
    private BarcodeInputField $barcodeField;
    private ProductRepositoryInterface $wcProductRepository;
    private LoggerInterface $logger;
    public function __construct(BarcodeSaverInterface $barcodeSaver, BarcodeInputField $barcodeField, ProductRepositoryInterface $wcProductRepository, LoggerInterface $logger)
    {
        $this->barcodeSaver = $barcodeSaver;
        $this->barcodeField = $barcodeField;
        $this->wcProductRepository = $wcProductRepository;
        $this->logger = $logger;
    }
    public function save(int $variationId, int $variantIndex): void
    {
        $variation = $this->wcProductRepository->findById($variationId);
        if (!$variation instanceof WC_Product_Variation) {
            $this->logger->warning(sprintf('Variation %1$d not found during variation settings saving.', $variantIndex));
            return;
        }
        $barcode = $this->getBarcode($variantIndex);
        if ($barcode === null) {
            return;
        }
        $this->barcodeSaver->save($variation, $barcode);
    }
    private function getBarcode(int $variantIndex): ?string
    {
        // phpcs:ignore WordPressVIPMinimum.Security.PHPFilterFunctions.RestrictedFilter
        $barcodes = filter_input(\INPUT_POST, $this->barcodeField->name(), \FILTER_DEFAULT, \FILTER_REQUIRE_ARRAY);
        if ($barcodes === \false) {
            $this->logger->warning('Got incorrect barcode value during variation settings saving.');
            return null;
        }
        if (!is_array($barcodes) || !isset($barcodes[$variantIndex])) {
            return null;
        }
        /** @psalm-suppress PossiblyInvalidCast */
        return $this->sanitizeText((string) $barcodes[$variantIndex]);
    }
    private function sanitizeText(string $text): string
    {
        return sanitize_text_field(wp_unslash($text));
    }
}
