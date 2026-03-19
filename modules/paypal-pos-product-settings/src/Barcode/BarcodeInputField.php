<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Barcode;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Barcode\Repository\BarcodeRetrieverInterface;
use WC_Product;
class BarcodeInputField
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var BarcodeRetrieverInterface
     */
    private $barcodeRetriever;
    /**
     * @var string
     */
    private $label;
    /**
     * @var string
     */
    private $containerClasses;
    /**
     * @param string $containerClasses Additional CSS classes for the container of input field.
     */
    public function __construct(string $name, BarcodeRetrieverInterface $barcodeRetriever, string $label, string $containerClasses = '')
    {
        $this->name = $name;
        $this->barcodeRetriever = $barcodeRetriever;
        $this->label = $label;
        $this->containerClasses = $containerClasses;
    }
    // phpcs:ignore Inpsyde.CodeQuality.FunctionLength.TooLong
    public function render(WC_Product $product, ?int $index = null): string
    {
        $name = $this->name . ($index !== null ? "[{$index}]" : '');
        $id = $this->name . ($index !== null ? $index : '');
        $currentBarcode = $this->barcodeRetriever->get($product) ?? '';
        ob_start();
        ?>

        <div class="zettle-barcode-input">
            <p class="zettle-barcode-input-field <?php 
        echo esc_attr($this->containerClasses);
        ?>">
                <label for="<?php 
        echo esc_attr($id);
        ?>"><?php 
        echo esc_html($this->label);
        ?></label>
                <span>
                    <input id="<?php 
        echo esc_attr($id);
        ?>"
                           type="text"
                           name="<?php 
        echo esc_attr($name);
        ?>"
                           value="<?php 
        echo esc_attr($currentBarcode);
        ?>">
                    <button
                            type="button"
                            aria-label="<?php 
        echo esc_attr__('Scan barcode', 'paypal-point-of-sale');
        ?>">
                        📷
                    </button>
                </span>
            </p>

            <div class="zettle-barcode-scan" style="display: none">
                <div>
                    <label><?php 
        echo esc_html__('Barcode type', 'paypal-point-of-sale');
        ?>
                        <select name="barcode_type">
                            <option value="ean_extended,ean,ean_8,code_128,code_39" selected="selected">
                                <?php 
        echo esc_html__('EAN (13, 8, extended), Code 128, Code 39', 'paypal-point-of-sale');
        ?>
                            </option>
                            <option value="code_128">Code 128</option>
                            <option value="code_39">Code 39</option>
                            <option value="code_39_vin">Code 39 VIN</option>
                            <option value="ean">EAN-13</option>
                            <option value="ean_extended"><?php 
        echo esc_html__('EAN extended', 'paypal-point-of-sale');
        ?></option>
                            <option value="ean_8">EAN-8</option>
                            <option value="upc">UPC-A</option>
                            <option value="upc_e">UPC-E</option>
                            <option value="codabar">Codabar</option>
                            <option value="i2of5"><?php 
        echo esc_html__('I2 of 5', 'paypal-point-of-sale');
        ?></option>
                            <option value="2of5"><?php 
        echo esc_html__('Standard 2 of 5', 'paypal-point-of-sale');
        ?></option>
                            <option value="code_93">Code 93</option>
                        </select>
                    </label>
                </div>
                <div>
                    <label><?php 
        echo esc_html__('Camera', 'paypal-point-of-sale');
        ?>
                        <select name="camera">
                        </select>
                    </label>
                </div>

                <div class="zettle-barcode-scanner-viewport">
                </div>
            </div>
        </div>

        <?php 
        return ob_get_clean();
    }
    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
}
