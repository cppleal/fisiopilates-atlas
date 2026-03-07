# v1.0.0 — Primera versión completa
**Fecha:** 2026-03-06

## Resumen
Primera versión completa de la web de Fisiopilates Atlas. Incluye todas las páginas del sitio, sistema de contacto con envío de email SMTP, panel de administración, y sistema de cookies RGPD conforme a la ley española.

## Cambios incluidos

### 1. Estructura y páginas
- `src/pages/index.astro` — Home con hero, servicios, mapa y CTA
- `src/pages/fisioterapia.astro` — Servicios y patologías de fisioterapia
- `src/pages/pilates.astro` — Clases de pilates, grupos reducidos, pilates embarazo
- `src/pages/precios.astro` — Tarifas fisioterapia y pilates
- `src/pages/contacto.astro` — Formulario + información de contacto
- `src/pages/privacidad.astro` — Política de privacidad RGPD
- `src/pages/cookies.astro` — Política de cookies
- `src/pages/404.astro` — Página de error 404
- `src/layouts/Layout.astro` — Layout base con meta, favicon, scripts
- `src/components/Header.astro` — Cabecera con menú de navegación
- `src/components/Hero.astro` — Componente hero reutilizable
- `src/components/SectionTitle.astro` — Títulos de sección

### 2. Backend PHP
- `php/config.php` — Configuración BD y SMTP (credenciales vía .env)
- `php/contacto.php` — Endpoint de formulario con validación y email SMTP
- `php/install.php` — Script de instalación inicial de tablas BD
- `php/lib/SmtpMailer.php` — Librería SMTP con soporte STARTTLS
- `php/admin/index.php` — Panel de administración (mensajes, login)
- `php/admin/cookies.php` — Panel gestión consentimientos RGPD
- `php/cookies/log-consent.php` — Endpoint de logging de cookies
- `php/cookies/CookieConsentService.php` — Servicio de consentimiento

### 3. Cookies y RGPD
- `public/js/cookie-consent.js` — Banner y modal de cookies
- `public/css/cookie-consent.css` — Estilos del sistema de cookies

### 4. Deploy y configuración
- `deploy/deploy-local.bat` — Deploy principal via WinSCP
- `deploy/deploy-config.template.bat` — Plantilla de credenciales
- `scripts/deploy.mjs` — Deploy alternativo Node.js via FTP
- `astro.config.mjs` — Configuración Astro 5
- `package.json` — Dependencias del proyecto

### 5. Especificaciones técnicas
- `specs/README.md` — Índice de especificaciones
- `specs/arquitectura.md` — Stack, estructura, flujo de datos
- `specs/backend.md` — PHP, MySQL, SMTP, endpoints API
- `specs/admin.md` — Panel de administración
- `specs/cookies-rgpd.md` — Sistema de cookies RGPD
- `specs/paginas.md` — Contenido y estructura de páginas
- `specs/deploy.md` — Deploy FTP, entornos, backup BD, versionado

## Notas de actualización
- Ejecutar `php/install.php` en TEST para crear tablas si no existen
- Rellenar credenciales reales en `deploy/deploy-config.bat` (no está en git)
- Configurar `.env` con passwords de BD y FTP
- Credenciales PROD de BD pendientes de configurar en `backup/backup-database.php`
