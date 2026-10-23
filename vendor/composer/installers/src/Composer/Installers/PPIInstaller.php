<?php

namespace Composer\Installers;

class PPIInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('module' => 'modules/{$name}/');
}
