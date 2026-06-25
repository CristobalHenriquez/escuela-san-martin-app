#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${E2E_BASE_URL:-http://localhost:8080}"
DB_HOST="${E2E_DB_HOST:-127.0.0.1}"
DB_PORT="${E2E_DB_PORT:-3307}"
DB_NAME="${E2E_DB_NAME:-kiosco}"
DB_USER="${E2E_DB_USER:-kiosco}"
DB_PASS="${E2E_DB_PASS:-kiosco}"
LOGIN_EMAIL="${E2E_LOGIN_EMAIL:-admin@escuela.com}"
LOGIN_PASSWORD="${E2E_LOGIN_PASSWORD:-admin123}"

TMP_DIR="$(mktemp -d)"
COOKIE_JAR="$TMP_DIR/cookies.txt"
HDR_FILE="$TMP_DIR/headers.txt"
trap 'rm -rf "$TMP_DIR"' EXIT

FAILURES=0
PASSES=0
USE_DOCKER_MYSQL=0

pass() {
  PASSES=$((PASSES + 1))
  echo "PASS: $1"
}

fail() {
  FAILURES=$((FAILURES + 1))
  echo "FAIL: $1"
}

mysql_query() {
  if [[ "$USE_DOCKER_MYSQL" -eq 1 ]]; then
    docker exec kiosco_mysql mysql -N -B -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$1"
  else
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -N -B "$DB_NAME" -e "$1"
  fi
}

wait_for_mysql() {
  local attempts=60
  local delay=2
  local i
  for i in $(seq 1 "$attempts"); do
    if mysql_query "SELECT 1;" >/dev/null 2>&1; then
      return 0
    fi
    sleep "$delay"
  done
  return 1
}

http_code() {
  local url="$1"
  curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "$url"
}

echo "== E2E Smoke Test =="
echo "BASE_URL=$BASE_URL"
echo "DB=${DB_NAME}@${DB_HOST}:${DB_PORT}"

if ! command -v mysql >/dev/null 2>&1; then
  echo "ERROR: mysql client no disponible."
  exit 2
fi

if ! command -v php >/dev/null 2>&1; then
  echo "ERROR: php CLI no disponible."
  exit 2
fi

if ! command -v curl >/dev/null 2>&1; then
  echo "ERROR: curl no disponible."
  exit 2
fi

if command -v docker >/dev/null 2>&1; then
  if docker ps --format '{{.Names}}' | grep -qx 'kiosco_mysql'; then
    USE_DOCKER_MYSQL=1
    echo "MySQL mode: docker container (kiosco_mysql)"
  fi
fi

echo "Esperando disponibilidad de MySQL..."
if ! wait_for_mysql; then
  echo "ERROR: MySQL no quedó disponible a tiempo."
  exit 1
fi

ADMIN_HASH="$(php -r "echo password_hash('${LOGIN_PASSWORD}', PASSWORD_DEFAULT);")"

echo "Preparando baseline de datos..."
mysql_query "DELETE FROM transacciones;"
mysql_query "DELETE FROM capital_liquido;"
mysql_query "DELETE FROM compras_mercaderia;"
mysql_query "DELETE FROM balance_diario;"
mysql_query "DELETE FROM turnos;"
mysql_query "DELETE FROM semanas_operativas;"
mysql_query "ALTER TABLE transacciones AUTO_INCREMENT = 1;"
mysql_query "ALTER TABLE capital_liquido AUTO_INCREMENT = 1;"
mysql_query "ALTER TABLE compras_mercaderia AUTO_INCREMENT = 1;"
mysql_query "ALTER TABLE balance_diario AUTO_INCREMENT = 1;"
mysql_query "ALTER TABLE turnos AUTO_INCREMENT = 1;"
mysql_query "ALTER TABLE semanas_operativas AUTO_INCREMENT = 1;"
mysql_query "UPDATE productos SET stock = 0;"
mysql_query "INSERT INTO grupos (id, nombre, activo) VALUES (1, 'Grupo 1', 1) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), activo = VALUES(activo);"
mysql_query "INSERT INTO usuarios (nombre, apellido, email, password, telefono, grupo_id, es_admin, super_admin, activo) VALUES ('Admin', 'Smoke', '${LOGIN_EMAIL}', '${ADMIN_HASH}', '', 1, 1, 1, 1) ON DUPLICATE KEY UPDATE password = VALUES(password), grupo_id = 1, es_admin = 1, super_admin = 1, activo = 1;"

PRODUCT_ID="$(mysql_query "SELECT id FROM productos ORDER BY id ASC LIMIT 1;")"
if [[ -z "${PRODUCT_ID}" ]]; then
  mysql_query "INSERT INTO productos (ref, nombre, precio, costo, stock) VALUES ('SMOKE01', 'Producto Smoke', 1000, 600, 0);"
  PRODUCT_ID="$(mysql_query "SELECT id FROM productos ORDER BY id DESC LIMIT 1;")"
fi
mysql_query "UPDATE productos SET precio = 1000, costo = 600, stock = 10 WHERE id = ${PRODUCT_ID};"
pass "Baseline preparado (producto id=${PRODUCT_ID})"

echo "1) Login"
curl -s -D "$HDR_FILE" -o /dev/null \
  -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
  -X POST \
  --data-urlencode "email=${LOGIN_EMAIL}" \
  --data-urlencode "password=${LOGIN_PASSWORD}" \
  "${BASE_URL}/login.php"

if grep -qi '^location: index.php' "$HDR_FILE"; then
  pass "Login exitoso"
else
  fail "Login fallido (no redirige a index.php)"
fi

echo "2) Abrir turno"
curl -s -o /dev/null \
  -b "$COOKIE_JAR" \
  -X POST \
  --data "abrir_turno=1&grupo_id=1&saldo_inicial=0" \
  "${BASE_URL}/index.php"

OPEN_TURNOS="$(mysql_query "SELECT COUNT(*) FROM turnos WHERE fecha_cierre IS NULL;")"
if [[ "${OPEN_TURNOS}" == "1" ]]; then
  pass "Turno abierto correctamente"
else
  fail "No se abrió turno (turnos abiertos=${OPEN_TURNOS})"
fi

echo "3) Registrar venta"
curl -s -o /dev/null \
  -b "$COOKIE_JAR" \
  -X POST \
  --data-urlencode "registrar=1" \
  --data-urlencode "tipo=Venta" \
  --data-urlencode "metodo_pago=Efectivo" \
  --data-urlencode "productos[${PRODUCT_ID}]=1" \
  "${BASE_URL}/index.php"

VENTAS_COUNT="$(mysql_query "SELECT COUNT(*) FROM transacciones WHERE tipo='Venta';")"
STOCK_POST_VENTA="$(mysql_query "SELECT stock FROM productos WHERE id = ${PRODUCT_ID};")"
if [[ "${VENTAS_COUNT}" -ge 1 && "${STOCK_POST_VENTA}" == "9" ]]; then
  pass "Venta registrada y stock descontado"
else
  fail "Venta/stock incorrecto (ventas=${VENTAS_COUNT}, stock=${STOCK_POST_VENTA})"
fi

echo "4) Cerrar turno"
curl -s -o /dev/null \
  -b "$COOKIE_JAR" \
  -X POST \
  --data "cerrar_turno=1&saldo_cierre=0" \
  "${BASE_URL}/index.php"

OPEN_TURNOS_POST="$(mysql_query "SELECT COUNT(*) FROM turnos WHERE fecha_cierre IS NULL;")"
BALANCE_COUNT="$(mysql_query "SELECT COUNT(*) FROM balance_diario;")"
if [[ "${OPEN_TURNOS_POST}" == "0" ]]; then
  pass "Turno cerrado correctamente"
else
  fail "Quedaron turnos abiertos (${OPEN_TURNOS_POST})"
fi

if [[ "${BALANCE_COUNT}" -ge 1 ]]; then
  pass "Balance generado al cierre"
else
  fail "No se generó balance diario"
fi

echo "5) Verificar pantallas clave (HTTP 200)"
for endpoint in index.php stock.php capital.php reportes.php balance_completo.php usuarios.php; do
  code="$(http_code "${BASE_URL}/${endpoint}")"
  if [[ "${code}" == "200" ]]; then
    pass "GET ${endpoint} -> 200"
  else
    fail "GET ${endpoint} -> ${code}"
  fi
done

echo "== Resumen =="
echo "PASSES=${PASSES}"
echo "FAILURES=${FAILURES}"

if [[ "${FAILURES}" -gt 0 ]]; then
  exit 1
fi

exit 0
