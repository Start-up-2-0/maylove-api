# Deploy no Railway — maylove-api

## Branches e ambientes

| Branch Git | Ambiente Railway | Workflow |
|------------|------------------|----------|
| `staging` | staging | `.github/workflows/deploy-staging.yml` |
| `main` | production | `.github/workflows/deploy-production.yml` |

## Primeiro deploy — ordem obrigatória

O `railway.toml` roda **migrations no pre-deploy**. Sem MySQL configurado, o deploy falha.

### 1. Adicionar MySQL no environment

No Railway, no environment correto (`production` ou `staging`):

1. **+ New** → **Database** → **Add MySQL**

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
| `BILLING_ENABLED` | `false` em teste/staging; `true` quando Mercado Pago estiver ativo |
| `STORAGE_API_URL` | URL interna do maylove-storages (`http://<servico>.railway.internal:<PORT>`) |
| `STORAGE_SERVICE_TOKEN_SECRET` | igual ao `SERVICE_TOKEN_SECRET` do storages |
| `AUTH_TOKEN_SALT` | ≥ 32 caracteres |
| `APP_URL` | URL do frontend |
| `CORS_ALLOW_ORIGIN` | regex do domínio do app (ex.: `^https://maylove-app-staging\.up\.railway\.app$`) |

### 4. Só então fazer deploy

Push em `staging`/`main` ou redeploy manual. Migrations pendentes rodam em dois momentos:

1. **Pre-deploy** (`preDeployCommand` no `railway.toml`)
2. **Start** (`start-web.sh`, em background após o PHP bindar na `PORT`)

O servidor sobe **antes** das migrations no start para o Railway healthcheck não receber 502.

Healthcheck do Railway: `GET /api/v1/health/live` (liveness, sempre 200).  
Monitoramento completo: `GET /api/v1/health` (pode retornar 503 se DB/storage falharem).

### Porta no Railway

O app escuta em `0.0.0.0:$PORT` (variável injetada pelo Railway). Em **Settings → Networking**, o target port do domínio deve ser a mesma `PORT` (geralmente automático). Ver [Application Failed to Respond](https://docs.railway.com/networking/troubleshooting/application-failed-to-respond).

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

## Serviços Railway (por environment)

### Web (maylove-api)

- `Dockerfile` + `railway.toml`
- Health: `GET /api/v1/health`
- Messenger roda de forma **síncrona** no próprio processo web (sem worker Redis).

## Referências Railway (copiar no dashboard)

Substitua `MySQL` pelo nome exato do seu plugin:

```
DATABASE_URL=${{MySQL.MYSQL_PRIVATE_URL}}
```

Se `MYSQL_PRIVATE_URL` não existir no seu plugin, use `MYSQL_URL`.

## Fluxo Git

```
feature/* → PR → staging → PR → main
```
