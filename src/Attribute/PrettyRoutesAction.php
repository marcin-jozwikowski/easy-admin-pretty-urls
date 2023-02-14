<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Attribute;

use Attribute;

#[Attribute]
class PrettyRoutesAction
{
    public const ARGUMENT_PATH = 'path';

    public function __construct(
        public ?string $path = null,
    ) {
    }
}
