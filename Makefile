test:
	PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --dry-run --diff --config=.php-cs-fixer.php src/ tests/
	PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/phpstan analyse src
	PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/phpunit ./tests/

fix:
	PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --diff --config=.php-cs-fixer.php src/ tests/

unit-coverage:
	PHP_CS_FIXER_IGNORE_ENV=1 php -dxdebug.mode=coverage vendor/bin/phpunit --coverage-html .phpunit.cache/coverage-html
