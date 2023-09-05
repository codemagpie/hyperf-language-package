<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage;

use CodeMagpie\HyperfLanguagePackage\Contract\TransConfigInterface;
use Hyperf\Context\Context;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Utils\Codec\Json;

class Translator implements TranslatorInterface
{
    protected TransConfigInterface $transConfig;

    protected Config $config;

    public function __construct(TransConfigInterface $transConfig, Config $config)
    {
        $this->transConfig = $transConfig;
        $this->config = $config;
    }

    public function trans(string $key, array $replace = [], ?string $locale = null)
    {
        $contextKey = __METHOD__ . '::' . $key . '::' . $locale;
        if (! $trans = Context::get($contextKey)) {
            $trans = $this->transConfig->getTrans($key, $locale ?: $this->getLocale());
            if (! $trans && ! is_null($this->config->getFallbackLocale())) {
                $trans = $this->transConfig->getTrans($key, $this->config->getFallbackLocale());
            }
            if (! $trans) {
                $trans = $key;
            }
            Context::set($contextKey, $trans);
        }
        if ($key === $trans) {
            return $trans;
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
