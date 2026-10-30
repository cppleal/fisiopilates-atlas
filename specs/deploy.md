# Deploy — Fisiopilates Atlas

## Entornos

### TEST (VPS de Hostinger desde 2026-10-30, ver `traslado-test-vps.md`)

| Parámetro | Valor |
|-----------|-------|
| URL | `https://test.fisiopilatesatlas.es` |
| Servidor | VPS 187.77.170.2, `/opt/fisiopilates-test` (SSH con `VPS_*` del `.env`) |
| Contenedores | `fa-test-web` (php:8.4-apache), `fa-test-db` (mysql:8.4) |
| DB host | `db` desde la web; `127.0.0.1:33309` desde el PC con `scripts/tunel-test.mjs` |
| DB nombre | `fisiopilates_test` |
| DB usuario | `fisiopilates_test` |
| Admin URL | `https://test.fisiopilatesatlas.es/admin/` |

### PRODUCCIÓN (web nueva activa desde 2026-03-15)

| Parámetro | Valor |
|-----------|-------|
| URL | `https://fisiopilatesatlas.es` |
| FTP host | `40546259.servicio-online.net` |
| FTP puerto | `21` |
| FTP usuario | `user-9702349` |
| FTP ruta remota | `/fisiopilatesatlas.es` |
| DB host | `PMYSQL117.dns-servicio.com` |
| DB nombre | `9702349_fisio` |
| DB usuario | `cppleal-fisio` |
| Admin URL | `https://fisiopilatesatlas.es/admin/` |

> **Nota:** El Joomla legacy fue eliminado el 2026-03-15. La web nueva Astro está activa en producción.
> **Regla crítica:** NUNCA hacer deploy a PROD sin permiso explícito del usuario.
> **TEST viejo de Hostalia** (`40749769.servicio-online.net`): congelado en una versión anterior a la 1.4.0. Su FTP
> rechaza la contraseña (530) desde 2026-10-30 y ya no se despliega en él. Pendiente de borrar: lo decide César.

---

## Método principal de deploy: Node.js (`scripts/deploy.mjs`)

```bash
# Deploy TEST completo (build + subida al VPS por SSH)
npm run build && node scripts/deploy.mjs

# Comprobar TEST de punta a punta (solo TEST: pide rutas sensibles; nunca contra Hostalia)
node scripts/verificar-test.mjs

# Deploy PROD (requiere permiso explícito — espera 10 segundos, Ctrl+C para cancelar)
node scripts/deploy.mjs prod

# Deploy PROD con install.php (primer deploy en nuevo entorno)
node scripts/deploy.mjs prod --install
```

### Archivos que sube el script en deploy completo

**Estáticos (`dist/`):**
- Todo el contenido generado por Astro, incluyendo `.htaccess`

**PHP Backend:**
```
php/config.template.php + .env       → /api/config.php   (generado: scripts/config-servidor.mjs)
php/contacto.php                     → /api/contacto.php
php/lib/SmtpMailer.php               → /api/lib/SmtpMailer.php
php/admin/index.php                  → /admin/index.php
php/admin/cookies.php                → /admin/cookies.php
php/admin/ip-check.php               → /admin/ip-check.php
php/cookies/log-consent.php          → /api/cookies/log-consent.php
php/cookies/CookieConsentService.php → /api/cookies/CookieConsentService.php
```

**Condicional:**
```
php/install.php → /install.php   (solo con --install)
```

---

## Método alternativo: WinSCP (.bat), solo PROD

```bat
REM Deploy PROD parcial (archivos específicos desde dist/; pide confirmación)
deploy\deploy-local.bat prod index.html
```

> Nota: el método .bat solo sube archivos desde `dist/`. Para PHP hay que usar `deploy.mjs`.

---

## Proceso de deploy habitual (cambios frontend o PHP)

```bash
# 1. Generar estáticos
npm run build

# 2. Deploy a TEST
node scripts/deploy.mjs

# 3. Verificar en navegador
# https://test.fisiopilatesatlas.es

# 4. Con permiso del usuario → deploy a PROD
node scripts/deploy.mjs prod
```

---

## Primer deploy en un entorno nuevo

```bash
# 1. Build
npm run build

# 2. Deploy con install.php
node scripts/deploy.mjs prod --install

# 3. Ejecutar en el navegador:
# https://dominio/install.php
# → Crea las 4 tablas y registra la IP del instalador

# 4. ¡IMPORTANTE! El install.php se auto-elimina tras ejecutarse
# (o borrarlo manualmente del servidor)

# 5. Entrar al panel admin y:
#    a) Cambiar contraseña (atlas2025 → nueva)
#    b) Verificar IPs permitidas
```

---

## Estructura remota del servidor

```
/opt/fisiopilates-test/site/ (raíz TEST) | /fisiopilatesatlas.es/ (raíz PROD)
├── index.html              ← Astro static
├── fisioterapia.html
├── pilates.html
├── precios.html
├── contacto.html
├── privacidad.html
├── cookies.html
├── 404.html
├── .htaccess               ← Clean URLs + caché + ErrorDocument 404
├── _astro/                 ← CSS/JS bundles Astro
├── images/                 ← Imágenes del sitio
├── css/
│   └── cookie-consent.css
├── js/
│   └── cookie-consent.js
├── api/
│   ├── config.php          ← Generado por entorno (solo sus credenciales; en TEST, montado desde env/)
│   ├── contacto.php
│   ├── lib/
│   │   └── SmtpMailer.php
│   └── cookies/
│       ├── log-consent.php
│       └── CookieConsentService.php
└── admin/
    ├── index.php
    ├── cookies.php
    └── ip-check.php        ← Protección acceso por IP
```

---

## `.htaccess` (generado en `public/.htaccess`)

```apache
Options -Indexes
DirectoryIndex index.html index.php

# api/config.php nunca se sirve por HTTP (PHP lo carga con require)
<Files "config.php">
  Require all denied   # (con su alternativa Order/Deny para Apache antiguo)
</Files>

RewriteEngine On

# Servir directamente si el fichero o directorio existe
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Clean URLs: /pilates → /pilates.html
RewriteCond %{REQUEST_FILENAME}.html -f
RewriteRule ^(.+?)/?$ /$1.html [L]

ErrorDocument 404 /404.html

# (aquí va la caché de assets con mod_expires)

<IfModule mod_headers.c>
  Header always set Content-Security-Policy "frame-ancestors 'self'; object-src 'none'; base-uri 'self'"
  Header always set X-Frame-Options "SAMEORIGIN"
  Header always set X-Content-Type-Options "nosniff"
  Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

> En Hostalia, nginx sirve los estáticos sin pasar por Apache: estas cabeceras solo llegan a las respuestas PHP.
> En TEST (VPS) llegan a todo.

`npm run build` acaba con `scripts/check-csp.mjs`: si falla, el build falla y no se despliega (ver `arquitectura.md`, «Content Security Policy»).

---

## Backup de base de datos

Los backups se realizan mediante PHP CLI directamente contra la BD (sin mysqldump),
con las credenciales `DB_*_TEST` / `DB_*_PROD` del `.env` local. TEST va por un túnel SSH que el script abre y
cierra solo (`scripts/tunel-test.mjs`).

```bat
backupackup.bat all vX.Y.Z
```

Genera `estructura.sql` y `completo.sql` en:
```
backup/test/vX.Y.Z/
backup/prod/vX.Y.Z/
```

> Las carpetas `backup/test/` y `backup/prod/` están en `.gitignore`.

---

## Variables de entorno (`.env`)

El `.env` es **solo local** (nunca en git ni en ningún servidor). Reúne lo
necesario para trabajar desde el PC, con sufijo de entorno:

```env
# FTP (solo para desplegar)
FTP_HOST_PROD=40546259.servicio-online.net
FTP_USER_PROD=user-9702349
FTP_PASS_PROD=...
FTP_REMOTE_DIR_PROD=/fisiopilatesatlas.es

# Lo que va al api/config.php de cada servidor (CLAVE_TEST / CLAVE_PROD)
DB_HOST_TEST=127.0.0.1  DB_PORT_TEST=33309  (el túnel; en el VPS, config-servidor.mjs pone «db»)
DB_NAME_TEST=...   DB_USER_TEST=...   DB_PASS_TEST=...   DB_ROOT_PASS_TEST=...
APP_SECRET_TEST=...  HCAPTCHA_SITE_KEY_TEST=...  HCAPTCHA_SECRET_TEST=...
SMTP_HOST_TEST=...  SMTP_PORT_TEST=...  SMTP_USER_TEST=...  SMTP_PASS_TEST=...
SMTP_FROM_TEST=...  SMTP_FROM_NAME_TEST=...
# …y las mismas con _PROD

# VPS de Hostinger (TEST)
VPS_HOST=...  VPS_PORT=...  VPS_USER=...  VPS_HOSTKEY=...  VPS_PASS=...  VPS_DIR=/opt/fisiopilates-test

# TEST viejo de Hostalia, solo para la vuelta atrás
TEST_HOSTALIA_FTP_*  TEST_HOSTALIA_DB_*
```

- `scripts/config-servidor.mjs` genera el `api/config.php` de cada servidor con
  las claves de su entorno (ver `specs/backend.md`).
- `backup/backup-database.php` lee `DB_*_TEST` / `DB_*_PROD`.
- `scripts/deploy.mjs` lee `VPS_*` para TEST y `FTP_*` para PROD; `scripts/get-prod-images.mjs` lee `FTP_*`.

## Notas importantes de Hostalia

- **SFTP (puerto 22) BLOQUEADO** en ambos servidores → usar siempre FTP (21)
- **`CREATE TABLE IF NOT EXISTS`** no actualiza tablas con esquema diferente → usar `ALTER TABLE` si el esquema cambia
- **`.htaccess`:** No añadir `RewriteRule` para archivos PHP → causa bucle 500
  - ✅ `RewriteCond %{REQUEST_FILENAME} -f [OR] -d` → pasa archivos existentes tal cual
  - ❌ `RewriteRule ^api/contacto\.php$` → loop infinito

---

## Repositorio GitHub

- **Repo**: `cppleal/fisiopilates-atlas` (privado)
- **Remote**: `https://github.com/cppleal/fisiopilates-atlas.git`
- **Rama principal**: `master`

### Ficheros excluidos del repositorio (`.gitignore`)
| Fichero/Carpeta | Motivo |
|-----------------|--------|
| `node_modules/` | Dependencias |
| `dist/` | Build output |
| `.env` | Credenciales FTP, VPS, BD, SMTP y hCaptcha (solo local) |
| `deploy/deploy-config.bat` | Credenciales FTP reales |
| `backup/test/` | Volcados SQL de TEST |
| `backup/prod/` | Volcados SQL de PROD |

---

## Procedimiento de creación de versión

Ejecutar **solo cuando el usuario lo indique explícitamente**.

### Pasos
1. Backup BD: `backupackup.bat all vX.Y.Z`
2. Actualizar specs afectadas en `specs/`
3. Crear `versiones/vX.Y.Z-desc/changelog.md`
4. Actualizar fichero `VERSION`
5. `git add -A && git commit -m "version vX.Y.Z-desc" && git push`

### Semántica de versiones
| Tipo | Cuándo |
|------|--------|
| PATCH (X.Y.**Z**) | Correcciones, ajustes menores |
| MINOR (X.**Y**.0) | Nueva funcionalidad, nuevo contenido significativo |
| MAJOR (**X**.0.0) | Rediseño completo, cambio de arquitectura |

---

## Historial de versiones

| Versión | Fecha | Descripción |
|---------|-------|-------------|
| `0.0.1` | 2026-02 | Scaffolding inicial |
| `1.0.0` | 2026-03-06 | Primera versión completa: 8 páginas, contacto, admin, cookies RGPD |
| `1.1.0` | 2026-03-07 | Sistema de backup BD, repositorio GitHub y procedimiento de versionado |
| `1.2.0` | 2026-03-08 | Logo real en la cabecera |
| `1.3.0` | 2026-03-15 | Protección admin por IP, primer deploy a producción, galería instalaciones |
| `1.3.1` | 2026-03-15 | Limpieza de la BD de producción (tablas Joomla) |
| `1.4.0` | 2026-10-30 | CSP por hashes, config.php por entorno, CORS limitado y aviso de precios |
