<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

class WC_Product_Attribute implements ArrayAccess
{

    /**
     * @var array
     */
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = array_merge(
            [
                'id' => 0,
                'name' => '__foo',
                'options' => [],
                'position' => rand(0, PHP_INT_MAX),
                'visible' => true,
                'variation' => false,
                'taxonomy' => '',
                'is_taxonomy' => false,
            ],
            $data
        );
    }

    public function is_taxonomy()
    {
        return $this->data['is_taxonomy'];
    }

    public function get_terms()
    {
        if (!$this->is_taxonomy()) {
            return null;
        }
        $terms = [];
        foreach ($this->get_options() as $termId) {
            $terms[] = get_term($termId);
        }

        return $terms;
    }

    public function __call(string $name, array $arguments)
    {
        $prop = str_replace('get_', '', $name);

        if (array_key_exists($prop, $this->data)) {
            return $this->data[$prop];
        }

        throw new Exception('Method not found: '.$prop);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}