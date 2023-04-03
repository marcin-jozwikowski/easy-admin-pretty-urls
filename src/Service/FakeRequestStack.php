<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Service;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use MarcinJozwikowski\EasyAdminPrettyUrls\Routing\PrettyUrlsGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

class FakeRequestStack extends RequestStack
{
    public function __construct(
        private RequestStack $requestStack,
        private bool $prettyUrlsIncludeMenuIndex,
    ) {
    }

    public function getCurrentRequest(): ?Request
    {
        $result = clone $this->requestStack->getCurrentRequest();
        if ($result->get(EA::CONTEXT_REQUEST_ATTRIBUTE)) {
            /** @var AdminContext $contextRequest */
            $contextRequest = $result->get(EA::CONTEXT_REQUEST_ATTRIBUTE);
            $query = clone $contextRequest->getRequest()->query;
            $query->remove(PrettyUrlsGenerator::EA_FQCN);
            $query->remove(PrettyUrlsGenerator::EA_ACTION);
            if ($this->prettyUrlsIncludeMenuIndex) {
                $query->remove(PrettyUrlsGenerator::EA_MENU_INDEX);
                $query->remove(PrettyUrlsGenerator::EA_SUBMENU_INDEX);
            }
            $contextRequest->getRequest()->query = $query;
        }

        return $result;
    }
}
