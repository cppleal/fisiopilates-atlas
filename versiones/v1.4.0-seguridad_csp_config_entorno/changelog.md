# v1.4.0 — seguridad_csp_config_entorno
**Fecha:** 2026-10-30

## Resumen
Refuerzo de seguridad de la web y de las credenciales: política CSP por huellas en todas las
páginas y en el admin, `api/config.php` generado por entorno, CORS limitado en el registro de
consentimiento y ninguna contraseña en el código. Incluye el nuevo aviso de la página de precios.

## Cambios incluidos

### 1. Frontend
- `astro.config.mjs` — CSP por huellas SHA-256 con hCaptcha y Google Maps, sin `unsafe-inline`.
- `src/components/Header.astro`, `src/pages/index.astro` — `style=""` pasados a clases de Tailwind.
- `src/pages/precios.astro` — aviso de tarifas no indicadas y seguros; fuera «Tarifas actualizadas».
- `public/.htaccess` — niega `config.php` por HTTP; cabeceras CSP (frame-ancestors, object-src,
  base-uri), X-Frame-Options, nosniff y Referrer-Policy.
- `public/js/admin.js` — nuevo: acciones del panel con atributos `data-*`.

### 2. Backend
- `php/admin/index.php`, `cookies.php`, `ip-check.php` — CSP propia y sin manejadores en línea.
- `php/cookies/log-consent.php` — CORS solo para los dominios propios; fuera el eco del Origin y
  `Allow-Credentials`.
- `php/config.template.php` — plantilla por entorno; `getDB()` ya no enseña el error con `?debug`.

### 3. Scripts y credenciales
- `scripts/config-servidor.mjs` — nuevo: genera el `api/config.php` de cada servidor solo con sus
  credenciales, guarda copia, se para si cambia la BD y comprueba la SHA-256.
- `scripts/deploy.mjs` — subida parcial también en PROD, credenciales del `.env` y `config.php`
  generado; fuera el aviso de Joomla, que ya no existe.
- `scripts/check-csp.mjs` — nuevo: comprobación de CSP al final de `npm run build`.
- `backup/backup-database.php`, `scripts/get-prod-images.mjs` — leen el `.env`; sin contraseñas.
- `crear-version.bat`, `package.json` — al día con lo anterior.

### 4. Especificaciones
- `specs/arquitectura.md`, `admin.md`, `backend.md` — CSP y configuración por entorno.
- `specs/deploy.md` — lo anterior, backups con `backup.bat`, estado de TEST e historial.
- `CLAUDE.md`, `versiones/README.md` — al día.

## Pruebas
TEST de Hostalia no pudo usarse: su FTP rechaza la contraseña (530), no se cambia porque TEST pasa
al VPS, y sirve una versión anterior sin CSP. Por eso CSP, admin y CORS se probaron en local, con
`php -S`, la misma estructura de PROD, las cabeceras de `.htaccess` imitadas y la BD de TEST:
- Chrome: portada con mapa, contacto con hCaptcha cargado y banner de cookies, sin violaciones de CSP.
- Admin (acceso de sesión e IP simulados solo en el montaje local): confirmar y cancelar borrado,
  «Usar mi IP», desplazamiento a IPs y filtro de periodo en cookies. Login real sin probar.
- CORS: un origen ajeno no recibe cabeceras; `fisiopilatesatlas.es` sí.
- `php -l` en todos los PHP y `npm run build` con la comprobación de CSP.
- El montaje local dejó 1 fila de prueba en `cookie_consent_logs` de la BD de TEST.

## Backups
- `backup/test/v1.4.0/` y `backup/prod/v1.4.0/` (estructura y completo).

## Notas de actualización
- `api/config.php` y `.htaccess` ya estaban en PROD desde el 2026-10-30, con la contraseña nueva de BD.
- `php/config.php` local está borrado; si hace falta, el de cada servidor se regenera desde el `.env`.

## Despliegue a PROD (2026-10-30)
- `node scripts/deploy.mjs prod`, una conexión FTP. Commit `50104b7`.
- Sumas SHA-256 descargadas de PROD: 43 de 43 ficheros iguales al build y a `php/`
  (incluido el `api/config.php` generado).
- Portada 200, precios 200 e igual al build, `/js/admin.js` 200, `/admin/` 403 por IP con su CSP.
- Chrome en `fisiopilatesatlas.es/contacto`: CSP presente y hCaptcha visible y cargado.

### Limitación detectada
nginx sirve directamente los ficheros estáticos, sin pasar por Apache: las cabeceras del
`.htaccess` (CSP frame-ancestors, X-Frame-Options, nosniff, Referrer-Policy) llegan a las respuestas
de PHP, pero no al HTML estático. La CSP por huellas del `<meta>` sí se aplica; `frame-ancestors`
no se puede poner en un `<meta>`, así que las páginas estáticas se pueden incrustar en otra web.
Además, nginx responde 405 a OPTIONS: el CORS de `log-consent.php` se probó solo en local. No afecta
al banner, que llama desde el mismo dominio.
