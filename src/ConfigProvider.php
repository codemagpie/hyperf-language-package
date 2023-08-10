<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace Douyu\HyperfLanguagePackage;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
            ],
            'commands' => [
            ],
            'listeners' => [
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
