.PHONY: help install up down test test-unit test-integration lint phpstan cs-fix cs-check rector rector-check composer-check quality fix

help: ## Показать список команд
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ── Установка ─────────────────────────────────────────

install: ## Установить зависимости
	composer install --ignore-platform-req=ext-amqp

up: ## Запустить Docker контейнеры
	docker compose up -d

down: ## Остановить Docker контейнеры
	docker compose down

# ── Тесты ─────────────────────────────────────────────

test: ## Запустить все тесты
	php bin/phpunit

test-unit: ## Запустить unit тесты
	php bin/phpunit --testsuite=Unit

test-integration: ## Запустить integration тесты
	php bin/phpunit --testsuite=Integration

# ── Качество кода ─────────────────────────────────────

phpstan: ## Статический анализ (PHPStan)
	php vendor/bin/phpstan analyse

cs-check: ## Проверить код-стиль (без изменений)
	php vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Исправить код-стиль
	php vendor/bin/php-cs-fixer fix

rector-check: ## Проверить Rector (без изменений)
	php vendor/bin/rector process --dry-run

rector: ## Применить Rector
	php vendor/bin/rector process

composer-check: ## Проверить зависимости composer
	php vendor/bin/composer-require-checker check

# ── Комбинированные ───────────────────────────────────

lint: cs-check phpstan rector-check composer-check ## Все проверки (без изменений)

fix: cs-fix rector ## Применить все автоисправления

quality: fix phpstan test ## Исправить, проверить и протестировать