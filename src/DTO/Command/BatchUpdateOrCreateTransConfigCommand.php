<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage\DTO\Command;

use CodeMagpie\HyperfLanguagePackage\DTO\AbstractDTO;
use CodeMagpie\HyperfLanguagePackage\DTO\Meta\Config;

class BatchUpdateOrCreateTransConfigCommand extends AbstractDTO
{
    /**
     * @var Config[]
     */
    public array $configs;

    public function validateRules(): array
    {
        return [
            'configs' => 'required|array',
        ];
    }
}
