<?php
declare(strict_types=1);

use Faker\Provider\Lorem;
use Faker\Provider\Text;

class WC_Product
{

    static $repo = [];

    /**
     * @var array
     */
    private $data;

    public function __construct(int $id = null, array $data = null)
    {
        $productName = Lorem::sentence(4);
        if (array_key_exists($id, self::$repo)) {
            $this->data = self::$repo[$id];

            return;
        }
        $this->data = array_merge(
            [
                'name' => $productName,
                'id' => $id ?? rand(1, PHP_INT_MAX),
                'description' => Lorem::sentence(20),
                'sku' => $this->createSkuFromProductName($productName),
                'weight' => (float) Text::numberBetween(1, 100),
                'image_id' => Text::numberBetween(50, 60),
                'gallery_image_ids' => [
                    Text::numberBetween(60, 64),
                    Text::numberBetween(60, 64),
                    Text::numberBetween(60, 64),
                ],
                'regular_price' => (float) Text::numberBetween(50, 500),
                'managing_stock' => false,
                'stock_quantity' => Text::numberBetween(10, 100),
                'is_purchasable' => true,
                'attributes' => [],
                'children' => [],
                'date_created' => new DateTime(),
                'tax_class' => '',
                'tax_status' => 'taxable',
                'meta' => '',
            ],
            $data ?? []
        );
        self::$repo[$id] = $this->data;
    }

    public function get_name()
    {
        return $this->data['name'];
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed|void
     * @throws Exception
     */
    public function __call(string $name, array $arguments)
    {
        if ($name === 'save') {
            return $this->data['id'];
        }
        $prop = str_replace('get_', '', $name);

        if (array_key_exists($prop, $this->data)) {
            return $this->data[$prop];
        }
        $prop = str_replace('set_', '', $name);
        if (array_key_exists($prop, $this->data)) {
            $this->data[$prop] = $arguments[0];
            self::$repo[$this->data['id']][$prop] = $arguments[0];

            return;
        }

        throw new Exception('Method not found: '.$name);
    }

    /**
     * @return bool
     */
    public function managing_stock(): bool
    {
        return $this->data['managing_stock'] ?? false;
    }

    /**
     * Checks the product type.
     *
     * Backwards compatibility with downloadable/virtual.
     *
     * @param  string|array $type Array or string of types.
     * @return bool
     */
    public function is_type($type)
    {
        return $this->get_type() === $type
            || (is_array($type) && in_array($this->get_type(), $type, true));
    }

    private function createSkuFromProductName(string $productName): string
    {
        $productName = strtolower($productName);
        $productName = preg_replace("/[^a-z0-9_\s-]/", '', $productName);
        $productName = preg_replace("/[\s-]+/", ' ', $productName);
        $productName = preg_replace("/[\s_]/", '_', $productName);

        return $productName;
    }

    public static function flush()
    {
        self::$repo = [];
    }
}
