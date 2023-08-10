<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage\DTO\Command;

use CodeMagpie\HyperfLanguagePackage\DTO\AbstractDTO;

class CreateModuleCommand extends AbstractDTO
{
    protected string $name;

    protected int $parent_id;

    protected string $description;

    protected int $created_at;

    protected int $updated_at;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->created_at = $this->updated_at = time();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentId(): int
    {
        return $this->parent_id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCreatedAt(): int
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): int
    {
        return $this->updated_at;
    }

    public function validateRules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'parent_id' => 'required|integer',
            'description' => 'string',
        ];
    }
}
