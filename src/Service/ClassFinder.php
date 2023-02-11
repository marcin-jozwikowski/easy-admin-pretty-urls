<?php

declare(strict_types=1);

namespace MarcinJozwikowski\EasyAdminPrettyUrls\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ClassFinder
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function getClassNames(string $directory): array
    {
        $classNames = [];

        $finder = new Finder();
        $finder->files()->in($this->projectDir.DIRECTORY_SEPARATOR.$directory)->name('*.php');

        foreach ($finder as $file) {
            $className = $this->getClassNameFromFile($file);
            if (!$className) {
                continue;
            }
            $classNames[] = $className;
        }

        return $classNames;
    }

    private function getClassNameFromFile(SplFileInfo $file): ?string
    {
        $contents = file_get_contents($file->getRealPath());
        if (preg_match('/^\s*(?:abstract\s+|final\s+)?(?:class|trait)\s+(\w+)/mi', $contents, $matches)) {
            $className = $matches[1];
            $namespace = $this->getNamespaceFromContent($contents);
            if ($namespace) {
                $className = "$namespace\\$className";
            }

            return $className;
        }

        return null;
    }

    private function getNamespaceFromContent(string $contents): ?string
    {
        if (preg_match('/^\s*namespace\s+(.+?);/mi', $contents, $matches)) {
            $namespace = $matches[1];

            return trim($namespace);
        }

        return null;
    }
}
