<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage\DTO\Command;

use CodeMagpie\HyperfLanguagePackage\DTO\AbstractDTO;
use CodeMagpie\HyperfLanguagePackage\DTO\Meta\Translation;

class UpdateTransConfigCommand extends AbstractDTO
{
    protected int $id;

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
            'id' => 'required|integer',
            'entry_name' => 'required|string|max:100',
            'entry_code' => 'required|string|max:60',
            'module_id' => 'required|integer',
            'description' => 'string',
            'translations' => 'required|array',
        ];
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getTranslations(): array
    {
        return $this->translations;
    }
}
