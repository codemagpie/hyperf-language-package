<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage;

use CodeMagpie\HyperfLanguagePackage\Contract\TransConfigInterface;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;

abstract class AbstractLangConstants
{
    protected TransConfigInterface $transConfig;

    protected TranslatorInterface $translator;

    public function __construct(TransConfigInterface $transConfig, TranslatorInterface $translator)
    {
        $this->transConfig = $transConfig;
        $this->translator = $translator;
    }

    abstract public function getId(): int;

    abstract public function getPrefix(): string;

    public static function getName($code, string $locale = '', array $replace = [])
    {
        $instance = self::getInstance();
        return $instance->translator->trans($instance->getPrefix() . $code, $replace, $locale);
    }

    public static function getCode($name, string $locale = '')
    {
        $list = self::getList($locale);
        return array_search($name, $list, true) ?: '';
    }

    public static function getList(string $locale = ''): array
    {
        $instance = self::getInstance();
        if (! $locale) {
            $locale = $instance->translator->getLocale();
        }
        $list = $instance->transConfig->get($instance->getId());
        $data = [];
        foreach ($list as $entryCode => $transList) {
            if (! isset($transList[$locale])) {
                continue;
            }
            if (! Str::contains($entryCode, $instance->getPrefix())) {
                continue;
            }
            $key = Str::after($entryCode, $instance->getPrefix());
            // 判断key是否是整型
            if (is_numeric($key)) {
                $key = (int) $key;
            }
            $data[$key] = $transList[$locale];
        }
        return $data;
    }

    /**
     * 获取实例.
     */
    public static function getInstance(): self
    {
        return ApplicationContext::getContainer()->get(static::class);
    }
}
