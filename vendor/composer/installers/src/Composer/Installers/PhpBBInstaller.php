<?php

namespace Composer\Installers;

class PhpBBInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('extension' => 'ext/{$vendor}/{$name}/', 'language' => 'language/{$name}/', 'style' => 'styles/{$name}/');
}
