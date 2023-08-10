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

    protected ?string $defaultValue;

    protected string $routePrefix;

    public function __construct(ConfigInterface $config)
    {
        $configArr = $config->get('douyu_language_translation') ?: [];
        $this->dbConnection = $configArr['db_connection'] ?? 'default';
        $this->locale = $configArr['locale'] ?? 'zh_CN';
        $this->fallbackLocale = $configArr['fallback_locale'] ?? null;
        $this->replaceSymbol = $configArr['replace_symbol'] ?? ':fill';
        $this->defaultValue = $configArr['default_value'] ?? null;
        $this->routePrefix = $configArr['route_prefix'] ?? '';
    }

    public function getDbConnection(): string
    {
        return $this->dbConnection ?: 'default';
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getFallbackLocale(): string
    {
        return $this->fallbackLocale;
    }

    public function getReplaceSymbol(): string
    {
        return $this->replaceSymbol;
    }

    public function getDefaultValue(): string
    {
        return $this->defaultValue;
    }

    public function getRoutePrefix(): string
    {
        return $this->routePrefix;
    }
}
