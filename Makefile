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
