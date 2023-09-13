<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage\Utils;

class Timer
{
    public static function fetchSyncAt(): int
    {
        $file = self::getFilename();
        if (! file_exists($file)) {
            $syncAt = 0;
        } else {
            $syncAt = file_get_contents($file);
        }
        if (! $syncAt) {
            $syncAt = time();
            file_put_contents($file, $syncAt);
        }
        return (int) $syncAt;
    }

    public static function refreshSyncAt(int $time): void
    {
        $file = self::getFilename();
        file_put_contents($file, $time);
    }

    protected static function getFilename(): string
    {
        return __DIR__ . '/../../sync-at.txt';
    }
}
