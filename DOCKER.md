# 🐳 Latin Shop — Docker

Guía completa para levantar Latin Shop con Docker y Docker Compose.

---

## Requisitos

| Herramienta     | Versión mínima |
|-----------------|----------------|
| Docker          | 24.x           |
| Docker Compose  | v2 (`docker compose`) |
| Make (opcional) | cualquiera     |

---

## Inicio rápido

### 1. Clonar y configurar variables

```bash
# Clona el proyecto (o descomprime el ZIP)
git clone https://github.com/tu-org/latinshop.git
cd latinshop

# Copia las variables de entorno Docker
cp .env.docker .env

# Edita los valores críticos
nano .env
```

Variables **obligatorias** a cambiar en `.env`:

```
DB_PASS=tu_password_mysql_segura
DB_ROOT_PASS=tu_root_password_segura
REDIS_PASS=tu_redis_password
```

---

### 2. Levantar en desarrollo

```bash
# Con Makefile (recomendado)
make dev

# O directamente
docker compose --profile dev up --build -d
```

Accesos disponibles:

| Servicio   | URL                      |
|------------|--------------------------|
| Aplicación | http://localhost:8080    |
| phpMyAdmin | http://localhost:8081    |

---

### 3. Levantar en producción

```bash
make prod
# o
docker compose up --build -d
```

> En producción no se levanta phpMyAdmin automáticamente.

---

## Comandos útiles

```bash
# Ver estado de contenedores
make ps

# Logs en tiempo real
make logs

# Logs solo de la app
make logs-app

# Shell en el contenedor PHP
make shell

# MySQL CLI
make db-shell

# Redis CLI
make redis-shell

# Reiniciar todos los servicios
make restart

# Parar sin eliminar volúmenes
make down

# Backup de la base de datos
make db-dump
# → Guarda en: backups/backup_YYYYMMDD_HHMMSS.sql.gz

# Restaurar backup
make db-restore FILE=backups/backup_20240101_120000.sql.gz
```

---

## Estructura de volúmenes

```
latinshop_db_data      MySQL data directory
latinshop_redis_data   Redis AOF + RDB snapshots
latinshop_storage      Archivos de la app (uploads, cache, backups)
```

El volumen `latinshop_storage` se monta en `/var/www/storage` dentro del
contenedor y **persiste entre reinicios y rebuilds**.

---

## Variables de entorno

| Variable       | Descripción                    | Default          |
|----------------|--------------------------------|------------------|
| `APP_ENV`      | `production` o `development`  | `production`     |
| `APP_PORT`     | Puerto de la app en el host    | `8080`           |
| `DB_HOST`      | Host MySQL (nombre del service)| `db`             |
| `DB_NAME`      | Nombre de la base de datos     | `latinshop_db`   |
| `DB_USER`      | Usuario MySQL                  | `latinshop`      |
| `DB_PASS`      | Password MySQL                 | —                |
| `DB_ROOT_PASS` | Password root MySQL            | —                |
| `DB_PORT`      | Puerto MySQL en el host        | `3307`           |
| `REDIS_HOST`   | Host Redis                     | `redis`          |
| `REDIS_PORT`   | Puerto Redis en el host        | `6379`           |
| `REDIS_PASS`   | Password Redis                 | —                |
| `PMA_PORT`     | Puerto phpMyAdmin en el host   | `8081`           |

---

## Primera vez: migraciones

Las migraciones SQL en `database/migrations/` se ejecutan **automáticamente**
cuando el volumen MySQL está vacío (primer `docker compose up`).

Para forzar una migración manualmente:

```bash
# Copiar y ejecutar un archivo SQL
docker exec -i latinshop_db mysql \
  -u latinshop -p"$DB_PASS" latinshop_db \
  < database/migrations/001_support_tickets.sql
```

---

## Troubleshooting

### La app no conecta a MySQL

```bash
# Ver si MySQL está healthy
docker compose ps db

# Ver logs de MySQL
make logs-db

# Probar conexión manual
make db-shell
```

### OPcache no refleja cambios en desarrollo

```bash
# Reiniciar el contenedor de la app
docker compose restart app

# O desactivar OPcache en desarrollo editando opcache.ini:
# opcache.validate_timestamps = 1
# opcache.revalidate_freq = 0
```

### Permisos en logs/

```bash
make shell
# dentro del contenedor:
chown -R www-data:www-data /var/www/html/logs
```

---

## Arquitectura del stack

```
  ┌─────────────┐     ┌──────────────────┐     ┌──────────┐
  │   Browser   │────▶│  app (PHP+Apache)│────▶│  db      │
  │             │     │  Puerto 8080     │     │  MySQL   │
  └─────────────┘     │                  │     │  :3306   │
                      │                  │────▶│  redis   │
                      └──────────────────┘     │  :6379   │
                                               └──────────┘
  Todos en la red interna: latinshop_net
```
