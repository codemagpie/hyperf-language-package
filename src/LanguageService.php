<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage;

use CodeMagpie\HyperfLanguagePackage\DTO\Command\CreateModuleCommand;
use CodeMagpie\HyperfLanguagePackage\DTO\Command\CreateTransConfigCommand;
use CodeMagpie\HyperfLanguagePackage\DTO\Command\UpdateModuleCommand;
use CodeMagpie\HyperfLanguagePackage\DTO\Command\UpdateTransConfigCommand;
use CodeMagpie\HyperfLanguagePackage\DTO\Meta\Translation;
use Hyperf\Database\ConnectionInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Collection;

class LanguageService
{
    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * 创建模块.
     */
    public function createModule(CreateModuleCommand $command): int
    {
        $time = time();
        return $this->getConnection()
            ->table('language_module')
            ->insertGetId([
                'name' => $command->getName(),
                'parent_id' => $command->getParentId(),
                'description' => $command->getDescription(),
                'created_at' => $time,
                'updated_at' => $time,
            ]);
    }

    /**
     * 删除模块.
     */
    public function delModule(int $id): void
    {
        // 判断是否有下级模块
        if ($this->getConnection()->table('language_module')->where('parent_id', $id)->exists()) {
            throw new \InvalidArgumentException('该模块下有子模块，不能删除');
        }
        // 判断是否有翻译配置
        if ($this->getConnection()->table('language_config')->where('module_id', $id)->exists()) {
            throw new \InvalidArgumentException('该模块下有翻译配置，不能删除');
        }
        // 删除模块
        $this->getConnection()->table('language_module')->where('id', $id)->delete();
    }

    /**
     * 更新模块.
     */
    public function updateModule(UpdateModuleCommand $command): void
    {
        // 判断父模块是否是该模块的子块
        $subModuleIds = $this->getSubModuleIds($command->getId());
        if (in_array($command->getParentId(), $subModuleIds, true)) {
            throw new \InvalidArgumentException('父模块不能是自己和自己的子模块');
        }
        $time = time();
        $this->getConnection()->table('language_module')
            ->where('id', $command->getId())
            ->update([
                'name' => $command->getName(),
                'parent_id' => $command->getParentId(),
                'description' => $command->getDescription(),
                'updated_at' => $time,
            ]);
    }

    public function getSubModuleIds(int $moduleId, bool $includeSelf = true): array
    {
        $subModuleIds = [];
        $parentIds = [$moduleId];
        do {
            $subModules = $this->getConnection()->table('language_module')
                ->whereIn('parent_id', $parentIds)
                ->get();
            if ($subModules->isEmpty()) {
                break;
            }
            $parentIds = $subModules->pluck('id')->toArray();
            $subModuleIds[] = $parentIds;
        } while ($parentIds);
        $subModuleIds = array_merge(...$subModuleIds);
        if ($includeSelf) {
            array_unshift($subModuleIds, $moduleId);
        }
        return $subModuleIds;
    }

    /**
     * 获取模块列表.
     */
    public function getModules(string $name = '', int $page = 1, int $pageSize = 10): array
    {
        $query = $this->getConnection()
            ->table('language_module')
            ->orderBy('id', 'desc');
        if ($name) {
            $query->where('name', 'like', "%{$name}%");
        }
        $list = $query->forPage($page, $pageSize)->get();
        if ($list->isEmpty()) {
            return [];
        }
        // 查找父模块名称
        $parentIds = $list->where('parent_id', '!=', 0)->pluck('parent_id')->unique()->toArray();
        $parentModules = $this->getConnection()->table('language_module')
            ->select(['id', 'name'])
            ->whereIn('id', $parentIds)
            ->get();
        return $list->map(function ($item) use ($parentModules) {
            $item = (array) $item;
            $parent = $parentModules->where('id', $item['parent_id'])->first();
            $item['parent_name'] = $parent ? $parent->name : '';
            return $item;
        })->toArray();
    }

    /**
     * 获取子模块列表.
     */
    public function getSubModules(int $parentId = 0, string $name = ''): array
    {
        $query = $this->getConnection()
            ->table('language_module')
            ->where('parent_id', $parentId);
        if ($name) {
            $query->where('name', 'like', "%{$name}%");
        }
        return $query->get()
            ->map(function ($item) {
                return (array) $item;
            })->toArray();
    }

    /**
     * 获取模块树.
     */
    public function getModulesTree(array $parentIds = [0]): array
    {
        $modules = $this->getConnection()->table('language_module')
            ->select(['id', 'parent_id', 'name'])
            ->whereIn('parent_id', $parentIds)
            ->get();
        if ($modules->isEmpty()) {
            return [];
        }
        $subModules = Collection::make($this->getModulesTree($modules->pluck('id')->toArray()));
        return $modules->map(function ($item) use ($subModules) {
            $item = (array) $item;
            $item['children'] = $subModules->where('parent_id', $item['id'])->values()->toArray();
            return $item;
        })->toArray();
    }

    /**
     * 添加翻译配置.
     */
    public function createTransConfig(CreateTransConfigCommand $command): int
    {
        // 判断词条是否已存在
        if ($this->getConnection()->table('language_config')->where('entry_code', $command->getEntryCode())->exists()) {
            throw new \InvalidArgumentException('词条已存在');
        }
        // 判断模块是否存在
        if (! $this->getConnection()->table('language_module')->where('id', $command->getModuleId())->exists()) {
            throw new \InvalidArgumentException('模块不存在');
        }
        return $this->getConnection()->transaction(function () use ($command) {
            $time = time();
            // 插入配置数据
            $id = $this->getConnection()->table('language_config')
                ->insertGetId([
                    'entry_name' => $command->getEntryName(),
                    'entry_code' => $command->getEntryCode(),
                    'module_id' => $command->getModuleId(),
                    'description' => $command->getDescription(),
                    'created_at' => $time,
                    'updated_at' => $time,
                ]);
            if ($command->getTranslations()) {
                // 批量插入翻译数据
                $translations = Collection::make($command->getTranslations())->map(function (Translation $translation) use ($command, $time) {
                    return [
                        'entry_code' => $command->getEntryCode(),
                        'locale' => $translation->getLocale(),
                        'translation' => $translation->getTranslation(),
                        'created_at' => $time,
                        'updated_at' => $time,
                    ];
                });
                $this->getConnection()->table('language_translation')
                    ->insert($translations->toArray());
            }
            return $id;
        });
    }

    /**
     * 更新翻译配置.
     */
    public function updateTransConfig(UpdateTransConfigCommand $command): void
    {
        // 判断词条是否已存在
        if (! $config = $this->getConnection()->table('language_config')->where('id', $command->getId())->first()) {
            throw new \InvalidArgumentException('词条不存在');
        }
        // 判断模块是否存在
        if (! $this->getConnection()->table('language_module')->where('id', $command->getModuleId())->exists()) {
            throw new \InvalidArgumentException('模块不存在');
        }
        // 判断词条编码是否存在
        if ($this->getConnection()->table('language_config')->where('entry_code', $command->getEntryCode())->where('id', '!=', $command->getId())->exists()) {
            throw new \InvalidArgumentException('词条编码已存在');
        }
        $this->getConnection()->transaction(function () use ($command, $config) {
            $time = time();
            $this->getConnection()
                ->table('language_config')
                ->where('id', $command->getId())
                ->update([
                    'entry_name' => $command->getEntryName(),
                    'module_id' => $command->getModuleId(),
                    'entry_code' => $command->getEntryCode(),
                    'description' => $command->getDescription(),
                    'updated_at' => $time,
                ]);
            foreach ($command->getTranslations() as $translation) {
                $this->getConnection()->table('language_translation')
                    ->updateOrInsert([
                        'entry_code' => $config->entry_code,
                        'locale' => $translation->getLocale(),
                    ], [
                        'entry_code' => $command->getEntryCode(),
                        'translation' => $translation->getTranslation(),
                        'created_at' => $time,
                        'updated_at' => $time,
                    ]);
            }
        });
    }

    /**
     * 删除翻译配置.
     */
    public function delConfig(int $id): void
    {
        // 判断词条是否已存在
        if (! $config = $this->getConnection()->table('language_config')->where('id', $id)->first()) {
            throw new \InvalidArgumentException('词条不存在');
        }
        $this->getConnection()->transaction(function () use ($id, $config) {
            // 删除配置
            $this->getConnection()->table('language_config')->where('id', $id)->delete();
            // 删除翻译
            $this->getConnection()->table('language_translation')->where('entry_code', $config->entry_code)->delete();
        });
    }

    /**
     * 获取配置列表.
     */
    public function getConfigs(array $queryParams): array
    {
        $query = $this->getConnection()
            ->table('language_config')
            ->orderBy('id', 'desc');
        if (! empty($queryParams['entry_name'])) {
            $query->where('entry_name', 'like', "%{$queryParams['entry_name']}%");
        }
        if (! empty($queryParams['entry_code'])) {
            $query->where('entry_code', $queryParams['entry_code']);
        }
        if (! empty($queryParams['module_id'])) {
            $query->whereIn('module_id', $this->getSubModuleIds((int) $queryParams['module_id']));
        }

        $translations = Collection::make();
        if (! empty($queryParams['translation'])) {
            $translations = $this->getConnection()->table('language_translation')
                ->select(['entry_code', 'translation'])
                ->where('translation', 'like', "%{$queryParams['translation']}%")
                ->limit(200)
                ->get();
            $translations->isNotEmpty() && $query->whereIn('entry_code', $translations->pluck('entry_code')->toArray());
        }

        $list = $query->forPage((int) ($queryParams['page'] ?? 1), (int) ($queryParams['page_size'] ?? 10))->get();
        if ($list->isEmpty()) {
            return [];
        }
        if (empty($queryParams['translation'])) {
            // 查找翻译
            $translations = $this->getConnection()->table('language_translation')
                ->select(['entry_code', 'translation'])
                ->where('locale', $this->config->getLocale())
                ->whereIn('entry_code', $list->pluck('entry_code')->toArray())
                ->get();
        }
        return $list->map(function ($item) use ($translations) {
            $item = (array) $item;
            $item['translation'] = $translations->where('entry_code', $item['entry_code'])->first()->translation ?? '';
            return $item;
        })->toArray();
    }

    /**
     * 获取配置详情.
     */
    public function getConfigInfo(int $id): array
    {
        $config = $this->getConnection()
            ->table('language_config')
            ->where('id', $id)
            ->first();
        if (! $config) {
            return [];
        }
        // 查找翻译
        $translations = $this->getConnection()->table('language_translation')
            ->where('entry_code', $config->entry_code)
            ->get();
        $config = (array) $config;
        $config['translations'] = $translations->map(function ($item) {
            return (array) $item;
        })->toArray();
        return $config;
    }

    /**
     * 获取翻译.
     */
    public function translate(string $key, string $locale): string
    {
        return $this->getConnection()
            ->table('language_translation')
            ->where('entry_code', $key)
            ->where('locale', $locale)
            ->value('translation') ?: '';
    }

    /**
     * 根据模块id列表获取所有翻译.
     */
    public function getTranslationsByModuleIds(array $moduleIds, array $locales = []): array
    {
        $query = $this->getConnection()
            ->table('language_config')
            ->leftJoin('language_translation', 'language_config.entry_code', '=', 'language_translation.entry_code')
            ->select(['language_translation.entry_code', 'language_translation.locale', 'language_translation.translation'])
            ->whereIn('language_config.module_id', $moduleIds);
        if ($locales) {
            $query->whereIn('language_translation.locale', $locales);
        }
        return $query->get()->map(function ($item) {
            return (array) $item;
        })->toArray();
    }

    protected function getConnection(): ConnectionInterface
    {
        return Db::connection($this->config->getDbConnection());
    }
}
