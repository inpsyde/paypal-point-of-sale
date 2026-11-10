<?php

namespace Composer\Installers;

class FuelphpInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('component' => 'components/{$name}/');
}
