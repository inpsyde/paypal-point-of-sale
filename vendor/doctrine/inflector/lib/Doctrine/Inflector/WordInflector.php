<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Doctrine\Inflector;

interface WordInflector
{
    public function inflect(string $word): string;
}
