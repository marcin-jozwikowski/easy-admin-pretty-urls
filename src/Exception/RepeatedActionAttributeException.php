<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Exception;

use RuntimeException;

class RepeatedActionAttributeException extends RuntimeException
{
    public function __construct(string $className, string $actionName)
    {
        $message = sprintf('More than one PrettyRoutesAction attribute was found in %s::%s', $className, $actionName);
        parent::__construct($message);
    }
}
