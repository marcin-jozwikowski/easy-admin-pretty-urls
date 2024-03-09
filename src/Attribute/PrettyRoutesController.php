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
        'autocomplete'
    ];

    /**
     * @param string[]|null $actions
     * @param string[]|null $customActions
     */
    public function __construct(
        public ?array $actions = null,
        public ?array $customActions = null,
        public ?string $path = null,
    ) {
    }
}
