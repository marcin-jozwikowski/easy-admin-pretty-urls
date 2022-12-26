<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls;

use MarcinJozwikowski\EasyAdminPrettyUrls\DependencyInjection\EasyAdminPrettyUrlsExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EasyAdminPrettyUrlsBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new EasyadminPrettyUrlsExtension();
    }
}
