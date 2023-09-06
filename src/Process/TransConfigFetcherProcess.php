<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage\Process;

use CodeMagpie\HyperfLanguagePackage\Contract\TransConfigInterface;
use CodeMagpie\HyperfLanguagePackage\LanguageService;
use CodeMagpie\HyperfLanguagePackage\PipeMessage;
use CodeMagpie\HyperfLanguagePackage\Utils\Timer;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessCollector;
use Psr\Container\ContainerInterface;
use Swoole\Server;

class TransConfigFetcherProcess extends AbstractProcess
{
    /**
     * @var Server
     */
    protected $server;

    protected ConfigInterface $config;

    protected LanguageService $languageService;

    protected TransConfigInterface $transConfig;

    protected StdoutLoggerInterface $logger;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->config = $container->get(ConfigInterface::class);
        $this->languageService = $container->get(LanguageService::class);
        $this->transConfig = $container->get(TransConfigInterface::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    public function bind($server): void
    {
        $this->server = $server;
        parent::bind($server);
    }

    public function handle(): void
    {
        $moduleIds = $this->config->get('douyu_language_translation.load_modules');
        if (! $moduleIds) {
            throw new \InvalidArgumentException('请配置项目需要加载的模块id列表');
        }
        $subModuleIds = $this->languageService->getSubModuleIds($moduleIds);
        $refreshRate = (int) $this->config->get('douyu_language_translation.refresh_rate', 60);
        while (true) {
            $this->logger->info(sprintf('%s language-package updating...', __CLASS__));
            $queryParams = [
                'updated_at_start' => Timer::fetchSyncAt(),
            ];
            try {
                $transConfigs = $this->languageService->getTranslationsByModuleIds($subModuleIds, [], $queryParams);
                if ($transConfigs) {
                    foreach ($transConfigs as $item) {
                        $this->transConfig->set($item['module_id'], $item['entry_code'], $item['locale'], $item['translation']);
                    }
                    Timer::refreshSyncAt(time());
                    // 通过进程间通信,发送到每个进程
                    $message = new PipeMessage($transConfigs);
                    $this->shareMessageToWorkers($message);
                    $this->shareMessageToUserProcesses($message);
                }
            } catch (\Throwable $e) {
                Timer::refreshSyncAt($queryParams['updated_at_start']);
                $this->logger->error('Trans Configuration synchronization failed.' . $e->getMessage());
            }
            $this->logger->info(sprintf('%s language-package update finish', __CLASS__));
            sleep($refreshRate);
        }
    }

    protected function shareMessageToWorkers($message): void
    {
        if ($this->server instanceof Server) {
            $workerCount = $this->server->setting['worker_num'] + ($this->server->setting['task_worker_num'] ?? 0) - 1;
            for ($workerId = 0; $workerId <= $workerCount; ++$workerId) {
                $this->server->sendMessage($message, $workerId);
            }
        }
    }

    protected function shareMessageToUserProcesses($message): void
    {
        $processes = ProcessCollector::all();
        if ($processes) {
            $string = serialize($message);
            /** @var \Swoole\Process $process */
            foreach ($processes as $process) {
                $result = $process->exportSocket()->send($string, 10);
                if ($result === false) {
                    $this->logger->error('Trans Configuration synchronization failed. Please restart the server.');
                }
            }
        }
    }
}
