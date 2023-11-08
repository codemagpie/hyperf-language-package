<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage;

use Hyperf\Contract\ConfigInterface;

class Config
{
    protected string $dbConnection;

    protected string $locale;

    protected ?string $fallbackLocale;

    protected string $replaceSymbol;

    protected string $routePrefix;

    protected array $moduleIds;

    protected int $refreshRate;

    public function __construct(ConfigInterface $config)
    {
        $configArr = $config->get('douyu_language_translation') ?: [];
        $this->dbConnection = $configArr['db_connection'] ?? 'default';
        $this->locale = $configArr['locale'] ?? 'zh_CN';
        $this->fallbackLocale = $configArr['fallback_locale'] ?? null;
        $this->replaceSymbol = $configArr['replace_symbol'] ?? ':fill';
        $this->routePrefix = $configArr['route_prefix'] ?? '';
        $this->moduleIds = $configArr['load_modules'] ?? [];
        $this->refreshRate = (int) ($configArr['refresh_rate'] ?? 60);
    }

    public function getDbConnection(): string
    {
        return $this->dbConnection ?: 'default';
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getFallbackLocale(): ?string
    {
        return $this->fallbackLocale;
    }

    public function getReplaceSymbol(): string
    {
        return $this->replaceSymbol;
    }

    public function getRoutePrefix(): string
    {
        return $this->routePrefix;
    }

    public function getModuleIds(): array
    {
        return $this->moduleIds;
    }

    public function getRefreshRate(): int
    {
        return $this->refreshRate;
    }
}
