<?php

namespace GearDev\Prometheus;


use GearDev\Collector\Collector\Collector;
use Illuminate\Support\ServiceProvider;

class GearPrometheusProvider extends ServiceProvider
{
    public function register() {
        Collector::addPackageToCollector(__DIR__);
    }

    public function boot() {

    }
}
