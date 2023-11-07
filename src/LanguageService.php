<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage;

use CodeMagpie\HyperfLanguagePackage\DTO\Command\BatchUpdateOrCreateTransConfigCommand;
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
     * 获取所有模块.
     */
    public function getAllModules(): array
    {
        return $this->getConnection()
            ->table('language_module')
            ->get()
            ->map(function ($item) {
                return (array) $item;
            })->toArray();
    }

    /**
     * 批量更或者创建模块.
     */
    public function batchUpdateOrCreateModules(array $modules): void
    {
        if (! $modules) {
            return;
        }
        $tablePrefix = $this->getConnection()->table('language_module')->getGrammar()->getTablePrefix();
        // 批量更新配置
        $time = time();
        $insertModules = [];
        foreach ($modules as $module) {
            $module['created_at'] = $module['updated_at'] = $time;
            $insertModules[] = "({$module['id']},'{$module['name']}', {$module['parent_id']}, '{$module['description']}', {$module['created_at']}, {$module['updated_at']})";
        }
        $insertModulesStr = implode(',', $insertModules);
        $table = $tablePrefix . 'language_module';
        $this->getConnection()->insert("insert into {$table} (id,`name`,parent_id, description, created_at, updated_at) values {$insertModulesStr} ON DUPLICATE KEY UPDATE `name` = values(`name`), parent_id = values(parent_id),  description = values(description),  updated_at = values(updated_at)");
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

    /**
     * @param array|int $moduleId
     */
    public function getSubModuleIds($moduleId, bool $includeSelf = true): array
    {
        $subModuleIds = [];
        $selfIds = $parentIds = is_array($moduleId) ? $moduleId : [$moduleId];
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
            array_unshift($subModuleIds, ...$selfIds);
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
     * 批量添加或者更新翻译配置.
     */
    public function batchUpdateOrCreateTransConfigs(BatchUpdateOrCreateTransConfigCommand $command)
    {
        $time = time();
        $configs = $transList = [];
        foreach ($command->configs as $config) {
            $configs[] = [
                'module_id' => $config->module_id,
                'entry_code' => $config->entry_code,
                'entry_name' => $config->entry_name,
                'description' => $config->description,
                'created_at' => $time,
                'updated_at' => $time,
            ];
            $transList[] = Collection::make($config->translations)->map(function (Translation $trans) use ($config, $time) {
                return [
                    'entry_code' => $config->entry_code,
                    'locale' => $trans->getLocale(),
                    'translation' => str_replace('\'', '\\\'', $trans->getTranslation()),
                    'created_at' => $time,
                    'updated_at' => $time,
                ];
            })->toArray();
        }
        if (! $configs) {
            return;
        }
        $transList = array_merge(...$transList);
        // 批量更新
        $this->getConnection()->transaction(function () use ($configs, $transList) {
            $tablePrefix = $this->getConnection()->table('language_config')->getGrammar()->getTablePrefix();
            // 批量更新配置
            $insertConfig = [];
            foreach ($configs as $config) {
                $insertConfig[] = "({$config['module_id']},'{$config['entry_code']}', '{$config['entry_name']}', '{$config['description']}', {$config['created_at']}, {$config['updated_at']})";
            }
            $insertConfigStr = implode(',', $insertConfig);
            $table = $tablePrefix . 'language_config';
            $this->getConnection()->insert("insert into {$table} (module_id,entry_code,entry_name, description, created_at, updated_at) values {$insertConfigStr} ON DUPLICATE KEY UPDATE module_id = values(module_id), entry_name = values(entry_name),  description = values(description),  updated_at = values(updated_at)");
            // 批量更新翻译
            $insertTrans = [];
            foreach ($transList as $trans) {
                $insertTrans[] = "('{$trans['entry_code']}','{$trans['locale']}', '{$trans['translation']}', {$trans['created_at']}, {$trans['updated_at']})";
            }
            $insertTransStr = implode(',', $insertTrans);
            $table = $tablePrefix . 'language_translation';
            $this->getConnection()->insert("insert into {$table} (entry_code, locale, `translation`, created_at, updated_at) values {$insertTransStr} ON DUPLICATE KEY UPDATE  `translation` = values(`translation`),  updated_at = values(updated_at)");
        });
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
            $query->whereIn('entry_code', $translations->pluck('entry_code')->toArray());
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
    public function getConfigInfo(array $queryParams): array
    {
        $query = $this->getConnection()
            ->table('language_config');
        if (! empty($queryParams['entry_code'])) {
            $query->where('entry_code', $queryParams['entry_code']);
        }
        if (! empty($queryParams['id'])) {
            $query->where('id', $queryParams['id']);
        }
        $config = $query->first();
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
    public function getTranslationsByModuleIds(array $moduleIds, array $locales = [], array $queryParams = []): array
    {
        $query = $this->getConnection()
            ->table('language_config')
            ->leftJoin('language_translation', 'language_config.entry_code', '=', 'language_translation.entry_code')
            ->select(['language_config.id', 'language_config.module_id', 'language_translation.entry_code', 'language_translation.locale', 'language_translation.translation'])
            ->whereIn('language_config.module_id', $moduleIds);
        if ($locales) {
            $query->whereIn('language_translation.locale', $locales);
        }
        if (! empty($queryParams['updated_at_start'])) {
            $query->where('language_config.updated_at', '>=', $queryParams['updated_at_start']);
        }
        if (! empty($queryParams['page']) && ! empty($queryParams['page_size'])) {
            $query->forPage((int) $queryParams['page'], (int) $queryParams['page_size']);
        }
        return $query->get()->map(function ($item) {
            if (! $item->entry_code) {
                return null;
            }
            return (array) $item;
        })->filter()->values()->toArray();
    }

    /**
     * 根据词条编码列表获取翻译.
     */
    public function getTranslations(array $entryCodes, string $locale = ''): array
    {
        if (! $entryCodes) {
            return [];
        }
        $query = $this->getConnection()
            ->table('language_translation')
            ->whereIn('entry_code', $entryCodes);
        if ($locale) {
            $query->where('locale', $locale);
        }
        return $query->get()
            ->map(function ($item) {
                return (array) $item;
            })->toArray();
    }

    public function getTransInfo(string $entryCode, string $locale): array
    {
        $info = $this->getConnection()
            ->table('language_config')
            ->leftJoin('language_translation', 'language_config.entry_code', '=', 'language_translation.entry_code')
            ->select(['language_config.module_id', 'language_translation.entry_code', 'language_translation.locale', 'language_translation.translation'])
            ->where('language_config.entry_code', '=', $entryCode)
            ->where('language_translation.locale', '=', $locale)
            ->first();
        return $info ? (array) $info : [];
    }

    public function getConnection(): ConnectionInterface
    {
        return Db::connection($this->config->getDbConnection());
    }
}
