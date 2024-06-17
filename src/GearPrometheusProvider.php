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

        $this->app->singleton(CollectorRegistry::class, function() {
            return new CollectorRegistry($this->app->make(InMemory::class));
        });
    }

    public function boot() {

    }
}