.PHONY: help up down restart logs ps build clean test migrate fresh lint lint-fix

name ?= local-dev

setup: ## Full project bootstrap: up, deps, env, key, migrate, token
	docker compose up -d --build
	docker compose exec app bash -lc 'composer install --no-interaction --prefer-dist'
	docker compose exec app bash -lc 'test -f .env || { [ -f .env.example ] && cp .env.example .env || printf "APP_NAME=Aiston\nAPP_ENV=local\nAPP_KEY=\nAPP_DEBUG=true\nAPP_URL=http://localhost:8080\nLOG_CHANNEL=stack\n\n" > .env; }'
	docker compose exec app php artisan key:generate --force
	docker compose exec app php artisan migrate --force
	$(MAKE) token-create name="$(name)"
	@echo "Base URL: http://localhost:8080"
	$(MAKE) ps

# Default target
help: ## Show this help message
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Docker commands
up: ## Start all containers
	docker compose up -d

down: ## Stop all containers
	docker compose down

restart: ## Restart all containers
	docker compose restart

logs: ## Show logs from all containers
	docker compose logs -f

logs-app: ## Show logs from app container
	docker compose logs -f app

logs-queue: ## Show logs from queue worker
	docker compose logs -f queue-worker

logs-scheduler: ## Show logs from scheduler
	docker compose logs -f scheduler

logs-nginx: ## Show logs from nginx
	docker compose logs -f nginx

logs-postgres: ## Show logs from postgres
	docker compose logs -f postgres

ps: ## Show running containers
	docker compose ps

# Build commands
build: ## Build all containers
	docker compose build

build-app: ## Build app container only
	docker compose build app

# Database commands
migrate: ## Run database migrations
	docker compose exec app php artisan migrate

migrate-fresh: ## Fresh database migration with seeding
	docker compose exec app php artisan migrate:fresh --seed

# Queue commands
queue-work: ## Start queue worker manually
	docker compose exec app php artisan queue:work

queue-failed: ## Show failed jobs
	docker compose exec app php artisan queue:failed

queue-flush: ## Clear all failed jobs
	docker compose exec app php artisan queue:flush

queue-monitor: ## Monitor queue status
	docker compose exec app php artisan queue:monitor default

# Testing commands
test: ## Run tests
	docker compose exec app php artisan test

test-feature: ## Run feature tests
	docker compose exec app php artisan test --testsuite=Feature

test-unit: ## Run unit tests
	docker compose exec app php artisan test --testsuite=Unit

# Maintenance commands
clean: ## Clean up containers and volumes
	docker compose down -v --remove-orphans
	docker system prune -f

cache-clear: ## Clear application cache
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear

# Individual container management
restart-app: ## Restart app container only
	docker compose restart app

restart-queue: ## Restart queue worker only
	docker compose restart queue-worker

restart-scheduler: ## Restart scheduler only
	docker compose restart scheduler

restart-nginx: ## Restart nginx only
	docker compose restart nginx

restart-postgres: ## Restart postgres only
	docker compose restart postgres

# Shell access
shell-app: ## Access app container shell
	docker compose exec app bash

shell-queue: ## Access queue worker shell
	docker compose exec queue-worker bash

shell-postgres: ## Access postgres container shell
	docker compose exec postgres psql -U aiston -d aiston

# Development helpers
tinker: ## Run Laravel tinker
	docker compose exec app php artisan tinker

artisan: ## Run artisan command (usage: make artisan cmd="migrate")
	docker compose exec app php artisan $(cmd)

# Linting
lint: ## Run PHP linter (Laravel Pint) in check mode
	docker compose exec app ./vendor/bin/pint --test

lint-fix: ## Fix PHP code style (Laravel Pint)
	docker compose exec app ./vendor/bin/pint

# Token management commands
token-create: ## Create new API token (usage: make token-create name="my-token")
	@echo "Creating API token: $(name)"
	@TOKEN=$$(docker compose exec -T app php artisan token:issue --name=$(name) | tr -d '\r'); \
	echo "Token created: $$TOKEN"; \
	if [ -f .env ]; then \
		if grep -q "^AUTH_TOKEN=" .env; then \
			sed -i '' "s/^AUTH_TOKEN=.*/AUTH_TOKEN=$$TOKEN/" .env; \
			echo "Updated AUTH_TOKEN in .env"; \
		else \
			echo "AUTH_TOKEN=$$TOKEN" >> .env; \
			echo "Added AUTH_TOKEN to .env"; \
		fi; \
	else \
		echo "AUTH_TOKEN=$$TOKEN" > .env; \
		echo "Created .env with AUTH_TOKEN"; \
	fi

token-list: ## List all API tokens
	docker compose exec app php artisan token:list

token-revoke: ## Revoke API token (usage: make token-revoke id="1")
	docker compose exec app php artisan token:revoke --id=$(id)

token-test: ## Test API token (usage: make token-test token="your-token-here")
	@echo "Testing token: $(token)"
	@curl -H "Authorization: Bearer $(token)" -H "Accept: application/json" http://localhost:8080/api/tasks || echo "Token test failed"

token-get: ## Get current token from .env
	@if [ -f .env ] && grep -q "AUTH_TOKEN=" .env; then \
		TOKEN=$$(grep "AUTH_TOKEN=" .env | cut -d'=' -f2); \
		echo "Current AUTH_TOKEN: $$TOKEN"; \
	else \
		echo "No AUTH_TOKEN found in .env"; \
		exit 1; \
	fi

token-test-env: ## Test current token from .env
	@if [ -f .env ] && grep -q "AUTH_TOKEN=" .env; then \
		TOKEN=$$(grep "AUTH_TOKEN=" .env | cut -d'=' -f2); \
		echo "Testing token from .env: $$TOKEN"; \
		curl -X POST -H "Authorization: Bearer $$TOKEN" -H "Accept: application/json" -H "Content-Type: application/json" -d '{"audio_url":"https://example.com/test.mp3","metadata":{}}' http://localhost:8080/api/tasks || echo "Token test failed"; \
	else \
		echo "No AUTH_TOKEN found in .env"; \
		exit 1; \
	fi

# Task management commands
task-create: ## Create a new task (usage: make task-create url="https://example.com/audio.mp3")
	@if [ -f .env ] && grep -q "AUTH_TOKEN=" .env; then \
		TOKEN=$$(grep "AUTH_TOKEN=" .env | cut -d'=' -f2); \
		echo "Creating task with audio URL: $(url)"; \
		RESPONSE=$$(curl -s -X POST -H "Authorization: Bearer $$TOKEN" -H "Accept: application/json" -H "Content-Type: application/json" -d '{"audio_url":"$(url)","metadata":{}}' http://localhost:8080/api/tasks); \
		echo "Response: $$RESPONSE"; \
		TASK_ID=$$(echo "$$RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2); \
		if [ ! -z "$$TASK_ID" ]; then \
			echo "Task created with ID: $$TASK_ID"; \
			echo "$$TASK_ID" > /tmp/last_task_id.txt; \
		else \
			echo "Failed to create task"; \
			exit 1; \
		fi; \
	else \
		echo "No AUTH_TOKEN found in .env. Run 'make token-create name=\"your-token\"' first"; \
		exit 1; \
	fi

task-get: ## Get task by ID (usage: make task-get id="1" or make task-get to get last created task)
	@if [ -f .env ] && grep -q "AUTH_TOKEN=" .env; then \
		TOKEN=$$(grep "AUTH_TOKEN=" .env | cut -d'=' -f2); \
		if [ -z "$(id)" ]; then \
			if [ -f /tmp/last_task_id.txt ]; then \
				TASK_ID=$$(cat /tmp/last_task_id.txt); \
				echo "Getting last created task (ID: $$TASK_ID)"; \
			else \
				echo "No task ID provided and no last task found. Usage: make task-get id=\"1\""; \
				exit 1; \
			fi; \
		else \
			TASK_ID=$(id); \
			echo "Getting task ID: $$TASK_ID"; \
		fi; \
		curl -s -H "Authorization: Bearer $$TOKEN" -H "Accept: application/json" http://localhost:8080/api/tasks/$$TASK_ID | jq '.' || curl -s -H "Authorization: Bearer $$TOKEN" -H "Accept: application/json" http://localhost:8080/api/tasks/$$TASK_ID; \
	else \
		echo "No AUTH_TOKEN found in .env. Run 'make token-create name=\"your-token\"' first"; \
		exit 1; \
	fi

task-create-and-wait: ## Create task and wait for completion (usage: make task-create-and-wait url="https://example.com/audio.mp3")
	@if [ -f .env ] && grep -q "AUTH_TOKEN=" .env; then \
		TOKEN=$$(grep "AUTH_TOKEN=" .env | cut -d'=' -f2); \
		echo "Creating task with audio URL: $(url)"; \
		RESPONSE=$$(curl -s -X POST -H "Authorization: Bearer $$TOKEN" -H "Accept: application/json" -H "Content-Type: application/json" -d '{"audio_url":"$(url)","metadata":{}}' http://localhost:8080/api/tasks); \
		echo "Response: $$RESPONSE"; \
		TASK_ID=$$(echo "$$RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2); \
		if [ ! -z "$$TASK_ID" ]; then \
			echo "Task created with ID: $$TASK_ID"; \
			echo "Waiting for task completion..."; \
			while true; do \
				STATUS=$$(curl -s -H "Authorization: Bearer $$TOKEN" -H "Accept: application/json" http://localhost:8080/api/tasks/$$TASK_ID | grep -o '"status":"[^"]*"' | cut -d'"' -f4); \
				echo "Current status: $$STATUS"; \
				if [ "$$STATUS" = "completed" ] || [ "$$STATUS" = "failed" ] || [ "$$STATUS" = "error" ]; then \
					echo "Task finished with status: $$STATUS"; \
					echo "Final result:"; \
					curl -s -H "Authorization: Bearer $$TOKEN" -H "Accept: application/json" http://localhost:8080/api/tasks/$$TASK_ID | jq '.' || curl -s -H "Authorization: Bearer $$TOKEN" -H "Accept: application/json" http://localhost:8080/api/tasks/$$TASK_ID; \
					break; \
				fi; \
				sleep 5; \
			done; \
		else \
			echo "Failed to create task"; \
			exit 1; \
		fi; \
	else \
		echo "No AUTH_TOKEN found in .env. Run 'make token-create name=\"your-token\"' first"; \
		exit 1; \
	fi
