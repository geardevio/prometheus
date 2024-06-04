<?php

namespace FrockDev\Prometheus;

use FrockDev\ToolsForLaravel\AnnotationsCollector\Collector;
use Prometheus\Storage\InMemory;

class FrockPrometheusProvider extends \Illuminate\Support\ServiceProvider
{
    public function register() {
        Collector::addPackageToCollector(__DIR__);

        $this->app->singleton(\Prometheus\CollectorRegistry::class, function() {
            return new \Prometheus\CollectorRegistry($this->app->make(InMemory::class));
        });
    }

    public function boot() {

    }
}