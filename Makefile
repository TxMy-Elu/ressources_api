COMPOSE_DEV       = docker compose
COMPOSE_MONITORING = docker compose -f docker-compose.yaml -f docker-compose.override.yaml -f docker-compose.monitoring.yaml

# ── Dev ──────────────────────────────────────────────────────────────────────
up:
	$(COMPOSE_DEV) up -d

down:
	$(COMPOSE_DEV) down

# ── Dev + Monitoring ─────────────────────────────────────────────────────────
up-monitoring:
	$(COMPOSE_MONITORING) up -d

down-monitoring:
	$(COMPOSE_MONITORING) down

# ── Logs ─────────────────────────────────────────────────────────────────────
logs:
	$(COMPOSE_DEV) logs -f

logs-nginx:
	docker logs -f resources_nginx

# ── PHP ──────────────────────────────────────────────────────────────────────
bash:
	docker exec -it resources_php bash

console:
	docker exec -it resources_php php bin/console $(cmd)

# ── BDD ──────────────────────────────────────────────────────────────────────
migrate:
	docker exec -it resources_php php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	docker exec -it resources_php php bin/console doctrine:fixtures:load --no-interaction

.PHONY: up down up-monitoring down-monitoring logs logs-nginx bash console migrate fixtures
