#!/bin/sh
set -e

if [ "${APP_ENV}" != "prod" ]; then
  echo ""
  echo "ERROR: APP_ENV deve ser exatamente \"prod\" (valor atual: \"${APP_ENV:-<vazio>}\")."
  echo ""
  echo "No Railway → serviço maylove-api → Variables:"
  echo "  APP_ENV = prod"
  echo ""
  echo "Valores como \"production\" ou variável vazia fazem o Symfony usar modo dev"
  echo "e tentar ler /app/.env, que não existe na imagem Docker."
  echo ""
  exit 1
fi

if [ -z "${APP_SECRET}" ]; then
  echo ""
  echo "ERROR: APP_SECRET não está definida."
  echo ""
  echo "No Railway → serviço maylove-api → Variables:"
  echo "  APP_SECRET = <string aleatória longa>"
  echo ""
  exit 1
fi

if [ -z "${DATABASE_URL}" ]; then
  echo ""
  echo "ERROR: DATABASE_URL não está definida."
  echo ""
  echo "No Railway, antes do deploy da API:"
  echo "  1. Adicione o plugin Database → MySQL no mesmo environment."
  echo "  2. No serviço maylove-api → Variables → Add Reference."
  echo "  3. Selecione o MySQL → MYSQL_PRIVATE_URL (ou MYSQL_URL)."
  echo "  4. Nome da variável: DATABASE_URL"
  echo ""
  echo "Se a URL não tiver query string, acrescente:"
  echo "  ?serverVersion=8.0&charset=utf8mb4"
  echo ""
  exit 1
fi

php bin/console doctrine:migrations:migrate --no-interaction
