# ============================================================
# Makefile — Latin Shop
# Comandos Docker rápidos
# ============================================================
.PHONY: help build up down restart logs shell db-shell redis-shell \
        ps clean nuke dev prod

# ─── Variables ───────────────────────────────────────────────
COMPOSE      = docker compose
APP          = latinshop_app
DB           = latinshop_db
REDIS        = latinshop_redis

# ─── Colores ─────────────────────────────────────────────────
GREEN  = \033[0;32m
YELLOW = \033[0;33m
NC     = \033[0m

help: ## Muestra esta ayuda
	@echo ""
	@echo "$(GREEN)Latin Shop — Comandos Docker$(NC)"
	@echo "─────────────────────────────────────────"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-18s$(NC) %s\n", $$1, $$2}'
	@echo ""

# ─── Desarrollo ──────────────────────────────────────────────
dev: ## Levanta el stack completo en modo desarrollo (con phpMyAdmin)
	$(COMPOSE) --profile dev up --build -d
	@echo "$(GREEN)✅ Stack levantado:$(NC)"
	@echo "   App:        http://localhost:8080"
	@echo "   phpMyAdmin: http://localhost:8081"

# ─── Producción ──────────────────────────────────────────────
prod: ## Levanta el stack en modo producción (sin phpMyAdmin)
	$(COMPOSE) up --build -d
	@echo "$(GREEN)✅ Stack producción levantado$(NC)"

# ─── Básicos ─────────────────────────────────────────────────
build: ## Reconstruye las imágenes
	$(COMPOSE) build --no-cache

up: ## Levanta contenedores existentes
	$(COMPOSE) up -d

down: ## Para y elimina contenedores
	$(COMPOSE) down

restart: ## Reinicia todos los servicios
	$(COMPOSE) restart

# ─── Logs ────────────────────────────────────────────────────
logs: ## Ver logs de todos los servicios
	$(COMPOSE) logs -f

logs-app: ## Ver logs solo de la app
	$(COMPOSE) logs -f app

logs-db: ## Ver logs solo de MySQL
	$(COMPOSE) logs -f db

# ─── Shells ──────────────────────────────────────────────────
shell: ## Shell en el contenedor de la app
	docker exec -it $(APP) bash

db-shell: ## MySQL shell
	docker exec -it $(DB) mysql -u root -p

redis-shell: ## Redis CLI
	docker exec -it $(REDIS) redis-cli

# ─── Estado ──────────────────────────────────────────────────
ps: ## Estado de los contenedores
	$(COMPOSE) ps

# ─── Limpieza ────────────────────────────────────────────────
clean: ## Para contenedores y elimina imágenes dangling
	$(COMPOSE) down
	docker image prune -f

nuke: ## ⚠️  ELIMINA TODO (contenedores, imágenes, volúmenes)
	@echo "$(YELLOW)⚠️  Esto eliminará TODOS los datos. ¿Continuar? [y/N]$(NC)"
	@read ans && [ $${ans:-N} = y ] && \
		$(COMPOSE) down --volumes --rmi all || echo "Cancelado."

# ─── DB ──────────────────────────────────────────────────────
db-dump: ## Exporta backup de la base de datos
	@mkdir -p backups
	docker exec $(DB) mysqldump -u root -p${DB_ROOT_PASS} ${DB_NAME} \
		| gzip > backups/backup_$(shell date +%Y%m%d_%H%M%S).sql.gz
	@echo "$(GREEN)✅ Backup guardado en backups/$(NC)"

db-restore: ## Restaura un backup (uso: make db-restore FILE=backups/archivo.sql.gz)
	@if [ -z "$(FILE)" ]; then echo "Uso: make db-restore FILE=ruta/backup.sql.gz"; exit 1; fi
	gunzip -c $(FILE) | docker exec -i $(DB) mysql -u root -p${DB_ROOT_PASS} ${DB_NAME}
	@echo "$(GREEN)✅ Restaurado desde $(FILE)$(NC)"
