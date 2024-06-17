<?php

namespace GearDev\Prometheus\Warmers;

use Illuminate\Foundation\Application;
use Prometheus\Storage\InMemory;

class SimpleStoragePrometheusWarmer implements \GearDev\Core\Warmers\WarmerInterface
{
    public function warm(Application $app): void
    {
        $classes = [
            InMemory::class,
        ];
        foreach ($classes as $service) {
            if (is_string($service) && $app->bound($service)) {
                $app->make($service);
            }
        }
    }
}