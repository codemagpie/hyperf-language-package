<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage;

use CodeMagpie\HyperfLanguagePackage\Contract\TransConfigInterface;
use Hyperf\Context\Context;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Utils\Codec\Json;
use Hyperf\Utils\Coroutine;

class Translator implements TranslatorInterface
{
    public static int $updateAt;

    public static bool $refreshing = false;

    protected TransConfigInterface $transConfig;

    protected LanguageService $languageService;

    protected Config $config;

    protected StdoutLoggerInterface $logger;

    public function __construct(TransConfigInterface $transConfig, Config $config, LanguageService $languageService, StdoutLoggerInterface $logger)
    {
        $this->transConfig = $transConfig;
        $this->config = $config;
        $this->languageService = $languageService;
        $this->logger = $logger;
    }

    public function trans(string $key, array $replace = [], ?string $locale = null)
    {
        $this->refresh();
        $trans = $this->transConfig->getTrans($key, $locale ?: $this->getLocale());
        if (! $trans && ! is_null($this->config->getFallbackLocale())) {
            $trans = $this->transConfig->getTrans($key, $this->config->getFallbackLocale());
        }
        // 从数据库加载
        if (! $trans) {
            $trans = $this->lodeTransByDb($key, $locale);
        }
        if ($trans === $key) {
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

    public function refresh(bool $async = true): void
    {
        // 判断是否在刷新中
        if (self::$refreshing) {
            return;
        }
        // 判断频率内是否刷新过
        $refreshRate = $this->config->getRefreshRate();
        if (self::$updateAt + $refreshRate > time()) {
            return;
        }
        self::$refreshing = true;
        $refreshFun = function () {
            $queryParams = [
                'updated_at_start' => self::$updateAt,
            ];
            self::$updateAt = time();
            try {
                $this->logger->info('Trans Configuration refreshing');
                $moduleIds = $this->config->getModuleIds();
                $subModuleIds = $this->languageService->getSubModuleIds($moduleIds);
                $transConfigs = $this->languageService->getTranslationsByModuleIds($subModuleIds, [], $queryParams);
                if ($transConfigs) {
                    foreach ($transConfigs as $item) {
                        $this->transConfig->set(
                            $item['module_id'],
                            $item['entry_code'],
                            $item['locale'],
                            $item['translation']
                        );
                    }
                }
            } catch (\Throwable $e) {
                self::$updateAt = $queryParams['updated_at_start'];
                $this->logger->error('Trans Configuration refresh failed.' . $e->getMessage());
            } finally {
                self::$refreshing = false;
            }
        };
        if ($async) {
            Coroutine::create(static function () use ($refreshFun) {
                $refreshFun();
            });
        } else {
            $refreshFun();
        }
    }

    protected function lodeTransByDb($key, $locale)
    {
        $transInfo = $this->languageService->getTransInfo($key, $locale);
        if ($transInfo) {
            $this->transConfig->set($transInfo['module_id'], $transInfo['entry_code'], $transInfo['locale'], $transInfo['translation']);
            return $transInfo['translation'];
        }
        return $key;
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
