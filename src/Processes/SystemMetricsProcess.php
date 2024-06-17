<?php

namespace GearDev\Prometheus\Processes;


use GearDev\Core\ContextStorage\ContextStorage;
use GearDev\Coroutines\Co\CoFactory;
use GearDev\Processes\Attributes\Process;
use GearDev\Processes\ProcessesManagement\AbstractProcess;
use Prometheus\CollectorRegistry;
use Swow\Coroutine;

#[Process(processName: 'system-metrics')]
class SystemMetricsProcess extends AbstractProcess
{

    public function __construct(private CollectorRegistry $registry)
    {
    }

    protected function run(): bool
    {
        $allowedMemoryUsage = env('ALLOWED_MEMORY_USAGE', 200);
        $counterName = 'coroutine_count';
        CoFactory::createCo($this->getName() . '_' . $counterName)
            ->charge(function () use ($counterName) {
                $coroutineGauge = $this->registry->getOrRegisterGauge(
                    config('gear.prometheus.namespace', 'gear'),
                    $counterName,
                    'coroutine_count');
                while (true) {
                    $coroutineGauge->set(Coroutine::count());
                    sleep(1);
                }
            })->runWithClonedDiContainer();

        $counterName = 'contextual_storage_count';
        CoFactory::createCo($this->getName() . '_' . $counterName)
            ->charge(function () use ($counterName) {
                $coroutineGauge = $this->registry->getOrRegisterGauge(
                    config('gear.prometheus.namespace', 'gear'),
                    $counterName,
                    'contextual_storage_count');
                while (true) {
                    $coroutineGauge->set(ContextStorage::getStorageCountForMetric());
                    sleep(1);
                }
            })->runWithClonedDiContainer();

        $counterName = 'di_containers_count';
        CoFactory::createCo($this->getName() . '_' . $counterName)
            ->charge(function () use ($counterName) {
                $coroutineGauge = $this->registry->getOrRegisterGauge(
                    config('gear.prometheus.namespace', 'gear'),
                    $counterName,
                    'containers_count');
                while (true) {
                    $coroutineGauge->set(ContextStorage::getContainersCountForMetric());
                    sleep(1);
                }
            })->runWithClonedDiContainer();

        $counterName = 'memory_usage';
        CoFactory::createCo($this->getName() . '_' . $counterName)
            ->charge(function () use ($counterName) {
                while (true) {
                    $coroutineGauge = $this->registry->getOrRegisterGauge(
                        config('gear.prometheus.namespace', 'gear'),
                        'memory_usage',
                        'memory_usage');
                    $coroutineGauge->set(memory_get_usage() / 1024 / 1024);
                    sleep(1);
                }
            })->runWithClonedDiContainer();

        if (function_exists('gc_enabled') && gc_enabled()) {
            $counterName = 'gc_cycles_collected_count';
            CoFactory::createCo($this->getName() . '_' . $counterName)
                ->charge(function () use ($counterName, $allowedMemoryUsage) {
                    while (true) {
                        $coroutineGauge = $this->registry->getOrRegisterGauge(
                            config('gear.prometheus.namespace', 'gear'),
                            $counterName,
                            'gc_cycles_collected_count');
                        if (memory_get_usage() / 1024 / 1024 > $allowedMemoryUsage) {
                            $cycles = gc_collect_cycles();
                            $coroutineGauge->set($cycles);
                        } else {
                            $coroutineGauge->set(0);
                        }
                        sleep(30);
                    }
                })->runWithClonedDiContainer();
        }

        if (env('EXIT_ON_OOM')==1) {
            CoFactory::createCo($this->getName() . '_OOM_EXITER')
                ->charge(function () use ($allowedMemoryUsage) {
                    while (true) {
                        if (memory_get_usage() / 1024 / 1024 > $allowedMemoryUsage) {
                            ContextStorage::getSystemChannel('exitChannel')->push(\Swow\Signal::TERM);
                        }
                        sleep(30);
                    }
                })->runWithClonedDiContainer();
        }

        return true;
    }
}
