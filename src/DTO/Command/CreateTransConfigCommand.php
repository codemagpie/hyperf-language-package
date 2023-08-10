<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace Douyu\HyperfLanguagePackage\DTO\Command;

use Douyu\HyperfLanguagePackage\DTO\AbstractDTO;
use Douyu\HyperfLanguagePackage\DTO\Meta\Translation;

class CreateTransConfigCommand extends AbstractDTO
{
    protected string $entry_name;

    protected string $entry_code;

    protected int $module_id;

    protected string $description = '';

    /**
     * @var Translation[]
     */
    protected array $translations;

    public function validateRules(): array
    {
        return [
            'entry_name' => 'required|string|max:100',
            'entry_code' => 'required|string|max:100',
            'module_id' => 'required|integer',
            'description' => 'string',
            'translations' => 'array',
        ];
    }

    public function getEntryName(): string
    {
        return $this->entry_name;
    }

    public function getEntryCode(): string
    {
        return $this->entry_code;
    }

    public function getModuleId(): int
    {
        return $this->module_id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return Translation[]
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }
}
