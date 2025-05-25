include .env
export

DOCKER_PHP=docker compose exec php

.PHONY: help
help: ## Affiche cette aide
	@awk -F ':.*?## ' '/^[a-zA-Z0-9_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

export-bdd: ## Export base de données
	docker compose exec database mysqldump -u${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} --no-tablespaces > ~/Downloads/comptes.sql

import-bdd: ## Import base de données
	@echo "⚠️ écrasement de la base de données dans 20 secondes… 🛑 CTRL+C pour annuler"
	@sleep 20
	docker compose exec database mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} -e "DROP DATABASE IF EXISTS ${MYSQL_DATABASE}; CREATE DATABASE ${MYSQL_DATABASE};"
	docker compose exec -T database mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} < ~/Downloads/comptes.sql

migration-generate: ## Génère le squelette d'un nouvelle migration de base de données
	$(DOCKER_PHP) vendor/bin/doctrine-migrations generate

migration-migrate: ## Exécute les migrations qui n'ont pas été jouées sur la base de données
	$(DOCKER_PHP) vendor/bin/doctrine-migrations migrate
