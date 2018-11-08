<?php
// src/AppBundle/DependencyInjection/AppExtension.php
namespace AppBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AppExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        // empty extension - not yet required but is auto loaded

    }
}