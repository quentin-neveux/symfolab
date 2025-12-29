<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        $contents = require dirname(__DIR__).'/config/bundles.php';

        foreach ($contents as $class => $envs) {
            if ($envs['all'] ?? false || ($envs[$this->environment] ?? false)) {
                yield new $class();
            }
        }
    }
}
