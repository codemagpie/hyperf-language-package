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
        $file = __DIR__ . '/../../cache/sync-at.txt';
        $syncAt = file_get_contents($file);
        if (! $syncAt) {
            $syncAt = time();
            file_put_contents($file, $syncAt);
        }
        return (int) $syncAt;
    }

    public static function refreshSyncAt(int $time): void
    {
        $file = __DIR__ . '/../../cache/sync-at.txt';
        file_put_contents($file, $time);
    }
}
