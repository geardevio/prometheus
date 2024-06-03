<?php

namespace FrockDev\Prometheus;

use FrockDev\ToolsForLaravel\AnnotationsCollector\Collector;
use Prometheus\Storage\InMemory;

class FrockPrometheusProvider extends \Illuminate\Support\ServiceProvider
{
    public function register() {
        Collector::addPackageToCollector(base_path().'/vendor/frock/prometheus');

        $this->app->singleton(\Prometheus\CollectorRegistry::class, function() {
            return new \Prometheus\CollectorRegistry($this->app->make(InMemory::class));
        });
    }

    public function boot() {

    }
}