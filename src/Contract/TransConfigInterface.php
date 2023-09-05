<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage\Contract;

use Hyperf\Utils\Collection;

interface TransConfigInterface
{
    public function set(int $moduleId, string $entryCode, string $locale, string $trans): void;

    public function get(int $moduleId): array;

    public function getTrans(string $entryCode, string $locale, string $default = ''): string;

    public function hasCollection(): bool;
}
