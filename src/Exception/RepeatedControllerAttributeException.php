<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Exception;

use RuntimeException;

class RepeatedControllerAttributeException extends RuntimeException
{
    public function __construct(string $className)
    {
        $message = sprintf('More than one PrettyRoutesController attribute was found in %s', $className);
        parent::__construct($message);
    }
}
