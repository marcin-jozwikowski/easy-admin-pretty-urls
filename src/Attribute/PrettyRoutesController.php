<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Attribute;

use Attribute;

#[Attribute]
class PrettyRoutesController
{
    public const ARGUMENT_ACTIONS = 'actions';

    /**
     * @param string[]|null $actions
     */
    public function __construct(
        public ?array $actions = null,
    ) {
    }
}
