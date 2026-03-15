# Deploy — Fisiopilates Atlas

## Entornos

### TEST (desarrollo activo)

| Parámetro | Valor |
|-----------|-------|
| URL | `https://40749769.servicio-online.net` |
| FTP host | `40749769.servicio-online.net` |
| FTP puerto | `21` (SFTP/22 bloqueado en Hostalia) |
| FTP usuario | `user-10067489` |
| FTP ruta remota | `/httpdocs` |
| DB host | `PMYSQL168.dns-servicio.com` |
| DB nombre | `10067489_fisiopilates_TEST` |
| DB usuario | `cppleal_fisiopilates` |
| Admin URL | `https://40749769.servicio-online.net/admin/` |

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

---

## Método principal de deploy: Node.js (`scripts/deploy.mjs`)

```bash
# Deploy TEST completo (build + subida)
npm run build && node scripts/deploy.mjs

# Deploy PROD (requiere permiso explícito — espera 10 segundos, Ctrl+C para cancelar)
node scripts/deploy.mjs prod

# Deploy con install.php (primer deploy en nuevo entorno)
node scripts/deploy.mjs --install
```

### Archivos que sube el script en deploy completo

**Estáticos (`dist/`):**
- Todo el contenido generado por Astro, incluyendo `.htaccess`

**PHP Backend:**
```
php/config.php                       → /api/config.php
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

## Método alternativo: WinSCP (.bat)

```bat
REM Deploy TEST completo
deploy\deploy-local.bat test

REM Deploy TEST parcial (archivos específicos desde dist/)
deploy\deploy-local.bat test index.html api/contacto.php
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
# https://40749769.servicio-online.net

# 4. Con permiso del usuario → deploy a PROD
node scripts/deploy.mjs prod
```

---

## Primer deploy en un entorno nuevo

```bash
# 1. Build
npm run build

# 2. Deploy con install.php
node scripts/deploy.mjs --install

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
/httpdocs/                  (raíz TEST) | /fisiopilatesatlas.es/ (raíz PROD)
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
│   ├── config.php          ← Auto-detecta entorno por hostname
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

RewriteEngine On

# Servir directamente si el fichero o directorio existe
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Clean URLs: /pilates → /pilates.html
RewriteCond %{REQUEST_FILENAME}.html -f
RewriteRule ^(.+?)/?$ /$1.html [L]

ErrorDocument 404 /404.html
```

---

## Backup de base de datos

Los backups se realizan mediante PHP CLI directamente contra la BD (sin mysqldump).

```bash
# Ejecutar script de backup (genera SQL en backup/prod/ y backup/test/)
php backup/do-backup.php
```

Los ficheros se guardan como:
```
backup/prod/backup_prod_YYYY-MM-DD_HHmmss.sql
backup/test/backup_test_YYYY-MM-DD_HHmmss.sql
```

> Las carpetas `backup/test/` y `backup/prod/` están en `.gitignore`.

---

## Variables de entorno (`.env`)

Credenciales sensibles en `.env` (nunca en git):

```env
FTP_HOST_TEST=40749769.servicio-online.net
FTP_USER_TEST=user-10067489
FTP_PASS_TEST=...
FTP_REMOTE_DIR_TEST=/httpdocs

SFTP_HOST_PROD=40546259.servicio-online.net
SFTP_USER_PROD=user-9702349
SFTP_REMOTE_DIR_PROD=/fisiopilatesatlas.es

DB_HOST=...
DB_NAME=...
DB_USER=...
DB_PASS=...
```

---

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
| `.env` | Credenciales FTP y BD |
| `deploy/deploy-config.bat` | Credenciales FTP reales |
| `backup/test/` | Volcados SQL de TEST |
| `backup/prod/` | Volcados SQL de PROD |

---

## Procedimiento de creación de versión

Ejecutar **solo cuando el usuario lo indique explícitamente**.

### Pasos
1. Backup BD: `php backup/do-backup.php`
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
