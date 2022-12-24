test:
	vendor/bin/php-cs-fixer fix --dry-run --diff --config=.php-cs-fixer.php src/ tests/
	vendor/bin/phpstan analyse src tests
	vendor/bin/phpunit ./tests/

fix:
	vendor/bin/php-cs-fixer fix --diff --config=.php-cs-fixer.php src/ tests/
