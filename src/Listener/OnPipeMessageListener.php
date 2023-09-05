<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage\Listener;

use CodeMagpie\HyperfLanguagePackage\Contract\TransConfigInterface;
use CodeMagpie\HyperfLanguagePackage\PipeMessage;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\OnPipeMessage;
use Hyperf\Process\Event\PipeMessage as UserProcessPipeMessage;

class OnPipeMessageListener implements ListenerInterface
{
    protected TransConfigInterface $transConfig;

    public function __construct(TransConfigInterface $transConfig)
    {
        $this->transConfig = $transConfig;
    }

    public function listen(): array
    {
        return [
            OnPipeMessage::class,
            UserProcessPipeMessage::class,
        ];
    }

    public function process(object $event)
    {
        if ($event instanceof OnPipeMessage || $event instanceof UserProcessPipeMessage) {
            $event->data instanceof PipeMessage && $this->onPipeMessage($event->data);
        }
    }

    protected function onPipeMessage(PipeMessage $message): void
    {
        foreach ($message->getTransConfig() as $item) {
            $this->transConfig->set($item['module_id'], $item['entry_code'], $item['locale'], $item['translation']);
        }
    }
}
