setup:
	ddev start
	ddev orchestrate

start:
	ddev start

stop:
	ddev stop

restart:
	ddev restart

reset:
	ddev orchestrate -f

shell:
	ddev ssh

ngrok:
	.ddev/bin/share

test:
	ddev exec ./phpunit.sh

test-unit:
	ddev exec 'vendor/bin/phpunit tests/PHPUnit/Unit && for c in modules.local/*/phpunit.xml.dist; do (cd "$$(dirname "$$c")" && { [ -d vendor ] || composer install -q --no-progress; } && vendor/bin/phpunit) || exit 1; done'

lint:
	ddev exec phpcs --parallel=8 -s --runtime-set ignore_warnings_on_exit 1
	ddev exec phpstan analyze --memory-limit=2G

fix-lint:
	ddev exec phpcbf

install:
	ddev composer install
	ddev npm ci
	ddev npm run build

composer-update:
	ddev composer update

build:
	ddev npm run build

watch:
	ddev npm run watch

.PHONY: build dist
