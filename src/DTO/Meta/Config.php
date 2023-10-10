<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage\DTO\Meta;

use CodeMagpie\HyperfLanguagePackage\DTO\AbstractDTO;

class Config extends AbstractDTO
{
    public int $module_id;

    public string $entry_code;

    public string $entry_name;

    public string $description = '';

    /**
     * @var Translation[]
     */
    public array $translations;

    public function validateRules(): array
    {
        return [
            'module_id' => 'required|integer',
            'entry_code' => 'required|string',
            'entry_name' => 'required|string',
            'description' => 'string',
        ];
    }
}
