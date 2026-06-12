# 🔑 GitHub Actions — Secrets requeridos

Configura estos secretos en:
`GitHub → tu repo → Settings → Secrets and variables → Actions`

---

## Secretos del workflow CD (deploy)

| Secret            | Descripción                                         | Ejemplo              |
|-------------------|-----------------------------------------------------|----------------------|
| `SSH_PRIVATE_KEY` | Clave privada SSH para conectar al servidor         | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `SSH_HOST`        | IP o dominio del servidor de producción             | `198.51.100.42`      |
| `SSH_USER`        | Usuario SSH en el servidor                          | `deploy`             |
| `SSH_PORT`        | Puerto SSH (opcional, default 22)                   | `22`                 |
| `DEPLOY_PATH`     | Ruta absoluta del proyecto en el servidor           | `/var/www/latinshop` |

> `GITHUB_TOKEN` es automático — GitHub lo genera en cada workflow, no lo configures tú.

---

## Cómo generar la clave SSH para el deploy

```bash
# 1. En tu máquina local, generar par de claves dedicado para CI/CD
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/latinshop_deploy

# 2. Copiar la clave PÚBLICA al servidor
ssh-copy-id -i ~/.ssh/latinshop_deploy.pub usuario@tu-servidor.com
# o manualmente:
cat ~/.ssh/latinshop_deploy.pub >> ~/.ssh/authorized_keys   # (en el servidor)

# 3. Copiar la clave PRIVADA → pegarla en GitHub Secret SSH_PRIVATE_KEY
cat ~/.ssh/latinshop_deploy
```

---

## Cómo crear el Environment "production" en GitHub

1. Ir a `Settings → Environments → New environment`
2. Nombre: `production`
3. Activar **"Required reviewers"** (opcional pero recomendado)
4. Los secretos de deploy van aquí (o en el nivel repo)

---

## Variables de entorno en el servidor

El servidor debe tener un archivo `/var/www/latinshop/.env` con:

```
APP_ENV=production
DB_HOST=db
DB_NAME=latinshop_db
DB_USER=latinshop
DB_PASS=tu_password
DB_ROOT_PASS=tu_root_password
REDIS_HOST=redis
REDIS_PASS=tu_redis_password
...
```

---

## Verificar que los workflows funcionan

```bash
# Ver todos los runs desde la CLI (requiere gh CLI)
gh run list

# Ver logs de un run específico
gh run view <run-id> --log

# Ejecutar CD manualmente
gh workflow run cd.yml
```
