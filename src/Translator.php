<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage;

use Hyperf\Contract\TranslatorInterface;
use Hyperf\Utils\Codec\Json;
use Hyperf\Utils\Context;

class Translator implements TranslatorInterface
{
    protected LanguageService $languageService;

    protected Config $config;

    public function __construct(LanguageService $languageService, Config $config)
    {
        $this->languageService = $languageService;
        $this->config = $config;
    }

    public function trans(string $key, array $replace = [], ?string $locale = null)
    {
        $trans = $this->languageService->translate($key, $locale ?: $this->getLocale());
        if (! $trans && ! is_null($this->config->getFallbackLocale())) {
            $trans = $this->languageService->translate($key, $this->config->getFallbackLocale());
        }
        if (! $trans) {
            return $key;
        }
        foreach ($replace as $k => $value) {
            $trans = str_replace(str_replace('fill', $k, $this->config->getReplaceSymbol()), $value, $trans);
        }
        if ($this->isJson($trans)) {
            return Json::decode($trans);
        }
        return $trans;
    }

    public function transChoice(string $key, $number, array $replace = [], ?string $locale = null): string
    {
        throw new \BadMethodCallException('not support');
    }

    public function getLocale(): string
    {
        $locale = Context::get($this->getLocaleContextKey());

        return (string) ($locale ?? $this->config->getLocale());
    }

    public function setLocale(string $locale): void
    {
        Context::set($this->getLocaleContextKey(), $locale);
    }

    public function getLocaleContextKey(): string
    {
        return sprintf('%s::%s', TranslatorInterface::class, 'locale');
    }

    protected function isJson(string $string): bool
    {
        if (! $string) {
            return false;
        }
        try {
            Json::decode($string);
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }
}
