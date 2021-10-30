DOCKER=docker-compose exec php

.PHONY: help
help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: install
install: ## Installe la base de données
	$(DOCKER) app/console doctrine:database:create
	$(DOCKER) app/console doctrine:schema:create
	make fixtures

.PHONY: reinstall
reinstall: ## Réinstalle la base de données
	@echo "⚠️ réinstallation dans 20 secondes… 🛑 CTRL+C pour annuler"
	@sleep 20
	$(DOCKER) app/console doctrine:database:drop --force
	make install

.PHONY: fixtures
fixtures: ## Charge les fixtures
	$(DOCKER) app/console doctrine:fixtures:load --no-interaction
