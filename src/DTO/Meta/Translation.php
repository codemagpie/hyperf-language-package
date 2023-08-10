<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage\DTO\Meta;

use CodeMagpie\HyperfLanguagePackage\DTO\AbstractDTO;

class Translation extends AbstractDTO
{
    protected int $id = 0;

    protected string $locale;

    protected string $translation;

    public function validateRules(): array
    {
        return [
            'id' => 'integer',
            'locale' => 'required|string|max:10',
            'translation' => 'string',
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getTranslation(): string
    {
        return $this->translation;
    }
}
