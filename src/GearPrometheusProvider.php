<?php

namespace GearDev\Prometheus;


use GearDev\Collector\Collector\Collector;
use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;

class GearPrometheusProvider extends ServiceProvider
{
    public function register() {
        Collector::addPackageToCollector(__DIR__);

        $collectorRegistry = new CollectorRegistry(app()->make(InMemory::class));
        app()->singleton(CollectorRegistry::class, function() use ($collectorRegistry) {
            return $collectorRegistry;
        });
    }

    public function boot() {

    }
}
