# Deploy no Railway — maylove-api

## Branches e ambientes

| Branch Git | Ambiente Railway | Workflow |
|------------|------------------|----------|
| `staging` | staging | `.github/workflows/deploy-staging.yml` |
| `main` | production | `.github/workflows/deploy-production.yml` |

## Primeiro deploy — ordem obrigatória

O `railway.toml` roda **migrations no pre-deploy**. Sem MySQL configurado, o deploy falha.

### 1. Adicionar MySQL (e Redis) no environment

No Railway, no environment correto (`production` ou `staging`):

1. **+ New** → **Database** → **Add MySQL**
2. **+ New** → **Database** → **Add Redis** (necessário para Messenger e health)

Anote o nome do serviço MySQL (ex.: `MySQL`).

### 2. Ligar `DATABASE_URL` ao serviço maylove-api

1. Abra o serviço **maylove-api** (não o MySQL).
2. Aba **Variables** → **+ New Variable** → **Add Reference**.
3. Serviço: **MySQL** → variável: **`MYSQL_PRIVATE_URL`** (rede interna; prefira em produção).
4. Nome da variável no API: **`DATABASE_URL`**.

Se o Symfony reclamar de `serverVersion`, edite o valor e garanta o sufixo:

```
?serverVersion=8.0&charset=utf8mb4
```

Exemplo final:

```
mysql://root:senha@mysql.railway.internal:3306/railway?serverVersion=8.0&charset=utf8mb4
```

O nome do banco (`railway` ou `maylove`) vem do plugin; as migrations criam as tabelas no banco da URL.

### 3. Demais variáveis mínimas (mesmo serviço)

| Variável | Como preencher |
|----------|----------------|
| `APP_ENV` | exatamente `prod` (não use `production` nem deixe vazio) |
| `APP_SECRET` | gere um valor aleatório longo |
| `REDIS_URL` | referência → Redis → `REDIS_URL` ou `REDIS_PRIVATE_URL` |
| `MESSENGER_TRANSPORT_DSN` | `${REDIS_URL}/messages` — ex.: `redis://default:pass@redis.railway.internal:6379/messages` |
| `STORAGE_API_URL` | URL interna do maylove-storages (`http://<servico>.railway.internal:<PORT>`) |
| `STORAGE_SERVICE_TOKEN_SECRET` | igual ao `SERVICE_TOKEN_SECRET` do storages |
| `AUTH_TOKEN_SALT` | ≥ 32 caracteres |
| `APP_URL` | URL do frontend |
| `CORS_ALLOW_ORIGIN` | regex do domínio do app |

### 4. Só então fazer deploy

Push em `staging`/`main` ou redeploy manual. Migrations pendentes rodam em dois momentos:

1. **Pre-deploy** (`preDeployCommand` no `railway.toml`)
2. **Start** (`start-web.sh`, antes do servidor PHP)

Comando usado: `doctrine:migrations:migrate --no-interaction --allow-no-migration` (só aplica o que falta).

## Erro comum

> Set the DATABASE_URL environment variable… / pre-deploy migration failed

**Causa:** serviço API sem `DATABASE_URL`, ou MySQL não existe no environment.

**Correção:** passos 1 e 2 acima. A variável deve estar no serviço **maylove-api**, não só no plugin MySQL.

> Symfony tenta carregar `/app/.env` no pre-deploy

**Causa:** `APP_ENV` ausente, vazia ou com valor errado (ex.: `production`). O Symfony cai em modo `dev` e procura `.env`, que não está na imagem.

**Correção:** no Railway → Variables → `APP_ENV` = `prod` (literalmente).

**Nota técnica:** sem `.env` na imagem Docker, `config/bootstrap-runtime.php` desabilita o Dotenv e copia as variáveis do Railway (`/proc/self/environ`) para `$_SERVER`/`$_ENV` antes do Symfony subir.

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

### Web (maylove-api)

- `Dockerfile` + `railway.toml`
- Health: `GET /api/v1/health`

### Worker (maylove-api-worker)

- Mesmo repositório, start command: `/usr/local/bin/start-worker.sh`
- **Mesmas variáveis** do web (incluindo `DATABASE_URL` e Redis)

## Referências Railway (copiar no dashboard)

Substitua `MySQL` / `Redis` pelo nome exato dos seus plugins:

```
DATABASE_URL=${{MySQL.MYSQL_PRIVATE_URL}}
REDIS_URL=${{Redis.REDIS_PRIVATE_URL}}
MESSENGER_TRANSPORT_DSN=${{Redis.REDIS_PRIVATE_URL}}/messages
```

Se `MYSQL_PRIVATE_URL` não existir no seu plugin, use `MYSQL_URL`.

## Fluxo Git

```
feature/* → PR → staging → PR → main
```
