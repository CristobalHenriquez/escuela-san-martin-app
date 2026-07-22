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

- Docker + Docker Compose (se levanta junto con el sitio principal, ver raíz del repo)

## Levantar el proyecto local

Kiosco ya no tiene un stack Docker propio: se levanta como parte del compose de la raíz del
repositorio (comparte imagen PHP, MySQL y red con el sitio principal, igual que en producción,
donde Kiosco vive como subcarpeta del mismo hosting).

Desde la raíz del repo (no desde `Kiosco/`):

```bash
docker compose up -d --build
```

- App: [http://localhost:8081/Kiosco/login.php](http://localhost:8081/Kiosco/login.php)
- MySQL local: `localhost:3308` (bases `escuela_san_martin` y `kiosco` en el mismo contenedor MySQL)

Las dependencias de Composer (`phpoffice/phpspreadsheet`, `mpdf`) se instalan automáticamente la
primera vez que arranca el contenedor `app` (ver `docker/entrypoint.sh` en la raíz).

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

El workflow vive en la raíz del repo (`.github/workflows/deploy.yml`), no acá — Kiosco se
despliega junto con el sitio principal en un solo pipeline, ya que ambos viven en el mismo
hosting (Kiosco como subcarpeta). Ver el `README.md` de la raíz para el detalle del pipeline,
los secretos configurados y el workflow de rollback (`.github/workflows/rollback.yml`).

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

Desde la raíz del repo (apaga también el sitio principal, ya que comparten stack):

```bash
docker compose down
```

Para borrar también el volumen de MySQL:

```bash
docker compose down -v
```
