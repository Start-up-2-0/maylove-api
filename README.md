# maylove-api

API principal do MayLove (Symfony 7 + MySQL 8).

## Stack

- Symfony 7.2
- MySQL 8 (Doctrine ORM)
- Arquitetura DDD pragmática (`Domain`, `Application`, `Infrastructure`, `UI/Http`)

## Autenticação

**MayLove Token** via header `x-maylove-token` — port do sistema **Glow Up Connect API** (`GlowTokenService`).  
**Não usar JWT convencional.**

Spec: [specs/02-autenticacao/10-sistema-maylove-token.md](../specs/02-autenticacao/10-sistema-maylove-token.md)

## Desenvolvimento

```bash
# 1. Copie as variáveis de ambiente
cp .env.example .env

# 2. Com Docker (recomendado — raiz do monorepo)
docker compose up --build
docker compose exec maylove-api php bin/console doctrine:migrations:migrate --no-interaction

# 3. Local (requer MySQL + maylove-storages em .env.local)
composer install
php -S 0.0.0.0:8080 -t public public/index.php
```

> O arquivo `.env` não é versionado. Use `.env.example` como base.

Health: `GET /api/v1/health`
Auth: `POST /api/v1/auth/register`, `POST /api/v1/auth/login`, `GET /api/v1/auth/me`

## E-mail (Resend)

Port do Glow Up Connect (`ProvedorMensagemEmail` + fila assíncrona):

```env
MAIL_ENABLED=true
MAIL_FROM="MayLove <noreply@maylove.com.br>"
MAILER_DSN=resend+api://re_sua_api_key@default
APP_URL=http://localhost:5173
AUTH_EMAIL_VERIFICATION_HOURS=24
```

Em **dev**, `MAIL_ENABLED=false` e `MAILER_DSN=null://null` desabilitam envio real.  
Mensagens do Messenger rodam de forma **síncrona** (sem worker separado).

## Documentação

**[specs/README.md](../specs/README.md)**
