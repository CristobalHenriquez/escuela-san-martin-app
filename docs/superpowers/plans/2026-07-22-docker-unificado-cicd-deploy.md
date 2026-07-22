# Docker Unificado + CI/CD Deploy a Hostinger — Plan de Implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Unificar el entorno Docker local (un solo stack para sitio + Kiosco) y automatizar el deploy a producción vía GitHub Actions, con aprobación manual, backup previo y rollback, sin tocar nunca la base de datos real.

**Architecture:** Se agrega `composer` y un entrypoint custom a la imagen raíz para que Kiosco funcione como subcarpeta del mismo contenedor (se elimina su stack Docker independiente). El deploy usa un único workflow de GitHub Actions con dos jobs (`validate` y `deploy`, este último detrás de un *environment* `production` con revisor obligatorio) que sincroniza archivos por `rsync` sobre SSH hacia el hosting compartido de Hostinger, con una lista de exclusiones versionada y un backup remoto + artifact previo a cada deploy. Un workflow separado (`rollback.yml`) permite restaurar cualquier backup manualmente.

**Tech Stack:** Docker, Docker Compose, PHP 8.2 (Apache), Composer, GitHub Actions, `rsync`/`ssh`/`tar`, GitHub CLI (`gh`).

## Global Constraints

- La base de datos de producción (`escuela_san_martin` y `kiosco` en el hosting real) **nunca** se toca por ningún workflow: no se ejecuta ningún `.sql`, no hay pasos de migración.
- El job `deploy` requiere aprobación manual (GitHub *Environment* `production` con revisor obligatorio) hasta nueva decisión — no se remueve en este plan.
- El primer deploy real usa `rsync` **sin** `--delete` hasta que el Task 3 (verificación de consistencia) se haya completado y revisado con el usuario.
- Las exclusiones de `rsync` para el **deploy de código** (repo → servidor) viven en un único archivo versionado (`deploy/rsync-excludes.txt`), nunca duplicadas inline. El rollback (backup → servidor, ambos ya en producción) usa su propia exclusión mínima de `uploads/`, ya que no aplica el resto de la lista (`.git/`, `docker/`, etc. no existen en un backup de producción).
- Nunca se commitea ninguna clave privada, contraseña, ni el contenido de `~/.ssh/`. Todo secreto vive en GitHub Secrets.
- `Kiosco/vendor/` y `Kiosco/.env` nunca se commitean (ya cubierto por `Kiosco/.gitignore` y `.gitignore` raíz — no tocar esa regla).
- Cualquier comando que se conecte al servidor real de producción (SSH/rsync contra Hostinger) requiere que el usuario esté presente para confirmarlo — no se ejecutan como parte de un subagente desatendido.

---

### Task 1: Generar clave SSH dedicada al pipeline y habilitarla en Hostinger

**Files:**
- No se modifica el repo en esta tarea (la clave vive fuera del repo, en `~/.ssh/`).

**Interfaces:**
- Produce: un par de claves en `~/.ssh/eeso225_deploy_ed25519` (privada) y `~/.ssh/eeso225_deploy_ed25519.pub` (pública), que Task 6 va a subir como el secreto `DEPLOY_SSH_KEY`.

- [ ] **Step 1: Generar el par de claves (sin passphrase, para uso no interactivo en CI)**

```bash
ssh-keygen -t ed25519 -C "github-actions-deploy@eeso225" -f ~/.ssh/eeso225_deploy_ed25519 -N ""
```

Expected: crea `~/.ssh/eeso225_deploy_ed25519` y `~/.ssh/eeso225_deploy_ed25519.pub`, imprime el fingerprint (`SHA256:...`).

- [ ] **Step 2: Mostrar la clave pública para agregarla a Hostinger**

```bash
cat ~/.ssh/eeso225_deploy_ed25519.pub
```

Expected: una línea `ssh-ed25519 AAAA... github-actions-deploy@eeso225`.

- [ ] **Step 3: 🧑 Acción manual del usuario — agregar la clave en Hostinger**

En el hPanel de Hostinger: `Avanzado → Acceso SSH` → buscar la sección de gestión de claves SSH (si esa sección no aparece en la vista actual, contactar soporte de Hostinger para habilitar autenticación por clave — algunos planes compartidos la exponen en una pestaña separada de "Claves SSH"). Pegar el contenido completo del `.pub` del Step 2. Confirmar guardado en el panel.

- [ ] **Step 4: Verificar que la clave funciona contra el servidor real**

```bash
ssh -p 65002 -i ~/.ssh/eeso225_deploy_ed25519 -o StrictHostKeyChecking=accept-new u978865485@185.173.111.66 "echo CONEXION_OK"
```

Expected: imprime `CONEXION_OK` sin pedir contraseña.

- [ ] **Step 5: Commit** — no aplica (ningún archivo del repo cambió en esta tarea).

---

### Task 2: Backup manual "punto cero" del estado actual de producción

**Files:**
- Modify: `.gitignore` (raíz) — agregar carpeta de backups locales.

**Interfaces:**
- Consume: acceso SSH de Task 1 (`~/.ssh/eeso225_deploy_ed25519`).
- Produce: la ruta remota real de `public_html` (necesaria para Task 3, Task 6 y Task 8) y un `.tar.gz` de referencia guardado localmente en `backups-locales/`.

- [ ] **Step 1: Confirmar la ruta remota real de `public_html`**

```bash
ssh -p 65002 -i ~/.ssh/eeso225_deploy_ed25519 u978865485@185.173.111.66 "ls -la ~/domains/eeso225-lasanmartin.edu.ar/"
```

Expected: lista un directorio `public_html` (y, dentro de él si se explora, `Kiosco/`). Anotar la ruta exacta confirmada — se usa en todos los pasos siguientes como `<RUTA_REMOTA>` (por defecto se asume `domains/eeso225-lasanmartin.edu.ar/public_html`, ajustar si el listado muestra algo distinto).

- [ ] **Step 2: Crear carpeta remota de backups fuera del docroot**

```bash
ssh -p 65002 -i ~/.ssh/eeso225_deploy_ed25519 u978865485@185.173.111.66 "mkdir -p ~/backups"
```

Expected: sin salida (éxito silencioso).

- [ ] **Step 3: Generar el snapshot "punto cero" de producción**

```bash
ssh -p 65002 -i ~/.ssh/eeso225_deploy_ed25519 u978865485@185.173.111.66 \
  "tar -czf ~/backups/produccion_punto_cero_\$(date +%Y%m%d_%H%M%S).tar.gz \
   --exclude='public_html/uploads' --exclude='public_html/Kiosco/uploads/comprobantes' \
   -C ~/domains/eeso225-lasanmartin.edu.ar public_html"
```

Expected: sin salida; el comando tarda unos segundos/minutos según el tamaño del sitio.

- [ ] **Step 4: Verificar que el backup remoto existe y tiene contenido**

```bash
ssh -p 65002 -i ~/.ssh/eeso225_deploy_ed25519 u978865485@185.173.111.66 "ls -lh ~/backups/"
```

Expected: un archivo `produccion_punto_cero_YYYYMMDD_HHMMSS.tar.gz` con tamaño mayor a 0.

- [ ] **Step 5: Descargar el backup a la máquina local como respaldo fuera del hosting**

```bash
mkdir -p backups-locales
scp -P 65002 -i ~/.ssh/eeso225_deploy_ed25519 \
  "u978865485@185.173.111.66:~/backups/produccion_punto_cero_*.tar.gz" ./backups-locales/
```

Expected: el archivo aparece en `./backups-locales/`.

- [ ] **Step 6: Verificar contenido del backup descargado**

```bash
tar -tzf backups-locales/produccion_punto_cero_*.tar.gz | head -20
```

Expected: lista archivos como `public_html/index.php`, `public_html/admin/`, `public_html/Kiosco/`, etc. (confirma que el backup capturó ambas apps).

- [ ] **Step 7: Ignorar la carpeta de backups locales en git**

Editar `.gitignore` (raíz), agregar al final:

```
backups-locales/
```

- [ ] **Step 8: Commit**

```bash
git add .gitignore
git commit -m "$(cat <<'EOF'
Ignore local production backup snapshots

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 3: Verificación de consistencia repo vs. servidor (rsync dry-run)

**Files:**
- Create: `deploy/rsync-excludes.txt`

**Interfaces:**
- Consume: acceso SSH de Task 1, ruta remota confirmada en Task 2 Step 1.
- Produce: `deploy/rsync-excludes.txt`, consumido por Task 3 (este mismo dry-run), Task 8 (`deploy.yml`) y Task 9 (`rollback.yml`) — única fuente de verdad de exclusiones.

- [ ] **Step 1: Crear el archivo de exclusiones versionado**

Crear `deploy/rsync-excludes.txt`:

```
.git/
.github/
uploads/
Kiosco/uploads/comprobantes/
.env
Kiosco/.env
config/local.php
*.sql
database/
docker/
docker-compose.yml
Dockerfile
Kiosco/Dockerfile
Kiosco/docker-compose.yml
node_modules/
*.log
docs/superpowers/
backups-locales/
```

- [ ] **Step 2: Correr el dry-run comparando el repo local contra el servidor real**

```bash
rsync -avzin --delete \
  -e "ssh -p 65002 -i ~/.ssh/eeso225_deploy_ed25519" \
  --exclude-from=deploy/rsync-excludes.txt \
  ./ u978865485@185.173.111.66:~/domains/eeso225-lasanmartin.edu.ar/public_html/ \
  | tee /tmp/rsync-dry-run.txt
```

Expected: una lista de líneas con prefijos `>f` (archivos que se agregarían/actualizarían) y `*deleting` (archivos que se borrarían del servidor por no estar en git). No se modifica nada real (`-n` = dry-run).

- [ ] **Step 3: Aislar específicamente qué se borraría**

```bash
grep '^\*deleting' /tmp/rsync-dry-run.txt
```

Expected: cero o más líneas. Cada línea es un archivo/carpeta que existe en producción pero no en el repo git.

- [ ] **Step 4: 🧑 Revisión manual conjunta con el usuario**

Repasar juntos la salida del Step 3. Por cada archivo listado, decidir:
- Si es contenido legítimo que falta en git (ej. un archivo subido a mano alguna vez) → agregarlo al repo con `git add` en un commit aparte antes de continuar.
- Si es contenido que nunca debe sincronizarse (ej. cachés, backups viejos del propio hosting, algo generado en el servidor) → agregarlo a `deploy/rsync-excludes.txt`.

No avanzar a Task 8 (deploy automático) hasta que esta lista esté en cero o completamente resuelta.

- [ ] **Step 5: Commit**

```bash
git add deploy/rsync-excludes.txt
git commit -m "$(cat <<'EOF'
Add versioned rsync exclude list for production deploys

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 4: Docker unificado — composer + entrypoint para Kiosco en la imagen raíz

**Files:**
- Create: `docker/entrypoint.sh`
- Modify: `Dockerfile` (raíz)

**Interfaces:**
- Produce: el contenedor `app` instala automáticamente `Kiosco/vendor/` en el primer arranque (si falta), sin pasos manuales adicionales.

- [ ] **Step 1: Crear el entrypoint custom**

Crear `docker/entrypoint.sh`:

```bash
#!/bin/sh
set -e

if [ -f /var/www/html/Kiosco/composer.json ] && [ ! -d /var/www/html/Kiosco/vendor ]; then
    echo "==> Instalando dependencias de Kiosco (composer install)..."
    composer install --working-dir=/var/www/html/Kiosco --no-interaction --optimize-autoloader
fi

exec apache2-foreground
```

- [ ] **Step 2: Actualizar el `Dockerfile` raíz para incluir composer y el entrypoint**

En `Dockerfile` (raíz), agregar el stage de composer al inicio y las líneas de copia del entrypoint antes de `EXPOSE 80`:

```dockerfile
FROM composer:2 AS composer

FROM php:8.2-apache
```

(el resto del bloque `RUN apt-get ...` existente queda igual, sin cambios)

Y agregar, después del `WORKDIR /var/www/html` existente y antes del `HEALTHCHECK`:

```dockerfile
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
```

- [ ] **Step 3: Dar permisos de ejecución al entrypoint en el repo (para que el `chmod` del build no dependa del filesystem del host)**

```bash
chmod +x docker/entrypoint.sh
```

- [ ] **Step 4: Reconstruir la imagen y levantar el stack**

```bash
docker compose down
docker compose up -d --build
```

Expected: build exitoso, contenedores `escuela_app`, `escuela_mysql` en estado `running`/`healthy`.

- [ ] **Step 5: Verificar que composer y el vendor de Kiosco quedaron instalados automáticamente**

```bash
docker compose exec app composer --version
docker compose exec app test -d Kiosco/vendor && echo VENDOR_OK
```

Expected: primera línea imprime algo como `Composer version 2.x.x`; segunda línea imprime `VENDOR_OK`.

- [ ] **Step 6: Commit**

```bash
git add Dockerfile docker/entrypoint.sh
git commit -m "$(cat <<'EOF'
Add composer and auto-install entrypoint to root Docker image

Kiosco's phpoffice/phpspreadsheet and mpdf dependencies aren't
vendored in git; the entrypoint installs them on first boot so
`docker compose up -d --build` is enough to run both apps.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 5: Deprecar el stack Docker independiente de Kiosco y actualizar documentación

**Files:**
- Delete: `Kiosco/Dockerfile`
- Delete: `Kiosco/docker-compose.yml`
- Modify: `Kiosco/README.md:15-37` (sección "Requisitos" / "Levantar el proyecto local")
- Modify: `README.md:131-165` (sección "🐳 Opción recomendada: levantar con Docker")

**Interfaces:**
- Consume: el stack unificado de Task 4 ya funcionando en `localhost:8081`.

- [ ] **Step 1: Eliminar los archivos Docker duplicados de Kiosco**

```bash
git rm Kiosco/Dockerfile Kiosco/docker-compose.yml
```

- [ ] **Step 2: Reemplazar la sección de instalación local en `Kiosco/README.md`**

Reemplazar el bloque entre `## Requisitos` (línea 15) y el final de `## Levantar el proyecto local` (antes de línea 39 `## Credenciales iniciales`) por:

```markdown
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
```

- [ ] **Step 3: Actualizar la sección Docker de `README.md` (raíz) para mencionar a Kiosco explícitamente**

En `README.md`, dentro de la sección `### 🐳 Opción recomendada: levantar con Docker`, después de la línea `- Panel admin: http://localhost:8081/admin/login.php` (línea 145), agregar:

```markdown
- Kiosco (POS escolar): http://localhost:8081/Kiosco/login.php
```

- [ ] **Step 4: Verificar que Kiosco sigue siendo accesible tras eliminar su stack propio**

```bash
docker compose down -v
docker compose up -d --build
curl -sf -o /dev/null -w "%{http_code}\n" http://localhost:8081/Kiosco/login.php
```

Expected: imprime `200`.

- [ ] **Step 5: Commit**

```bash
git add Kiosco/README.md README.md
git commit -m "$(cat <<'EOF'
Deprecate Kiosco's standalone Docker stack in favor of root compose

Kiosco already ran correctly as a subfolder of the root container
(same env vars, same PHP extensions) — the separate stack was pure
duplication. Docs now point to the single root compose command.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 6: Configurar secretos y el Environment `production` en GitHub

**Files:**
- No se modifica el repo (configuración vía GitHub API/CLI).

**Interfaces:**
- Consume: clave privada de Task 1 (`~/.ssh/eeso225_deploy_ed25519`), ruta remota confirmada en Task 2 Step 1.
- Produce: secretos `DEPLOY_SSH_KEY`, `DEPLOY_HOST`, `DEPLOY_PORT`, `DEPLOY_USER`, `DEPLOY_PATH`, y el *environment* `production` con revisor obligatorio, consumidos por Task 8 y Task 9.

- [ ] **Step 1: Confirmar que `gh` está autenticado contra el repo correcto**

```bash
gh repo view CristobalHenriquez/escuela-san-martin-app --json nameWithOwner
```

Expected: `{"nameWithOwner":"CristobalHenriquez/escuela-san-martin-app"}`.

- [ ] **Step 2: Cargar los secretos de conexión**

```bash
gh secret set DEPLOY_SSH_KEY --repo CristobalHenriquez/escuela-san-martin-app < ~/.ssh/eeso225_deploy_ed25519
gh secret set DEPLOY_HOST --repo CristobalHenriquez/escuela-san-martin-app --body "185.173.111.66"
gh secret set DEPLOY_PORT --repo CristobalHenriquez/escuela-san-martin-app --body "65002"
gh secret set DEPLOY_USER --repo CristobalHenriquez/escuela-san-martin-app --body "u978865485"
gh secret set DEPLOY_PATH --repo CristobalHenriquez/escuela-san-martin-app --body "domains/eeso225-lasanmartin.edu.ar/public_html"
```

Expected: cada comando imprime `✓ Set Actions secret DEPLOY_... for CristobalHenriquez/escuela-san-martin-app`. Ajustar el valor de `DEPLOY_PATH` si Task 2 Step 1 confirmó una ruta distinta.

- [ ] **Step 3: Verificar que los secretos quedaron creados**

```bash
gh secret list --repo CristobalHenriquez/escuela-san-martin-app
```

Expected: lista `DEPLOY_SSH_KEY`, `DEPLOY_HOST`, `DEPLOY_PORT`, `DEPLOY_USER`, `DEPLOY_PATH`.

- [ ] **Step 4: Crear el environment `production` con revisor obligatorio**

```bash
OWNER_ID=$(gh api users/CristobalHenriquez --jq .id)
cat <<EOF > /tmp/env-production.json
{
  "reviewers": [{"type": "User", "id": $OWNER_ID}],
  "deployment_branch_policy": {
    "protected_branches": false,
    "custom_branch_policies": true
  }
}
EOF
gh api --method PUT repos/CristobalHenriquez/escuela-san-martin-app/environments/production --input /tmp/env-production.json
rm /tmp/env-production.json
```

Expected: respuesta JSON con `"name": "production"` y `"protection_rules"` incluyendo un `type: required_reviewers`.

- [ ] **Step 5: Restringir el environment a la rama `main`**

```bash
gh api --method POST repos/CristobalHenriquez/escuela-san-martin-app/environments/production/deployment-branch-policies \
  -f name=main
```

Expected: respuesta JSON con `"name": "main"`.

- [ ] **Step 6: Verificar la configuración final del environment**

```bash
gh api repos/CristobalHenriquez/escuela-san-martin-app/environments/production --jq '.protection_rules'
```

Expected: incluye un objeto con `"type": "required_reviewers"` y el usuario configurado.

- [ ] **Step 7: Commit** — no aplica (configuración vive en GitHub, no en el repo).

---

### Task 7: Workflow `validate` (lint + composer + build de imagen)

**Files:**
- Create: `.github/workflows/deploy.yml` (job `validate` únicamente en esta tarea)

**Interfaces:**
- Produce: job `validate`, consumido por Task 8 (`deploy` usa `needs: validate`).

- [ ] **Step 1: Crear el workflow con el job `validate`**

Crear `.github/workflows/deploy.yml`:

```yaml
name: Deploy

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  validate:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer

      - name: Lint PHP syntax
        run: |
          find . -path ./Kiosco/vendor -prune -o -name "*.php" -print0 \
            | xargs -0 -n1 -P4 php -l

      - name: Install Kiosco composer dependencies
        working-directory: Kiosco
        run: composer install --no-dev --optimize-autoloader

      - name: Build root Docker image
        run: docker build -t escuela-app:ci .
```

- [ ] **Step 2: Verificar el YAML localmente antes de subir**

```bash
python3 -c "import yaml; yaml.safe_load(open('.github/workflows/deploy.yml'))" && echo YAML_OK
```

Expected: `YAML_OK`.

- [ ] **Step 3: Commit y push para disparar el workflow por primera vez**

```bash
git add .github/workflows/deploy.yml
git commit -m "$(cat <<'EOF'
Add validate job: PHP lint, Kiosco composer install, Docker build

Runs on every push/PR to main as a pre-deploy safety check.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
git push
```

- [ ] **Step 4: Verificar que el workflow corrió y pasó**

```bash
gh run list --repo CristobalHenriquez/escuela-san-martin-app --workflow=deploy.yml --limit 1
```

Expected: una fila con `status: completed` y `conclusion: success` para el job `validate` (el job `deploy` todavía no existe en esta tarea).

---

### Task 8: Job `deploy` — aprobación manual + backup + rsync

**Files:**
- Modify: `.github/workflows/deploy.yml` (agregar el job `deploy`)

**Interfaces:**
- Consume: job `validate` de Task 7, secretos de Task 6, `deploy/rsync-excludes.txt` de Task 3.

- [ ] **Step 1: Agregar el job `deploy` al workflow existente**

Agregar al final de `.github/workflows/deploy.yml` (después del job `validate`, manteniendo la indentación de nivel `jobs:`):

```yaml
  deploy:
    needs: validate
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'
    runs-on: ubuntu-latest
    environment: production
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Install SSH key
        run: |
          mkdir -p ~/.ssh
          echo "${{ secrets.DEPLOY_SSH_KEY }}" > ~/.ssh/deploy_key
          chmod 600 ~/.ssh/deploy_key
          ssh-keyscan -p ${{ secrets.DEPLOY_PORT }} ${{ secrets.DEPLOY_HOST }} >> ~/.ssh/known_hosts

      - name: Backup current production state
        run: |
          ssh -i ~/.ssh/deploy_key -p ${{ secrets.DEPLOY_PORT }} \
            ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }} "
              mkdir -p ~/backups &&
              tar -czf ~/backups/pre_deploy_\$(date +%Y%m%d_%H%M%S).tar.gz \
                --exclude='public_html/uploads' \
                --exclude='public_html/Kiosco/uploads/comprobantes' \
                -C ~/${{ secrets.DEPLOY_PATH }}/.. \$(basename ${{ secrets.DEPLOY_PATH }}) &&
              cd ~/backups && ls -t pre_deploy_*.tar.gz | tail -n +6 | xargs -r rm --
            "

      - name: Download backup as workflow artifact
        run: |
          LATEST=$(ssh -i ~/.ssh/deploy_key -p ${{ secrets.DEPLOY_PORT }} \
            ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }} \
            "ls -t ~/backups/pre_deploy_*.tar.gz | head -1")
          scp -i ~/.ssh/deploy_key -P ${{ secrets.DEPLOY_PORT }} \
            "${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }}:${LATEST}" ./pre_deploy_backup.tar.gz

      - name: Upload backup artifact
        uses: actions/upload-artifact@v4
        with:
          name: pre-deploy-backup-${{ github.sha }}
          path: pre_deploy_backup.tar.gz
          retention-days: 30

      - name: Rsync deploy
        run: |
          rsync -avz \
            -e "ssh -i ~/.ssh/deploy_key -p ${{ secrets.DEPLOY_PORT }}" \
            --exclude-from=deploy/rsync-excludes.txt \
            ./ ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }}:~/${{ secrets.DEPLOY_PATH }}/
```

- [ ] **Step 2: Verificar el YAML localmente**

```bash
python3 -c "import yaml; yaml.safe_load(open('.github/workflows/deploy.yml'))" && echo YAML_OK
```

Expected: `YAML_OK`.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/deploy.yml
git commit -m "$(cat <<'EOF'
Add gated deploy job: manual approval, pre-deploy backup, rsync

Deploy only runs on push to main, behind the production environment's
required reviewer. Backs up the live server (excluding uploads/) both
remotely and as a downloadable workflow artifact before syncing, and
never runs against the database.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

- [ ] **Step 4: 🧑 Push y aprobar manualmente el primer deploy real**

```bash
git push
gh run list --repo CristobalHenriquez/escuela-san-martin-app --workflow=deploy.yml --limit 1
```

Expected: el run queda en estado `waiting` sobre el job `deploy` (esperando revisor). El usuario debe entrar a la pestaña Actions de GitHub y aprobar manualmente antes de que el rsync real se ejecute contra producción.

- [ ] **Step 5: Verificar el resultado tras la aprobación**

```bash
gh run watch --repo CristobalHenriquez/escuela-san-martin-app
curl -sf -o /dev/null -w "%{http_code}\n" https://eeso225-lasanmartin.edu.ar/
```

Expected: el run termina en `success`; el `curl` final imprime `200`.

---

### Task 9: Workflow `rollback.yml`

**Files:**
- Create: `.github/workflows/rollback.yml`

**Interfaces:**
- Consume: secretos de Task 6.

- [ ] **Step 1: Crear el workflow de rollback**

Crear `.github/workflows/rollback.yml`:

```yaml
name: Rollback

on:
  workflow_dispatch:
    inputs:
      backup_file:
        description: 'Nombre exacto del backup en ~/backups/ (vacío = el más reciente)'
        required: false
        default: ''

jobs:
  rollback:
    runs-on: ubuntu-latest
    environment: production
    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Install SSH key
        run: |
          mkdir -p ~/.ssh
          echo "${{ secrets.DEPLOY_SSH_KEY }}" > ~/.ssh/deploy_key
          chmod 600 ~/.ssh/deploy_key
          ssh-keyscan -p ${{ secrets.DEPLOY_PORT }} ${{ secrets.DEPLOY_HOST }} >> ~/.ssh/known_hosts

      - name: Resolve backup file to restore
        id: resolve
        run: |
          INPUT="${{ github.event.inputs.backup_file }}"
          if [ -z "$INPUT" ]; then
            FILE=$(ssh -i ~/.ssh/deploy_key -p ${{ secrets.DEPLOY_PORT }} \
              ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }} \
              "ls -t ~/backups/*.tar.gz | head -1")
          else
            FILE="~/backups/$INPUT"
          fi
          echo "file=$FILE" >> "$GITHUB_OUTPUT"

      - name: Restore backup on the server
        run: |
          ssh -i ~/.ssh/deploy_key -p ${{ secrets.DEPLOY_PORT }} \
            ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }} "
              rm -rf /tmp/rollback_restore && mkdir -p /tmp/rollback_restore &&
              tar -xzf ${{ steps.resolve.outputs.file }} -C /tmp/rollback_restore &&
              rsync -a --delete \
                --exclude='uploads' --exclude='Kiosco/uploads/comprobantes' \
                /tmp/rollback_restore/$(basename ${{ secrets.DEPLOY_PATH }})/ \
                ~/${{ secrets.DEPLOY_PATH }}/ &&
              rm -rf /tmp/rollback_restore
            "
```

- [ ] **Step 2: Verificar el YAML localmente**

```bash
python3 -c "import yaml; yaml.safe_load(open('.github/workflows/rollback.yml'))" && echo YAML_OK
```

Expected: `YAML_OK`.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/rollback.yml
git commit -m "$(cat <<'EOF'
Add manual rollback workflow to restore any pre-deploy backup

workflow_dispatch only — never runs automatically. Defaults to the
most recent backup on the server if no filename is given.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
git push
```

- [ ] **Step 4: Documentar el uso del rollback**

Agregar al final de `README.md` (raíz) una sección nueva:

```markdown
## 🔙 Rollback de producción

Si un deploy rompe algo, se puede restaurar el backup previo (tomado automáticamente antes de
cada deploy) desde GitHub: pestaña **Actions → Rollback → Run workflow**. Dejar el campo
`backup_file` vacío restaura el backup más reciente; si se necesita uno específico, revisar los
artifacts de deploys anteriores (`pre-deploy-backup-<sha>`) o listar `~/backups/` en el servidor
por SSH.
```

- [ ] **Step 5: Commit**

```bash
git add README.md
git commit -m "$(cat <<'EOF'
Document the rollback workflow in the README

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
git push
```
