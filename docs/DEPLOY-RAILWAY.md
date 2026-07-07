# Deploy no Railway — maylove-api

## Branches e ambientes

| Branch Git | Ambiente Railway | Workflow |
|------------|------------------|----------|
| `staging` | staging | `.github/workflows/deploy-staging.yml` |
| `main` | production | `.github/workflows/deploy-production.yml` |

## Secrets GitHub

| Secret | Descrição |
|--------|-----------|
| `RAILWAY_TOKEN_STAGING` | Project token do environment **staging** |
| `RAILWAY_TOKEN_PRODUCTION` | Project token do environment **production** |

## Variables GitHub (worker Messenger)

| Variable | Descrição |
|----------|-----------|
| `RAILWAY_WORKER_SERVICE_ID_STAGING` | Nome ou ID do serviço worker em staging |
| `RAILWAY_WORKER_SERVICE_ID_PRODUCTION` | Nome ou ID do serviço worker em production |

Se não configuradas, só o serviço web é deployado.

## Serviços Railway (por environment)

### 1. Web (maylove-api)

- Repositório: este repo
- `Dockerfile` + `railway.toml`
- Migrations: `doctrine:migrations:migrate` no pre-deploy
- Health: `GET /api/v1/health`

### 2. Worker (maylove-api-worker)

- **Mesmo repositório**, segundo serviço no Railway
- Start command: `/usr/local/bin/start-worker.sh`
- Mesmas variáveis do web (`DATABASE_URL`, `REDIS_URL`, `MESSENGER_TRANSPORT_DSN`, storages, etc.)
- Sem healthcheck HTTP

### 3. Plugins

- **MySQL 8** → `DATABASE_URL`
- **Redis 7** → `REDIS_URL` e `MESSENGER_TRANSPORT_DSN=redis://.../messages`

## Variáveis críticas

| Variável | Notas |
|----------|-------|
| `APP_ENV` | `prod` |
| `APP_SECRET` | gerado no Railway |
| `APP_URL` | URL pública do frontend |
| `DATABASE_URL` | MySQL plugin |
| `REDIS_URL` | Redis plugin |
| `MESSENGER_TRANSPORT_DSN` | Redis async |
| `STORAGE_API_URL` | URL interna do maylove-storages |
| `STORAGE_SERVICE_TOKEN_SECRET` | igual ao `SERVICE_TOKEN_SECRET` do storages |
| `AUTH_TOKEN_SALT` | ≥ 32 caracteres |
| `MERCADOPAGO_ACCESS_TOKEN` | sandbox (staging) / prod |
| `MAILER_DSN` | Resend |
| `CORS_ALLOW_ORIGIN` | regex do domínio do app |

## Fluxo Git

```
feature/* → PR → staging → PR → main
```
