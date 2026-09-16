<?php

namespace Composer\Installers;

class DframeInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('module' => 'modules/{$vendor}/{$name}/');
}
