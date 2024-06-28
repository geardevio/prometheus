<?php

namespace GearDev\Prometheus\Container;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;

class RegistryContainer
{
    private static ?CollectorRegistry $registry = null;
    public static function getRegistry(): CollectorRegistry {
        if (!self::$registry) {
            self::$registry = new CollectorRegistry(new InMemory());
        }
        return self::$registry;
    }
}