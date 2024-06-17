<?php

namespace GearDev\Prometheus\Warmers;

use GearDev\Core\Attributes\Warmer;
use GearDev\Core\Warmers\WarmerInterface;
use Illuminate\Foundation\Application;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
#[Warmer]
class SimpleStoragePrometheusWarmer implements WarmerInterface
{
    public function warm(Application $app): void
    {
        $collectorRegistry = new CollectorRegistry(app()->make(InMemory::class));
        app()->singleton(CollectorRegistry::class, function() use ($collectorRegistry) {
            return $collectorRegistry;
        });
    }
}
