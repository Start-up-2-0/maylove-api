#!/bin/sh
set -e

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
