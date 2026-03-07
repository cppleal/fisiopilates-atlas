# CLAUDE.md - Fisiopilates Atlas

## Proyecto
Web del Centro de Fisioterapia y Pilates Atlas, Carabanchel Alto, Madrid.
Migración de Joomla a web moderna con Astro 5 + Tailwind CSS 4.

## Stack técnico
- **Frontend**: Astro 5 + Tailwind CSS 4 (genera HTML estático)
- **Backend**: PHP 8 + MySQL (formulario contacto + panel admin)
- **Deploy**: WinSCP via FTP (puerto 21) — igual que en Asociación Aérea
- **Repositorio**: GitHub (privado) — cppleal/fisiopilates-atlas
- **Nota**: Puerto SFTP (22) bloqueado en Hostalia → siempre FTP (21)

## Paleta de colores
- Primary: #1B6B6E (teal profundo)
- Primary light: #2D8A8E
- Accent: #E07B39 (naranja cálido)
- BG light: #F4F9F9

## Entornos

### TEST (desarrollo activo)
- **URL FTP**: 40749769.servicio-online.net (puerto 21)
- **Usuario**: user-10067489
- **BD**: PMYSQL168.dns-servicio.com — 10067489_fisiopilates_TEST
- **Admin BD**: cppleal_fisiopilates
- **Admin web**: https://40749769.servicio-online.net/admin/
- **Credenciales**: en `.env`

### PRODUCCIÓN (Joomla legacy — NO TOCAR)
- **URL**: https://fisiopilatesatlas.es/
- **SFTP**: 40546259.servicio-online.net (puerto 22)
- **Usuario**: user-9702349
- **BD**: PMYSQL117.dns-servicio.com — 9702349_fisio
- **Estado**: Joomla activo. Solo accedemos para bajar imágenes.
- **Credenciales**: en `.env`

## Estructura del proyecto
```
src/pages/          → Páginas Astro (.astro)
src/components/     → Componentes reutilizables
src/layouts/        → Layout base
src/styles/         → CSS global + Tailwind
php/                → Backend PHP (config, contacto, admin)
php/admin/          → Panel de administración
php/lib/            → SmtpMailer.php
public/             → Assets estáticos (imágenes, .htaccess)
public/images/      → Imágenes del sitio
deploy/             → Scripts de deploy WinSCP
  deploy-local.bat      → Deploy principal (test/prod)
  deploy-config.bat     → Credenciales reales (NO en git)
  deploy-config.template.bat → Plantilla (sí en git)
  get-prod-images.bat   → Descarga imágenes de PROD via FTP
scripts/            → Scripts Node.js auxiliares
  deploy.mjs            → Deploy alternativo FTP (Node)
backup/             → Backups de base de datos
  backup-database.php   → Script PHP de backup
  backup.bat            → Wrapper Windows
  test/               → Backups de TEST (NO en git)
  prod/               → Backups de PROD (NO en git)
versiones/          → Historial de versiones (changelogs)
specs/              → Especificaciones técnicas del sistema
crear-version.bat   → Script automatizado de creación de versión
VERSION             → Versión actual (formato X.Y.Z)
```

## Páginas
- `/` → index.astro — Home con hero, servicios, mapa, CTA
- `/fisioterapia` → fisioterapia.astro — Servicios y patologías
- `/pilates` → pilates.astro — Clases, grupos reducidos, embarazo
- `/precios` → precios.astro — Tarifas fisioterapia y Pilates
- `/contacto` → contacto.astro — Formulario + info de contacto
- `/privacidad` → privacidad.astro — RGPD
- `/cookies` → cookies.astro — Política de cookies
- `/404` → 404.astro — Página de error

## Datos de contacto (a preservar)
- Teléfono: 691 487 526
- Email: fisiopilates.atlas@gmail.com
- Dirección: c/Travesía de Alfredo Aleix, 1 (local), Carabanchel Alto, 28044 Madrid
- Horario: Lunes-Viernes 10:00-21:00
- Metro: La Peseta · Carabanchel Alto
- Bus: 35 · 47
- Facebook: FisioterapiaYPilatesAtlas

## Reglas de deploy

### REGLA CRÍTICA: SOLO DEPLOY A TEST POR DEFECTO
- `node scripts/deploy.mjs` ✅ → TEST, permitido sin confirmación
- `node scripts/deploy.mjs prod` ❌ → REQUIERE autorización explícita del usuario
- NUNCA desplegar a PROD sin permiso del usuario
- PROD tiene Joomla legacy activo que NO debe sobreescribirse

### Proceso de deploy (WinSCP)
1. Cambios en `src/` o `php/`
2. Build: `npm run build`
3. Deploy TEST completo: `deploy\deploy-local.bat test`
4. Deploy TEST parcial: `deploy\deploy-local.bat test index.html api/contacto.php`
5. Deploy PROD (solo con permiso explícito): `deploy\deploy-local.bat prod`

### Descargar imágenes de PROD
```bat
deploy\get-prod-images.bat   → Descarga imágenes Joomla a public/images/
```
> Puerto SFTP (22) bloqueado en Hostalia → se usa FTP (21) en ambos servidores

## Versionado

### REGLA CRÍTICA: La generación de versión la decide el usuario
- NUNCA crear versión automáticamente tras un cambio
- Solo generar versión cuando el usuario lo indique explícitamente
- Los commits de desarrollo se hacen con normalidad sin bump de versión

### Repositorio GitHub
- **Repo**: `cppleal/fisiopilates-atlas` (privado)
- **Remote**: `https://github.com/cppleal/fisiopilates-atlas.git`
- Los backups de BD (backup/test/, backup/prod/) NO se suben a git
- El fichero deploy-config.bat (credenciales FTP) NO se sube a git

### Convención de commits de desarrollo
```
[feature_slug] - [descripción en español]
```
Ejemplo: `header_sticky - Cabecera fija al hacer scroll`

### Cuándo incrementar versión (solo bajo indicación del usuario)
- **PATCH** (1.0.X): Correcciones de bugs, ajustes menores de estilo
- **MINOR** (1.X.0): Nueva página, nueva funcionalidad, mejora significativa
- **MAJOR** (X.0.0): Rediseño completo, cambio de arquitectura

### Procedimiento de creación de versión (cuando el usuario lo solicite)

**Opción A — Script automatizado:**
```bat
crear-version.bat 1.1.0 nueva_funcionalidad
```
El script guía paso a paso: backup BD → revisión specs → changelog → VERSION → git push.

**Opción B — Manual (cuando Claude genera la versión):**

#### Paso 1: Backup de base de datos
```bat
backup\backup.bat test v1.1.0
REM Si hay credenciales PROD configuradas:
REM backup\backup.bat all v1.1.0
```

#### Paso 2: Revisar y actualizar especificaciones
Antes de crear el changelog, actualizar los ficheros de specs que correspondan:

| Fichero | Actualizar si... |
|---------|-----------------|
| `specs/arquitectura.md` | Cambió el stack o la estructura del proyecto |
| `specs/backend.md` | Cambió PHP, BD, tablas o endpoints |
| `specs/admin.md` | Cambió el panel de administración |
| `specs/cookies-rgpd.md` | Cambió el sistema de cookies |
| `specs/paginas.md` | Se añadieron o modificaron páginas |
| `specs/deploy.md` | Cambió el proceso de deploy, entornos o versionado |

#### Paso 3: Crear carpeta de versión y changelog
```
versiones/
  v1.1.0-descripcion_breve/
    changelog.md     ← Fecha, resumen, cambios por fichero, specs actualizadas
```

#### Paso 4: Actualizar fichero VERSION
Escribir el nuevo número de versión en `VERSION` (sin la "v").

#### Paso 5: Commit y push a GitHub
```bash
git add -A
git commit -m "version v1.1.0-descripcion_breve"
git push
```

### Estructura del changelog.md de cada versión
```markdown
# vX.Y.Z — descripcion
**Fecha:** YYYY-MM-DD

## Resumen
< descripción breve >

## Cambios incluidos
### 1. Frontend
- `src/...` —

### 2. Backend
- `php/...` —

### 3. Especificaciones
- `specs/...` — qué se actualizó

## Notas de actualización
- < instrucciones especiales >
```

## Base de datos

### Tablas (entorno TEST)
- `contacto`: Mensajes del formulario (id, nombre, email, telefono, servicio, mensaje, ip, leido, created_at)
- `admins`: Usuarios administradores (id, username, password, nombre, email, last_login, created_at)
- `cookie_consent_logs`: Consentimientos RGPD

### Conexión TEST
- Host: PMYSQL168.dns-servicio.com
- BD: 10067489_fisiopilates_TEST
- Usuario: cppleal_fisiopilates
- Credenciales en `.env` y en `php/config.php` (vía getenv)

## Pendiente / TODO
- [ ] Configurar credenciales PROD de BD en `backup/backup-database.php`
- [ ] Crear repositorio GitHub: `cppleal/fisiopilates-atlas` (privado)
- [ ] Primer push: `git remote add origin https://github.com/cppleal/fisiopilates-atlas.git && git push -u origin main`
- [ ] Configurar hCaptcha (reemplazar YOUR_HCAPTCHA_SITE_KEY en contacto.astro y config.php)
- [ ] Configurar SMTP en php/config.php
- [ ] Primer deploy TEST: `npm run build && node scripts/deploy.mjs`
- [ ] Ejecutar php/install.php en TEST para crear tablas
- [ ] Actualizar tarifas reales en precios.astro
- [ ] Ajustar coordenadas del mapa en index.astro
