.PHONY: setup up install migrate seed test test-unit styles down logs

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

styles:
	npm ci --ignore-scripts --no-audit --no-fund
	npm run build

down:
	docker compose --profile test down

logs:
	docker compose logs --tail=100 web app db
