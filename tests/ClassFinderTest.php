<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Tests;

use MarcinJozwikowski\EasyAdminPrettyUrls\Service\ClassFinder;
use MarcinJozwikowski\EasyAdminPrettyUrls\Tests\data\ExampleClass;
use MarcinJozwikowski\EasyAdminPrettyUrls\Tests\data\ExampleClassImplementingDashboard;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MarcinJozwikowski\EasyAdminPrettyUrls\Service\ClassFinder
 */
class ClassFinderTest extends TestCase
{
    public function testGetClassNames(): void
    {
        $finder = new ClassFinder(__DIR__.DIRECTORY_SEPARATOR.'..');
        $names = $finder->getClassNames('tests/data');
        $names = array_flip($names);

        self::assertIsArray($names);
        self::assertCount(3, $names);
        self::assertArrayHasKey(ExampleClassImplementingDashboard::class, $names);
        self::assertArrayHasKey(ExampleClass::class, $names);
        self::assertArrayHasKey('ExampleClassWithNoNamespace', $names);
    }
}
