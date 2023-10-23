<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use MarcinJozwikowski\EasyAdminPrettyUrls\Tests\data\ExampleClassImplementingDashboard;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Controller\PrettyDashboardController
 */
class PrettyDashboardControllerTest extends TestCase
{
    public function testConfigureCrud(): void
    {
        $controller = new ExampleClassImplementingDashboard();

        self::assertEquals(
            [
                'crud/field/association' => '@EasyAdminPrettyUrls/crud/field/association.html.twig',
                'layout' => '@EasyAdminPrettyUrls/layout.html.twig',
            ],
            $controller->configureCrud()->getAsDto()->getOverriddenTemplates(),
        );
    }
}
