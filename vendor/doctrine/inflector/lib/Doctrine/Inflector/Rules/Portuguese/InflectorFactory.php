<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Doctrine\Inflector\Rules\Portuguese;

use Syde\Vendor\Zettle\Doctrine\Inflector\GenericLanguageInflectorFactory;
use Syde\Vendor\Zettle\Doctrine\Inflector\Rules\Ruleset;
final class InflectorFactory extends GenericLanguageInflectorFactory
{
    protected function getSingularRuleset(): Ruleset
    {
        return Rules::getSingularRuleset();
    }
    protected function getPluralRuleset(): Ruleset
    {
        return Rules::getPluralRuleset();
    }
}
