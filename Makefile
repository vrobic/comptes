DOCKER_PHP=docker compose exec php

.PHONY: help
help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: up
up: ## Démarre la stack
	docker network create web || true
	docker compose up -d
	$(DOCKER_PHP) composer install
	sudo chown -R $$USER:$$USER .
	chmod -R a+w app/cache app/logs

.PHONY: install
install: ## Installe la base de données
	$(DOCKER_PHP) app/console doctrine:database:create
	$(DOCKER_PHP) app/console doctrine:schema:create
	make fixtures

.PHONY: reinstall
reinstall: ## Réinstalle la base de données
	@echo "⚠️ réinstallation dans 20 secondes… 🛑 CTRL+C pour annuler"
	@sleep 20
	$(DOCKER_PHP) app/console doctrine:database:drop --force
	make install

.PHONY: fixtures
fixtures: ## Charge les fixtures
	$(DOCKER_PHP) app/console doctrine:fixtures:load --no-interaction

.PHONY: stan
stan: ## Exécute PHPStan
	$(DOCKER_PHP) bin/phpstan analyse -l 7 src