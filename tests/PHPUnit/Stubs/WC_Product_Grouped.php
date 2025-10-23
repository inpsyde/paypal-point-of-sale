<?php
declare(strict_types=1);

use Faker\Provider\Text;

class WC_Product_Grouped extends WC_Product
{
    public function __construct(int $id, array $data = [])
    {
        $data = array_merge(
            [
                'children' => [
                    Text::numberBetween(55, 65)
                ]
            ],
            $data
        );

        parent::__construct($id, $data);
    }
}