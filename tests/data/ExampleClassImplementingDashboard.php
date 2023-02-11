<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests\data;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class ExampleClassImplementingDashboard implements DashboardControllerInterface
{

    public function configureDashboard(): Dashboard
    {
        // TODO: Implement configureDashboard() method.
    }

    public function configureAssets(): Assets
    {
        // TODO: Implement configureAssets() method.
    }

    public function configureMenuItems(): iterable
    {
        // TODO: Implement configureMenuItems() method.
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        // TODO: Implement configureUserMenu() method.
    }

    public function configureCrud(): Crud
    {
        // TODO: Implement configureCrud() method.
    }

    public function configureActions(): Actions
    {
        // TODO: Implement configureActions() method.
    }

    public function configureFilters(): Filters
    {
        // TODO: Implement configureFilters() method.
    }

    public function index(): Response
    {
        // TODO: Implement index() method.
    }
}
