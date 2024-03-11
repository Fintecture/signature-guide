init:
	# Install composer deps
	composer install

	# Install PHP CS Fixer
	mkdir --parents tools/php-cs-fixer
	composer require --dev --working-dir=tools/php-cs-fixer friendsofphp/php-cs-fixer

analyse:
	./vendor/bin/phpstan analyse

format:
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --config .php-cs-fixer.dist.php