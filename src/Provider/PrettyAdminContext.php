<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PrettyAdminContext extends RequestStack
{
    public function __construct(
        private RequestStack $requestStack,
        private bool $prettyUrlsIncludeMenuIndex,
    ) {
    }

    public function getCurrentRequest(): ?Request
    {
        if ($this->requestStack->getCurrentRequest()->get(EA::CONTEXT_REQUEST_ATTRIBUTE)) {
            $result = clone $this->requestStack->getCurrentRequest();
            $result->attributes = clone $result->attributes;
            /** @var AdminContext $adminContext */
            $adminContext = $result->get(EA::CONTEXT_REQUEST_ATTRIBUTE);
            $contextRequest = clone $adminContext->getRequest();

            $query = clone $contextRequest->query;
            $query->remove(PrettyUrlsGenerator::EA_FQCN);
            $query->remove(PrettyUrlsGenerator::EA_ACTION);
            if ($this->prettyUrlsIncludeMenuIndex) {
                $query->remove(PrettyUrlsGenerator::EA_MENU_INDEX);
                $query->remove(PrettyUrlsGenerator::EA_SUBMENU_INDEX);
            }
            $contextRequest->query = $query;

            return $result;
        }

        return null;
    }
}
