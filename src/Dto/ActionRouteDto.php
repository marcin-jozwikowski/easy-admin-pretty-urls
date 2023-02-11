<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Dto;

use Symfony\Component\Routing\Route;

class ActionRouteDto
{
    public function __construct(
        private string $name,
        private Route $route,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }
}
