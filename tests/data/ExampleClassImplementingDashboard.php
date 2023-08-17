<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests\data;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use MarcinJozwikowski\EasyAdminPrettyUrls\Controller\PrettyDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class ExampleClassImplementingDashboard extends PrettyDashboardController implements DashboardControllerInterface
{
    public function configureDashboard(): Dashboard
    {
        // @phpstan-ignore-line
    }

    public function configureAssets(): Assets
    {
        // @phpstan-ignore-line
    }

    public function configureMenuItems(): iterable
    {
        // @phpstan-ignore-line
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        // @phpstan-ignore-line
    }

    public function configureActions(): Actions
    {
        // @phpstan-ignore-line
    }

    public function configureFilters(): Filters
    {
        // @phpstan-ignore-line
    }

    public function index(): Response
    {
        // @phpstan-ignore-line
    }
}
