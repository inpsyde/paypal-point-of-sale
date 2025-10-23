<?php
declare(strict_types=1);

class WC_Product_Variation extends WC_Product
{

    public function __construct(int $id, array $data = [])
    {
        $data = array_merge(
            [
                'attributes' => [],
                'parent_id' => rand(1, PHP_INT_MAX),
            ],
            $data
        );
        parent::__construct($id, $data);
    }
}
