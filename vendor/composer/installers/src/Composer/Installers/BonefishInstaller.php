<?php

namespace Composer\Installers;

class BonefishInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('package' => 'Packages/{$vendor}/{$name}/');
}
