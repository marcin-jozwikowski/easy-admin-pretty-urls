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

        self::assertIsArray($names);
        self::assertCount(3, $names);
        self::assertEquals(ExampleClassImplementingDashboard::class, $names[0]);
        self::assertEquals(ExampleClass::class, $names[1]);
        self::assertEquals('ExampleClassWithNoNamespace', $names[2]);
    }
}
