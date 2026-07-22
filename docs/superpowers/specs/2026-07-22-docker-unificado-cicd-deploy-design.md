# Diseño: Docker unificado + CI/CD con deploy automático a Hostinger

**Fecha:** 2026-07-22
**Estado:** Aprobado por el usuario, pendiente de implementación

## Contexto

El repo `escuela-san-martin-app` contiene dos aplicaciones PHP corriendo bajo el mismo hosting compartido de Hostinger (`eeso225-lasanmartin.edu.ar`, usuario SSH `u978865485`, acceso por puerto 65002):

- **Sitio raíz**: web pública + CRM/admin de la escuela EESO 225 "La San Martín" (noticias, personal docente, logros estudiantiles).
- **`Kiosco/`**: sistema POS escolar, desplegado en producción como subcarpeta (`public_html/Kiosco/`) del mismo dominio, y anidado de la misma forma dentro de este mismo repo git.

Hoy no existe ningún pipeline de CI/CD: los deploys se hacen a mano. Tampoco hay un único entorno Docker local — el compose de la raíz y el de `Kiosco/` están duplicados. La producción actual (código + base de datos) tiene datos reales de alumnos y personal, y **Kiosco tiene datos transaccionales en uso activo** (turnos, ventas, capital), por lo que cualquier automatización debe tratar la base de datos de producción como intocable y ser cuidadosa con el timing de los deploys sobre Kiosco.

El repositorio de GitHub (`CristobalHenriquez/escuela-san-martin-app`) es **privado**. Se detectó que los dumps `u978865485_web_escuela.sql` y `u978865485_kiosco2.sql`, versionados en git para sembrar el entorno Docker local, contienen emails reales de alumnos/personal y hashes de contraseñas. No es una fuga pública porque el repo es privado, pero queda registrado como limpieza pendiente fuera del alcance de este diseño.

Hostinger ya ofrece sus propios backups automáticos a nivel de hosting (capa adicional, no sustituye el backup propio del pipeline descrito abajo).

## Objetivo

1. Unificar el entorno Docker local en un solo stack (eliminando la duplicación entre el compose raíz y el de `Kiosco/`).
2. Automatizar el deploy a producción (ambas apps) vía GitHub Actions, con un paso de aprobación manual y un backup/rollback propio, sin tocar nunca la base de datos de producción.

## Fuera de alcance (explícitamente, para esta iteración)

- Migrar a un VPS o correr Docker en producción (el hosting sigue siendo compartido; producción sigue sirviendo PHP plano vía Apache del hosting).
- Migraciones de esquema de base de datos automatizadas.
- Limpieza de PII en los dumps `.sql` versionados.
- Actualización de contenido del sitio (noticias/personal/logros) — tarea de contenido, no de infraestructura.

## Diseño

### 1. Docker local unificado

- Se elimina el rol de stack independiente de `Kiosco/docker-compose.yml` y `Kiosco/Dockerfile`. Estos quedan marcados como legado (o se borran) con una nota en `Kiosco/README.md` que redirige al compose de la raíz.
- Justificación técnica: las extensiones PHP que requiere `Kiosco/Dockerfile` (pdo, pdo_mysql, mysqli, mbstring, gd, intl, zip, xml) son idénticas a las que ya instala el `Dockerfile` raíz. `Kiosco/db.php` ya lee las mismas variables de entorno (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_ENV`) que el `docker-compose.yml` raíz ya inyecta apuntando a la base `kiosco`. Es decir: Kiosco ya es funcionalmente accesible en `localhost:8081/Kiosco/` dentro del contenedor raíz; solo sobraba el segundo stack.
- Se agrega `composer` al `Dockerfile` raíz (Kiosco lo necesita para `phpoffice/phpspreadsheet` y `mpdf`, no versionados en `vendor/`) y un paso para correr `composer install` dentro de `Kiosco/` (durante el build de la imagen, o documentado como `docker compose exec app composer install -d Kiosco` para no inflar la imagen).
- Resultado para el desarrollador: un solo comando (`docker compose up -d --build` desde la raíz) levanta sitio + Kiosco + una sola MySQL con ambas bases — reflejando la topología real de producción (mismo dominio, subcarpeta).

### 2. Pipeline de CI/CD (GitHub Actions)

Un solo workflow en la raíz del repo (`.github/workflows/deploy.yml`), ya que Kiosco vive anidado dentro del mismo árbol y se despliega junto con el sitio raíz.

**Job `validate`** (en todo push y PR a `main`):

1. Checkout del repo.
2. Lint sintáctico PHP sobre todo el árbol (excluyendo `vendor/`): `php -l` por archivo.
3. `composer install --no-dev --optimize-autoloader` dentro de `Kiosco/` — valida que las dependencias instalan y genera el `vendor/` que efectivamente se va a desplegar.
4. `docker build` de la imagen raíz unificada, para detectar roturas del `Dockerfile` antes de mergear.

**Job `deploy`** (solo en push a `main`, solo si `validate` fue exitoso):

1. **Gate de aprobación manual**: el job usa un GitHub *Environment* llamado `production` con "required reviewers" configurado al usuario. El job queda pausado — mostrando el diff a desplegar — hasta que se apruebe manualmente con un clic. Este gate se puede remover más adelante cuando haya confianza en el pipeline.
2. **Backup previo al deploy** (una vez aprobado, antes de tocar nada):
   - Por SSH (clave en el secreto `DEPLOY_SSH_KEY`), se genera un `tar.gz` con marca de tiempo del estado actual del servidor (`public_html/` y `public_html/Kiosco/`, excluyendo `uploads/` ya que nunca se toca) en una carpeta fuera del docroot (ej. `~/backups/`, no accesible por web).
   - Ese `.tar.gz` se descarga al runner y se sube como *artifact* del workflow (retención ~30 días), para tener una copia también fuera del hosting.
   - Se podan backups remotos viejos, conservando los últimos 5, para no exceder la cuota de disco del hosting compartido.
   - **Backup único inicial**: antes de activar el pipeline por primera vez, se ejecuta este mismo backup a mano una vez, como "punto cero", capturando el estado real de producción (incluyendo cualquier cambio manual histórico que nunca llegó a git).
3. **Deploy** vía `rsync -avz` (sin `--delete` hasta validar consistencia; ver más abajo) desde el checkout hacia `DEPLOY_PATH` (secretos: `DEPLOY_HOST`, `DEPLOY_PORT`, `DEPLOY_USER`, `DEPLOY_PATH`), excluyendo siempre:
   - `.git/`, `.github/`
   - `uploads/` (raíz) y `Kiosco/uploads/comprobantes/`
   - `.env`, `Kiosco/.env`, `config/local.php`
   - `*.sql` sueltos y `database/`
   - `docker/`, `docker-compose.yml`, `Dockerfile`, y cualquier remanente de `Kiosco/Dockerfile`/`Kiosco/docker-compose.yml`
   - `node_modules/`, `*.log`
   - `docs/superpowers/` (specs/planes internos, no forman parte del sitio)
4. La base de datos de producción **nunca** se toca por este pipeline: no se ejecuta ningún `.sql`, no hay paso de migración. Cualquier cambio de esquema se sigue haciendo a mano y con cuidado.

**Workflow de rollback** (`rollback.yml`, disparado manualmente vía `workflow_dispatch`, nunca automático):

- Input: qué backup restaurar (por defecto, el más reciente disponible).
- Descarga el backup elegido (desde el artifact de GitHub si sigue dentro de la ventana de retención, o directamente desde `~/backups/` en el servidor si es más reciente) y lo sincroniza de vuelta por SSH, restaurando exactamente ese estado de archivos. No toca la base de datos (no hace falta: nunca fue tocada).

### 3. Autenticación SSH

- Se genera un par de claves SSH nuevo, sin passphrase, dedicado a este pipeline.
- La clave pública se agrega en el hPanel de Hostinger (Avanzado → Acceso SSH → gestión de claves).
- La clave privada se guarda como secreto de GitHub (`DEPLOY_SSH_KEY`), junto con `DEPLOY_HOST` (185.173.111.66), `DEPLOY_PORT` (65002) y `DEPLOY_USER` (u978865485) y `DEPLOY_PATH` (a confirmar la ruta exacta remota de `public_html`) como secretos/variables del repo. Nunca viaja una contraseña.

### 4. Verificación de consistencia antes del primer deploy real

Antes de habilitar el `--delete` de rsync (que borraría en el servidor archivos que no estén en el repo), se corre una vez `rsync --dry-run -avzi` comparando el checkout de git contra el estado real del servidor, para detectar archivos presentes en producción que nunca se subieron a git (esperable, dado que no hubo CI/CD hasta ahora) y decidir manualmente si incorporarlos al repo o excluirlos explícitamente antes de automatizar la sincronización destructiva.

## Orden de implementación sugerido

1. Backup manual único ("punto cero") del estado actual de producción.
2. `rsync --dry-run` de verificación de consistencia repo vs. servidor; resolver diffs encontrados.
3. Unificación del Docker local (root `Dockerfile` + `docker-compose.yml`, deprecar stack de `Kiosco/`).
4. Generación y configuración de la clave SSH dedicada + secretos de GitHub.
5. Workflow `validate` (lint + composer install + docker build) — sin deploy todavía, solo para validar en PRs.
6. Workflow `deploy` con gate de aprobación manual, backup automático y rsync (sin `--delete` al inicio).
7. Workflow `rollback.yml`.
8. Una vez con confianza acumulada: evaluar activar `--delete` en rsync y/o remover el gate de aprobación manual.
