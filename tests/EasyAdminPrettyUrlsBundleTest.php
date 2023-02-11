<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use MarcinJozwikowski\EasyAdminPrettyUrls\DependencyInjection\EasyAdminPrettyUrlsExtension;
use MarcinJozwikowski\EasyAdminPrettyUrls\EasyAdminPrettyUrlsBundle;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\EasyAdminPrettyUrlsBundle
 */
class EasyAdminPrettyUrlsBundleTest extends TestCase
{
    public function testGetContainerExtension(): void
    {
        $bundle = new EasyAdminPrettyUrlsBundle();
        $extension = $bundle->getContainerExtension();

        self::assertNotNull($extension);
        self::assertInstanceOf(EasyAdminPrettyUrlsExtension::class, $extension);
    }
}
