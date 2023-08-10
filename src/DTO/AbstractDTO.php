<?php

declare(strict_types=1);
/**
 * This file belong to douYuTech, all rights reserved.
 * (c) DouYuTech <https://www.douyutech.cn/>
 */
namespace CodeMagpie\HyperfLanguagePackage\DTO;

use Hyperf\Utils\ApplicationContext;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidationException;

abstract class AbstractDTO
{
    public function __construct(array $attributes = [])
    {
        $validator = ApplicationContext::getContainer()->get(ValidatorFactoryInterface::class)
            ->make($attributes, $this->validateRules());
        if ($validator->fails()) {
            throw new ValidationException($validator, $validator->errors()->getMessages());
        }
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            if (! isset($attributes[$property->getName()])) {
                continue;
            }
            $type = $property->getType();
            $value = $attributes[$property->getName()];
            if ($type) {
                settype($value, $type->getName());
            }
            $this->{$property->getName()} = $value;
        }
    }

    public function toArray(): array
    {
        return array_map(function ($value) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            } elseif (is_array($value)) {
                foreach ($value as $index => $item) {
                    $value[$index] = (is_object($item) && method_exists($item, 'toArray')) ? $item->toArray() : $item;
                }
            }
            return $value;
        }, get_object_vars($this));
    }

    abstract public function validateRules(): array;
}
