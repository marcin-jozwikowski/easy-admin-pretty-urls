<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

abstract class PrettyDashboardController extends AbstractDashboardController
{
    public function configureCrud(): Crud
    {
        return parent::configureCrud()
            ->overrideTemplate('layout', '@EasyAdminPrettyUrls/layout.html.twig')
            ->overrideTemplate('crud/field/association', '@EasyAdminPrettyUrls/crud/field/association.html.twig');
    }
}
