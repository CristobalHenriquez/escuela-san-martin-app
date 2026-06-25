# POS Kiosco Escuela

Sistema POS escolar en PHP + MySQL.

## Objetivo del setup actual

- Una sola base de datos en todos los ambientes: **MySQL**.
- Entorno local reproducible con **Docker Compose**.
- Misma lógica de conexión para local y producción desde `db.php`.
- Turno vinculado al capital disponible en efectivo.
- Soporte para 4 grupos operativos y 4 cuentas de transferencia.
- Panel de Super Admin para asignar semanas operativas por grupo.
- Comprobantes en egresos/compras (imagen convertida a WebP o PDF).

## Requisitos

- Docker + Docker Compose

## Levantar el proyecto local

1. Clonar el repositorio.
2. (Opcional) copiar variables de entorno:

```bash
cp .env.example .env
```

3. Levantar contenedores:

```bash
docker compose up -d --build
```

4. Abrir:

- App: [http://localhost:8080/login.php](http://localhost:8080/login.php)
- MySQL local: `localhost:3307`

## Credenciales iniciales (seed local)

- Email: `admin@escuela.com`
- Contraseña: `admin123`

Cambiar contraseña luego del primer ingreso.

## Configuración de base de datos

`db.php` usa estas variables:

- `APP_ENV`
- `APP_TIMEZONE`
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `DB_TIMEZONE`

En Docker se inyectan desde `docker-compose.yml`.
En producción puedes definirlas en variables de entorno del hosting o en archivo `.env`.

## Esquema y datos iniciales

- Esquema: `docker/mysql/init/01_schema.sql`
- Seed: `docker/mysql/init/02_seed.sql`

## Scripts útiles

- Instalación manual en MySQL existente:
  - `instalar_hostinger.php` (usa los SQL anteriores y `db.php`)
- Migración desde SQLite legado:
  - `migrar_a_mysql.php`

## CI/CD (GitHub Actions)

El workflow vive en `.github/workflows/ci.yml` y ejecuta:

1. `php-lint`
2. `docker-build`
3. `e2e-smoke` (flujo básico completo)
4. `deploy-production` solo en `push` a `main` (si todo lo anterior pasa)

### Secretos requeridos para deploy

Configurar estos secretos en GitHub (`Settings > Secrets and variables > Actions`):

- `DEPLOY_HOST`: IP o dominio SSH del hosting
- `DEPLOY_PORT`: puerto SSH (ejemplo Hostinger: `65002`)
- `DEPLOY_USER`: usuario SSH
- `DEPLOY_SSH_KEY`: clave privada (formato OpenSSH, recomendado)
- `DEPLOY_PASSWORD`: contraseña SSH (alternativa si no usas clave)
- `DEPLOY_PATH`: ruta remota del proyecto (ejemplo: `/home/USER/domains/DOMINIO/public_html/Kiosco/`)

El deploy usa `rsync` y excluye `.env`, `uploads/`, `backups/` y carpetas de desarrollo para no pisar configuración ni comprobantes.

## Operación diaria

- Login: `login.php`
- POS principal: `index.php`
- Semanas operativas (Super Admin): `semanas.php`
- Stock: `stock.php`
- Reportes: `reportes.php`
- Capital: `capital.php`
- Balance completo: `balance_completo.php` (modos diario, semanal e histórico)

### Flujo recomendado de caja

1. Registrar ingresos/ajustes de capital en `capital.php`.
2. Super Admin asigna semana (lunes-domingo) y grupo desde `semanas.php`.
3. Abrir turno en `index.php`:
   - Usuarios normales solo pueden abrir turno para el grupo asignado en la semana vigente.
   - El saldo inicial no puede superar el capital disponible en efectivo.
   - Si el usuario tiene grupo asignado desde `usuarios.php`, debe coincidir con el grupo de la semana.
4. En ventas por transferencia, elegir la cuenta de Mercado Pago usada.
5. En compras/egresos, adjuntar comprobante (imagen o PDF).
6. Cerrar turno y revisar reportes/capital.

## Apagar entorno local

```bash
docker compose down
```

Para borrar también el volumen de MySQL:

```bash
docker compose down -v
```
