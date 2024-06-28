<?php

namespace GearDev\Prometheus\Processes;


use GearDev\Core\ContextStorage\ContextStorage;
use GearDev\Coroutines\Co\CoFactory;
use GearDev\Coroutines\Co\CoManagerFactory;
use GearDev\Processes\Attributes\Process;
use GearDev\Processes\ProcessesManagement\AbstractProcess;
use GearDev\Prometheus\Container\RegistryContainer;
use Prometheus\CollectorRegistry;

#[Process(processName: 'system-metrics')]
class SystemMetricsProcess extends AbstractProcess
{

    private CollectorRegistry $registry;

    public function __construct()
    {
        $this->registry = RegistryContainer::getRegistry();
    }

    protected function run(): bool
    {
        echo json_encode(['msg'=>'System metrics process is starting...']).PHP_EOL;
        $allowedMemoryUsage = env('ALLOWED_MEMORY_USAGE', 200);
        $counterName = 'coroutine_count';
        CoFactory::createCo($this->getName() . '_' . $counterName)
            ->charge(function () use ($counterName) {
                $coroutineGauge = $this->registry->getOrRegisterGauge(
                    'system',
                    $counterName,
                    'coroutine_count');
                $coManager = CoManagerFactory::getCoroutineManager();
                while (true) {
                    $coroutineGauge->set($coManager->getCoroutineCount());
                    sleep(1);
                }
            })->run();

        $counterName = 'contextual_storage_count';
        CoFactory::createCo($this->getName() . '_' . $counterName)
            ->charge(function () use ($counterName) {
                $coroutineGauge = $this->registry->getOrRegisterGauge(
                    'system',
                    $counterName,
                    'contextual_storage_count');
                while (true) {
                    $coroutineGauge->set(ContextStorage::getStorageCountForMetric());
                    sleep(1);
                }
            })->run();

        $counterName = 'di_containers_count';
        CoFactory::createCo($this->getName() . '_' . $counterName)
            ->charge(function () use ($counterName) {
                $coroutineGauge = $this->registry->getOrRegisterGauge(
                    'system',
                    $counterName,
                    'containers_count');
                while (true) {
                    $coroutineGauge->set(ContextStorage::getContainersCountForMetric());
                    sleep(1);
                }
            })->run();

        $counterName = 'memory_usage';
        CoFactory::createCo($this->getName() . '_' . $counterName)
            ->charge(function () use ($counterName) {
                while (true) {
                    $coroutineGauge = $this->registry->getOrRegisterGauge(
                        'system',
                        'memory_usage',
                        'memory_usage');
                    $coroutineGauge->set(memory_get_usage() / 1024 / 1024);
                    sleep(1);
                }
            })->run();

        if (function_exists('gc_enabled') && gc_enabled()) {
            $counterName = 'gc_cycles_collected_count';
            CoFactory::createCo($this->getName() . '_' . $counterName)
                ->charge(function () use ($counterName, $allowedMemoryUsage) {
                    while (true) {
                        $coroutineGauge = $this->registry->getOrRegisterGauge(
                            'system',
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
                })->run();
        }

        if (env('EXIT_ON_OOM')==1) {
            CoFactory::createCo($this->getName() . '_OOM_EXITER')
                ->charge(function () use ($allowedMemoryUsage) {
                    while (true) {
                        if (memory_get_usage() / 1024 / 1024 > $allowedMemoryUsage) {
                            echo 'OOM detected. Exiting...';
//                            exit(1);
                            ContextStorage::getSystemChannel('exitChannel')->push(\Swow\Signal::TERM);
                        }
                        sleep(30);
                    }
                })->run();
        }

        return true;
    }
}
