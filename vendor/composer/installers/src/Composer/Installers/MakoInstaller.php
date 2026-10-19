<?php

namespace Composer\Installers;

class MakoInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('package' => 'app/packages/{$name}/');
}
