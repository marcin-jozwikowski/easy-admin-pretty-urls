<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Exception;

use Exception;

class RouteAlreadyExists extends Exception
{
    public function __construct(string $routeName)
    {
        parent::__construct(
            message: sprintf('Route %s already exists', $routeName),
        );
    }
}
