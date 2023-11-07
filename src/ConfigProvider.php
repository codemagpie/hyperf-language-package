<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage;

use CodeMagpie\HyperfLanguagePackage\Contract\TransConfigInterface;
use CodeMagpie\HyperfLanguagePackage\Listener\ApplicationBootListener;
use CodeMagpie\HyperfLanguagePackage\Listener\FetchTransConfigOnBootListener;
use CodeMagpie\HyperfLanguagePackage\Listener\OnPipeMessageListener;
use CodeMagpie\HyperfLanguagePackage\Process\TransConfigFetcherProcess;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                TransConfigInterface::class => TransConfig::class,
            ],
            'listeners' => [
                ApplicationBootListener::class,
                FetchTransConfigOnBootListener::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for language package.',
                    'source' => __DIR__ . '/../publish/douyu_language_translation.php',
                    'destination' => BASE_PATH . '/config/autoload/douyu_language_translation.php',
                ],
            ],
        ];
    }
}
