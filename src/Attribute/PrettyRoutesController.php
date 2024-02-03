<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Attribute;

use Attribute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

#[Attribute]
class PrettyRoutesController
{
    public const ARGUMENT_ACTIONS = 'actions';
    public const ARGUMENT_CUSTOM_ACTIONS = 'customActions';
    public const ARGUMENT_PATH = 'path';
    public const DEFAULT_ACTIONS = [
        Action::INDEX,
        Action::NEW,
        Action::DETAIL,
        Action::EDIT,
        Action::DELETE,
        Action::BATCH_DELETE,
        'renderFilters',
    ];

    /**
     * @param string[]|null $actions
     */
    public function __construct(
        public array $actions = self::DEFAULT_ACTIONS,
        public ?array $customActions = null,
        public ?string $path = null,
    ) {
    }
}
