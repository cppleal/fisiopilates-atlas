# Deploy — Fisiopilates Atlas

## Entornos

### TEST (desarrollo activo)

| Parámetro | Valor |
|-----------|-------|
| URL | `https://40749769.servicio-online.net` |
| FTP host | `40749769.servicio-online.net` |
| FTP puerto | `21` (SFTP/22 bloqueado en Hostalia) |
| FTP usuario | `user-10067489` |
| DB host | `PMYSQL168.dns-servicio.com` |
| DB nombre | `10067489_fisiopilates_TEST` |
| DB usuario | `cppleal_fisiopilates` |
| Admin URL | `https://40749769.servicio-online.net/admin/` |

### PRODUCCIÓN (⚠️ Joomla legacy activo)

| Parámetro | Valor |
|-----------|-------|
| URL | `https://fisiopilatesatlas.es` |
| FTP host | `40546259.servicio-online.net` |
| FTP puerto | `21` |
| FTP usuario | `user-9702349` |
| DB host | `PMYSQL117.dns-servicio.com` |
| DB nombre | `9702349_fisio` |
| Estado | **Joomla activo — NO SOBREESCRIBIR sin autorización** |

> **Regla crítica:** NUNCA hacer deploy a PROD sin permiso explícito del usuario.

---

## Método principal de deploy: WinSCP (.bat)

### Deploy TEST completo
```bat
deploy\deploy-local.bat test
```

### Deploy TEST parcial (archivos específicos)
```bat
deploy\deploy-local.bat test index.html
deploy\deploy-local.bat test api/contacto.php admin/cookies.php
```

### Deploy PROD (requiere confirmación explícita)
```bat
deploy\deploy-local.bat prod
```

### Descargar imágenes de PROD
```bat
deploy\get-prod-images.bat
```
> Descarga imágenes del Joomla legacy a `public/images/`

### Archivos del método WinSCP
| Archivo | Descripción |
|---------|-------------|
| `deploy\deploy-local.bat` | Script principal de deploy |
| `deploy\deploy-config.bat` | Credenciales reales (no en git) |
| `deploy\deploy-config.template.bat` | Plantilla sin credenciales (en git) |
| `deploy\get-prod-images.bat` | Descarga imágenes PROD |

---

## Método alternativo: Node.js (`scripts/deploy.mjs`)

```bash
# Deploy TEST completo
node scripts/deploy.mjs

# Deploy TEST parcial
node scripts/deploy.mjs test index.html api/contacto.php

# Deploy PROD (espera 10 segundos, Ctrl+C para cancelar)
node scripts/deploy.mjs prod

# Deploy con install.php (primer deploy en nuevo entorno)
node scripts/deploy.mjs --install
```

### Archivos que sube el script Node en deploy completo

**Estáticos (dist/):**
- Todo el contenido del directorio `dist/` (generado por Astro)

**PHP Backend:**
```
php/config.php                      → /api/config.php
php/contacto.php                    → /api/contacto.php
php/lib/SmtpMailer.php              → /api/lib/SmtpMailer.php
php/admin/index.php                 → /admin/index.php
php/admin/cookies.php               → /admin/cookies.php
php/cookies/log-consent.php        → /api/cookies/log-consent.php
php/cookies/CookieConsentService.php → /api/cookies/CookieConsentService.php
```

**Condicional:**
```
php/install.php   → /install.php   (solo con --install)
```

---

## Proceso de deploy paso a paso

### Deploy habitual (cambios frontend o PHP)

```bash
# 1. Generar estáticos
npm run build

# 2. Deploy a TEST
node scripts/deploy.mjs
# o: deploy\deploy-local.bat test

# 3. Verificar en navegador
# https://40749769.servicio-online.net
```

### Primer deploy en nuevo entorno

```bash
# 1. Build
npm run build

# 2. Deploy con install.php
node scripts/deploy.mjs --install

# 3. Ejecutar en el navegador:
# https://dominio/install.php

# 4. ¡IMPORTANTE! Borrar install.php del servidor inmediatamente
```

---

## Estructura remota del servidor

```
/httpdocs/                  (raíz TEST)
├── index.html              ← Astro static
├── fisioterapia.html
├── pilates.html
├── precios.html
├── contacto.html
├── privacidad.html
├── cookies.html
├── 404.html
├── .htaccess
├── _astro/                 ← CSS/JS bundles Astro
├── images/                 ← Imágenes
├── css/
│   └── cookie-consent.css
├── js/
│   └── cookie-consent.js
├── api/
│   ├── config.php
│   ├── contacto.php
│   ├── lib/
│   │   └── SmtpMailer.php
│   └── cookies/
│       ├── log-consent.php
│       └── CookieConsentService.php
└── admin/
    ├── index.php
    └── cookies.php
```

---

## Variables de entorno (`.env`)

Credenciales sensibles gestionadas en `.env` (nunca en git):

```env
FTP_HOST_TEST=40749769.servicio-online.net
FTP_USER_TEST=user-10067489
FTP_PASS_TEST=...
FTP_REMOTE_DIR_TEST=/httpdocs

SFTP_HOST_PROD=40546259.servicio-online.net
SFTP_USER_PROD=user-9702349
SFTP_REMOTE_DIR_PROD=/fisiopilatesatlas.es

DB_HOST=PMYSQL168.dns-servicio.com
DB_NAME=10067489_fisiopilates_TEST
DB_USER=cppleal_fisiopilates
DB_PASS=...
```

---

## Notas importantes de Hostalia

- **SFTP (puerto 22) BLOQUEADO** en ambos servidores → usar siempre FTP (21)
- **`.htaccess`:** No añadir `RewriteRule` explícitas para archivos PHP → causa bucle infinito (500 error)
  - ✅ Correcto: `RewriteCond %{REQUEST_FILENAME} -f [OR] -d` → pasa archivos existentes tal cual
  - ❌ Incorrecto: `RewriteRule ^api/contacto\.php$ /api/contacto.php [L]` → loop infinito
- **`CREATE TABLE IF NOT EXISTS`** no actualiza tablas existentes con esquema diferente → usar `DROP TABLE` + recrear si el esquema cambia

---

## Versionado

| Versión | Fecha | Descripción |
|---------|-------|-------------|
| `0.0.1` | 2026-02 | Scaffolding inicial |
| `1.0.0` | 2026-03-06 | Primera versión completa: 8 páginas, contacto, admin, cookies RGPD |
