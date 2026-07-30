<?php

namespace Composer\Installers;

class UserFrostingInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('sprinkle' => 'app/sprinkles/{$name}/');
}
