# v1.3.0 — proteccion_ip_deploy_produccion
**Fecha:** 2026-03-15

## Resumen
Primera puesta en producción de la web nueva (sustitución del Joomla legacy), con protección de acceso al panel de administración por IP, gestión de IPs desde el panel, mejora del cambio de contraseña, galería de instalaciones reales y corrección del routing Apache.

---

## Cambios incluidos

### 1. Seguridad — Protección del admin por IP
- **`php/admin/ip-check.php`** *(nuevo)* — Helper con funciones `getClientIP()` y `checkAdminIP(PDO)`. Bloquea el acceso al panel con HTTP 403 si la IP del cliente no está en la tabla `admin_ips`. Si la tabla está vacía, el acceso es libre (estado inicial).
- **`php/admin/index.php`** — Conexión a BD movida al inicio del script (antes del login) para permitir la verificación de IP previa a cualquier acción. Añadida llamada a `checkAdminIP($pdo)`.
- **`php/admin/cookies.php`** — Añadida verificación de IP (`require_once ip-check.php` + `checkAdminIP($pdo)`).

### 2. Panel admin — Gestión de IPs permitidas
- **`php/admin/index.php`** — Nueva sección "Control de acceso por IP":
  - Tabla de IPs registradas con descripción, fecha de alta y badge "Tu IP actual"
  - Formulario para añadir nueva IP (campo pre-rellenado con la IP actual)
  - Botón "Eliminar" con aviso de advertencia si es la IP activa
  - Tarjeta de estadística en el dashboard (naranja si no hay IPs = sin protección)
  - Acción `?action=delete_ip&id=X` para eliminar una IP
  - Validación con `filter_var($ip, FILTER_VALIDATE_IP)`

### 3. Panel admin — Mejora cambio de contraseña
- **`php/admin/index.php`** — El formulario de cambio de contraseña ahora requiere:
  1. Contraseña actual (verificada con `password_verify()` antes de cambiar)
  2. Nueva contraseña (mín. 8 caracteres)
  3. Confirmación de la nueva contraseña (debe coincidir)

### 4. Base de datos — Nueva tabla `admin_ips`
- **`php/install.php`** — Añadida creación de tabla `admin_ips` y registro automático de la IP del instalador (`REMOTE_ADDR`).
- **BD TEST** — Tabla `admin_ips` creada manualmente vía PHP CLI.
- **BD PROD** — Tabla `admin_ips` creada manualmente vía PHP CLI.

### 5. Base de datos — Corrección `cookie_consent_logs` en PROD
- La tabla creada en PROD tenía solo 6 columnas; `CookieConsentService.php` requería 11.
- **BD PROD** — Añadidas columnas faltantes vía `ALTER TABLE`: `session_token`, `necessary`, `ip_address`, `user_agent`, `page_url`, `consent_version`.
- **`php/install.php`** — Corregida la definición de la tabla para que futuras instalaciones la creen con la estructura completa.

### 6. Backend — Auto-detección de entorno en `config.php`
- **`php/config.php`** — Sustituidas las credenciales TEST hardcodeadas por detección automática del entorno mediante `$_SERVER['HTTP_HOST']`:
  - Host contiene `fisiopilatesatlas.es` → credenciales PROD
  - Cualquier otro host → credenciales TEST
  - Variables de entorno (`DB_HOST`, etc.) tienen prioridad si están definidas

### 7. Frontend — Imágenes de instalaciones reales
- **`public/images/DSC_0009.jpg`** *(nuevo)* — Sala de fisioterapia (descargada del Joomla legacy)
- **`public/images/DSC_0020.jpg`** *(nuevo)* — Sala de Pilates (descargada del Joomla legacy)
- **`public/images/DSC_0040.jpg`** *(nuevo)* — Foto principal del centro (del repositorio local)
- **`src/pages/index.astro`** — Sección "Nuestro Centro" rediseñada: grid 50/50 con `DSC_0009.jpg` y `DSC_0020.jpg` (antes: 3 imágenes con fotos de metro/calle).
- **`src/pages/index.astro`** — Hero principal cambiado de `pilates_clase.webp` a `DSC_0040.jpg`.
- **`src/pages/precios.astro`** — Hero cambiado de `precios_pilates_02.jpg` a `pilates_clase.webp`.

### 8. Routing Apache — `.htaccess`
- **`public/.htaccess`** *(nuevo)* — Creado fichero `.htaccess` con:
  - `Options -Indexes` (sin listado de directorios)
  - `RewriteEngine On` con regla clean URLs: `/pilates` → `/pilates.html`
  - `ErrorDocument 404 /404.html`
  - Cabeceras de caché para imágenes y assets estáticos
- Sin este fichero, todas las páginas excepto `/` devolvían 404 en producción.

### 9. Deploy — `scripts/deploy.mjs`
- Añadido `php/admin/ip-check.php` a la lista de ficheros PHP desplegados.

### 10. Primer deploy a PRODUCCIÓN (2026-03-15)
**Proceso ejecutado:**
1. Backup BD PROD completa → `backup/prod/backup_prod_2026-03-15_211535.sql` (24 MB — Joomla completo)
2. Backup BD TEST → `backup/test/backup_test_2026-03-15_211535.sql` (6 KB)
3. Creación tablas en BD PROD mediante PHP CLI (sin necesidad de `install.php` en servidor)
4. Eliminación del Joomla legacy: 35 elementos borrados de `/fisiopilatesatlas.es` vía FTP
5. Deploy completo: HTML estático + backend PHP
6. Subida de `.htaccess` correctivo para clean URLs

---

## Especificaciones actualizadas
- `specs/admin.md` — Sección protección por IP, gestión de IPs, mejora cambio contraseña
- `specs/backend.md` — Auto-detección entorno en config.php, tabla `admin_ips`, corrección `cookie_consent_logs`, `install.php` actualizado
- `specs/deploy.md` — PROD actualizado (web nueva activa), estructura remota con `ip-check.php`, `.htaccess`, backup PHP CLI, historial de versiones
- `specs/arquitectura.md` — Estructura con `ip-check.php`, `public/.htaccess`, carpeta `images/`
- `specs/paginas.md` — Imágenes hero actualizadas (index, precios), galería "Nuestro Centro" con fotos reales

## Notas de actualización
- **Contraseña admin PROD:** La contraseña por defecto es `atlas2025`. Cambiarla inmediatamente desde `/admin/`.
- **IPs admin PROD:** La tabla `admin_ips` está vacía (acceso libre). Añadir la IP desde el panel para activar la protección.
- **BD PROD:** La tabla `cookie_consent_logs` tiene la columna `ip` sobrante del script de instalación incorrecto. No afecta al funcionamiento (ignorada por el servicio).
