<?php

namespace App\Services\Automation;

class NodeDefinition
{
    public function __construct(
        public readonly string $component,
        public readonly string $type,
        public readonly string $name,
        public readonly string $description,
        public readonly string $category,
        public readonly string $icon,
        public readonly string $color,
        public readonly array $handles = [],
        public readonly array $config = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'component' => $this->component,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'icon' => $this->icon,
            'color' => $this->color,
            'handles' => $this->handles,
            'config' => $this->config,
        ];
    }
}