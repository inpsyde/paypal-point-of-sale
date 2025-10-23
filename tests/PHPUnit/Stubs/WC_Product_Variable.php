<?php
declare(strict_types=1);

class WC_Product_Variable extends WC_Product
{

    public function __construct(int $id, array $data = [])
    {
        $data = array_merge(
            [
                'attributes' => [],
                'visible_children' => [
                    rand(0, PHP_INT_MAX),
                ],
            ],
            $data
        );
        parent::__construct($id, $data);
    }

    public function get_variation_attributes()
    {
        $result = [];
        foreach ($this->get_attributes() as $attribute) {
            $slugs = [];
            foreach ($attribute->get_options() as $option) {
                $term = get_term($option);
                $slugs[] = $term->slug;
            }
            $result[$attribute->get_name()] = $slugs;
        }

        return $result;
    }
}
