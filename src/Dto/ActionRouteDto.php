<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Dto;

class ActionRouteDto
{
    public function __construct(
        private string $name,
        private string $path,
        private array $defaults,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string[]
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }
}
