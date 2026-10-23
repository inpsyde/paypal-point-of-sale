<?php

namespace Composer\Installers;

class PhiftyInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('bundle' => 'bundles/{$name}/', 'library' => 'libraries/{$name}/', 'framework' => 'frameworks/{$name}/');
}
