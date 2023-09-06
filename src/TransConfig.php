<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage;

use CodeMagpie\HyperfLanguagePackage\Contract\TransConfigInterface;
use Hyperf\Utils\Collection;

class TransConfig implements TransConfigInterface
{
    protected Collection $collection;

    public function __construct(array $items = [])
    {
        $this->collection = Collection::make($items);
    }

    public function getTrans(string $entryCode, string $locale, string $default = ''): string
    {
        foreach ($this->collection->all() as $item) {
            if ($trans = $item[$entryCode][$locale] ?? null) {
                return $trans;
            }
        }
        return $default;
    }

    public function set(int $moduleId, string $entryCode, string $locale, string $trans): void
    {
        $config = $this->collection->get($moduleId, []);
        $config[$entryCode][$locale] = $trans;
        $this->collection->put($moduleId, $config);
    }

    public function get(int $moduleId): array
    {
        return $this->collection->get($moduleId) ?: [];
    }

    public function hasCollection(): bool
    {
        return $this->collection->isNotEmpty();
    }
}
