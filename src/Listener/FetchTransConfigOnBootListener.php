<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage\Listener;

use Hyperf\Command\Event\BeforeHandle;
use Hyperf\Framework\Event\BeforeWorkerStart;
use Hyperf\Process\Event\BeforeProcessHandle;
use Hyperf\Server\Event\MainCoroutineServerStart;

class FetchTransConfigOnBootListener extends ApplicationBootListener
{
    public function listen(): array
    {
        return [
            BeforeWorkerStart::class,
            BeforeProcessHandle::class,
            BeforeHandle::class,
            MainCoroutineServerStart::class,
        ];
    }

    public function process(object $event): void
    {
        if ($this->transConfig->hasCollection()) {
            return;
        }
        $this->syncTransConfig();
    }
}
