<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage;

class PipeMessage
{
    protected array $transConfigs = [];

    public function __construct(array $transConfigs)
    {
        $this->transConfigs = $transConfigs;
    }

    public function getTransConfig(): array
    {
        return $this->transConfigs;
    }
}
