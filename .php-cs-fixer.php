<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2' => true,
        '@PSR12' => true,
        '@Symfony' => true,
        'yoda_style' => false,
        'single_line_throw' => false,
        'list_syntax' => ['syntax' => 'short'],
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'global_namespace_import' => ['import_classes' => true],
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
    ])
    ->setFinder($finder)
;
