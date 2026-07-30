<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Doctrine\Inflector\Rules\Spanish;

use Syde\Vendor\Zettle\Doctrine\Inflector\Rules\Patterns;
use Syde\Vendor\Zettle\Doctrine\Inflector\Rules\Ruleset;
use Syde\Vendor\Zettle\Doctrine\Inflector\Rules\Substitutions;
use Syde\Vendor\Zettle\Doctrine\Inflector\Rules\Transformations;
final class Rules
{
    public static function getSingularRuleset(): Ruleset
    {
        return new Ruleset(new Transformations(...Inflectible::getSingular()), new Patterns(...Uninflected::getSingular()), (new Substitutions(...Inflectible::getIrregular()))->getFlippedSubstitutions());
    }
    public static function getPluralRuleset(): Ruleset
    {
        return new Ruleset(new Transformations(...Inflectible::getPlural()), new Patterns(...Uninflected::getPlural()), new Substitutions(...Inflectible::getIrregular()));
    }
}
