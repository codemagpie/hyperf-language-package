<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage\Listener;

use CodeMagpie\HyperfLanguagePackage\Contract\TransConfigInterface;
use CodeMagpie\HyperfLanguagePackage\LanguageService;
use CodeMagpie\HyperfLanguagePackage\Utils\Timer;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Psr\Container\ContainerInterface;

class ApplicationBootListener implements ListenerInterface
{
    protected ConfigInterface $config;

    protected LanguageService $languageService;

    protected TransConfigInterface $transConfig;

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
        $this->languageService = $container->get(LanguageService::class);
        $this->transConfig = $container->get(TransConfigInterface::class);
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event)
    {
        Timer::refreshSyncAt(time());
        $this->syncTransConfig();
    }

    protected function syncTransConfig()
    {
        // 进程启动前将翻译配置加载到进程内存中
        $parentModuleIds = $this->config->get('douyu_language_translation.load_modules');
        if (! $parentModuleIds) {
            throw new \InvalidArgumentException('请配置项目需要加载的模块id列表');
        }
        $subModuleIds = $this->languageService->getSubModuleIds($parentModuleIds);
        $nextId = null;
        do {
            $queryParams = [
                'next_id' => $nextId,
                'page' => 1,
                'page_size' => 10000,
            ];
            $transConfigs = $this->languageService->getTranslationsByModuleIds($subModuleIds, [], $queryParams);
            foreach ($transConfigs as $item) {
                $nextId = $item['id'];
                $this->transConfig->set($item['module_id'], $item['entry_code'], $item['locale'], $item['translation']);
            }
        } while ($transConfigs);
    }
}
