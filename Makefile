include .env
export

DOCKER_PHP=docker compose exec php

.PHONY: help
help: ## Affiche cette aide
	@awk -F ':.*?## ' '/^[a-zA-Z0-9_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Installe le projet et démarre la stack
	docker compose build --pull --no-cache

up: ## Démarre la stack
	docker compose up --wait
	@echo "🚀 https://localhost"

down: ## Éteint la stack
	docker compose down --remove-orphans

export-bdd: ## Export base de données
	docker compose exec database mysqldump -u${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} --no-tablespaces > ~/Downloads/comptes.sql

import-bdd: ## Import base de données
	@echo "⚠️ écrasement de la base de données dans 20 secondes… 🛑 CTRL+C pour annuler"
	@sleep 20
	docker compose exec database mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} -e "DROP DATABASE IF EXISTS ${MYSQL_DATABASE}; CREATE DATABASE ${MYSQL_DATABASE};"
	docker compose exec -T database mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} < ~/Downloads/comptes.sql

migration-generate: ## Génère le squelette d'un nouvelle migration de base de données
	$(DOCKER_PHP) bin/console doctrine:migrations:generate

migration-migrate: ## Exécute les migrations qui n'ont pas été jouées sur la base de données
	$(DOCKER_PHP) bin/console doctrine:migrations:migrate

lint: ## Exécute PHPCS, PHPStan et les linters Symfony
	make cs-fix
	make stan
	make lint-symfony

lint-symfony: ## Exécute les linters Symfony (conteneur de services, Twig, YAML)
	$(DOCKER_PHP) bin/console lint:container
	$(DOCKER_PHP) bin/console lint:twig templates
	$(DOCKER_PHP) bin/console lint:yaml config --parse-tags

cs-fix: ## Exécute PHPCS
	$(DOCKER_PHP) vendor/bin/php-cs-fixer fix src --allow-risky=yes

cs-check: ## Exécute PHPCS sans modifier les fichiers
	$(DOCKER_PHP) vendor/bin/php-cs-fixer fix src --allow-risky=yes --dry-run --diff

stan: ## Exécute PHPStan
	$(DOCKER_PHP) vendor/bin/phpstan analyse --memory-limit 256M

stan-baseline: ## Régénère la baseline PHPStan
	$(DOCKER_PHP) vendor/bin/phpstan analyse --memory-limit 256M --generate-baseline phpstan-baseline.neon

test: ## Exécute PHPUnit
	$(DOCKER_PHP) bin/phpunit

audit: ## Vérifie les vulnérabilités connues des dépendances
	$(DOCKER_PHP) composer audit
