.PHONY: setup up install migrate seed test test-unit cs-deps cs-check cs-fix cs-fix-git cs-fix-all styles down logs flush-views

setup:
	docker compose up -d --build
	docker compose exec -T app composer install --no-interaction --prefer-dist
	docker compose exec -T app php bin/migrate.php
	docker compose exec -T app php bin/seed.php

up:
	docker compose up -d --build

install:
	docker compose exec -T app composer install --no-interaction --prefer-dist

migrate:
	docker compose exec -T app php bin/migrate.php

seed:
	docker compose exec -T app php bin/seed.php

test:
	docker compose --profile test run --rm tests

test-unit:
	docker compose exec -T app composer test:unit

cs-deps:
	@if [ ! -x vendor/bin/php-cs-fixer ]; then \
		composer install --no-interaction --prefer-dist --ignore-platform-req=ext-redis; \
	fi

cs-check: cs-deps
	./vendor/bin/php-cs-fixer check --config=.php-cs-fixer.dist.php

cs-fix:
	$(MAKE) cs-fix-all

cs-fix-git: cs-deps
	@if git ls-files -- '*.php' ':!:vendor/**' ':!:var/cache/**' ':!:.phpunit.cache/**' ':!:node_modules/**' ':!:.idea/**' ':!:coverage/**' ':!:storage/logs/**' ':!:storage/smarty/**' | grep -q .; then \
		git ls-files -z -- '*.php' ':!:vendor/**' ':!:var/cache/**' ':!:.phpunit.cache/**' ':!:node_modules/**' ':!:.idea/**' ':!:coverage/**' ':!:storage/logs/**' ':!:storage/smarty/**' | \
			xargs -0 ./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --path-mode=override; \
	fi

cs-fix-all: cs-deps
	./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php

flush-views:
	docker compose exec -T app php bin/flush-views.php

styles:
	npm ci --ignore-scripts --no-audit --no-fund
	npm run build

down:
	docker compose --profile test down

logs:
	docker compose logs --tail=100 web app db redis views-worker
