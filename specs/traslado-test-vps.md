# Traslado de TEST al VPS

**Estado**: 🟡 TEST montado y probado dentro del VPS (2026-10-30). Falta el registro DNS `test`, que crea César,
para sacar el certificado y verificar desde fuera.
**Modelo**: Asociación Aérea (`specs/traslado-test-vps.md` de su repo). Mismo VPS y mismas piezas.

---

## 1. Objetivo

Sacar TEST de Hostalia (`https://40749769.servicio-online.net`) y llevarlo al VPS de Hostinger (187.77.170.2) como
**`https://test.fisiopilatesatlas.es`**: allí se puede desplegar y probar sin la regla anti-bloqueos de Hostalia.
PRODUCCIÓN **sigue en Hostalia** y no se toca.

## 2. Decisiones

| # | Decisión | Qué se ha hecho |
|---|----------|-----------------|
| D1 | Nombre de TEST | **`test.fisiopilatesatlas.es`**. Registro A `test` → 187.77.170.2 en la zona de `ns10/11/12.servicio-online.net` (los DNS del dominio); lo crea César |
| D2 | BD de TEST | Contenedor `mysql:8.4` propio (`fa-test-db`), BD y usuario `fisiopilates_test` (el `_test` activa `IS_TEST_ENV`), contraseñas nuevas. **Copia de la BD de TEST de Hostalia** (volcado v1.4.0 del 30-10, sin conectar otra vez con Hostalia) **sin los registros de consentimientos de cookies** (llevan IP de visitantes). La vista `v_cookie_consent_daily` queda sin el `DEFINER` de Hostalia. `admins` y `contacto` iguales |
| D3 | Acceso a TEST | Sin contraseña, con `X-Robots-Tag: noindex, nofollow, noarchive` (Traefik) y `robots.txt` propio que lo bloquea todo. El panel sigue limitado por `admin_ips`: se añade la IP actual de la oficina (`83.38.56.249`) solo en TEST |
| D4 | `api/config.php` del servidor | Lo genera `scripts/config-servidor.mjs test` en `/opt/fisiopilates-test/env/config.php` (`root:www-data 640`), fuera de `site/`, y se monta de solo lectura como `api/config.php`. Solo las claves de TEST, con `DB_HOST=db` y un `APP_SECRET` nuevo. La ruta para el PHP no cambia, así que PROD no se toca |
| D5 | Correo de TEST | Mismo SMTP de Hostalia, solo a `cppleal@gmail.com` y con prefijo `[TEST]`, como hasta ahora. Separar la cuenta sigue en Pendientes |
| D6 | Acceso a la BD desde el PC | MySQL publicado **solo en el loopback del VPS** (`127.0.0.1:33309`). `scripts/tunel-test.mjs` abre el túnel; `backup/backup-database.php` lo abre y lo cierra solo |
| D7 | TEST viejo de Hostalia | Deja de desplegarse. **Sin aviso en su portada**: su FTP rechaza la contraseña (530) desde el 30-10. Borrarlo (`/httpdocs` y la BD `10067489_fisiopilates_TEST`) lo decide César; propuesta: 2026-11-13. Sus credenciales pasan a `TEST_HOSTALIA_*` en el `.env` local |

## 3. Situación de partida

| Elemento | Dato |
|----------|------|
| Código | Astro estático (`dist/`) + PHP: `api/contacto.php` (hCaptcha + SMTP), `api/cookies/`, panel `admin/` (PDO MySQL) |
| Lo que lee el PHP | `api/config.php`, generado por entorno desde la v1.4.0 |
| Escrituras | Ningún fichero: todo va a la BD |
| BD | MySQL 8.4 de Hostalia: 4 tablas y la vista `v_cookie_consent_daily` (unos pocos KB) |
| Despliegue | `scripts/deploy.mjs` por FTP; `deploy/deploy-local.bat` con WinSCP |

## 4. Arquitectura destino

```
Internet ──► Traefik (VPS :443, cert Let's Encrypt test.fisiopilatesatlas.es)
                │  red docker «fisiopilates_test»
                ▼
     fa-test-web (php:8.4-apache)                       fa-test-db (mysql:8.4)
     /var/www/html                ◄── /opt/fisiopilates-test/site            volumen fa_test_mysql
     /var/www/html/api/config.php ◄── /opt/fisiopilates-test/env/config.php  127.0.0.1:33309 (solo loopback)
     /var/www/html/robots.txt     ◄── /opt/fisiopilates-test/robots.txt
```

Ficheros en Git: `deploy/vps-test/`. Solo en el VPS: `env/config.php` y `env/db.env` (contraseñas de MySQL; las
mismas que `DB_PASS_TEST` y `DB_ROOT_PASS_TEST` del `.env` local).

## 5. Pasos

### Fase 0 — Copias
- [x] `../backups/pre-vps_2026-10-30/` (fuera de Git): BD de TEST completa y `.env` local anterior

### Fase 1 — Código
- [x] `php/contacto.php` y `php/cookies/log-consent.php`: URL de TEST nueva
- [x] `scripts/vps.mjs`, `scripts/tunel-test.mjs`, `scripts/verificar-test.mjs`
- [x] `scripts/deploy.mjs`: `test` → VPS (tar por una conexión SSH y SHA-256 de cada fichero); `prod` sin cambios
- [x] `scripts/config-servidor.mjs`: `test` → VPS por SSH (`DB_HOST=db`); el `config.php` de PROD sale idéntico, misma SHA-256
- [x] `backup/backup-database.php`: TEST por el túnel
- [x] `deploy/deploy-local.bat`: `test` se para y remite a `deploy.mjs`

### Fase 2 — VPS
- [x] `/opt/fisiopilates-test` con `site/`, `env/config.php`, `env/db.env`; contenedores arrancados con `FA_TRAEFIK=false`
- [x] BD importada; IP de la oficina añadida a `admin_ips`
- [x] Deploy completo: 42 ficheros, SHA-256 comprobada en el servidor

### Fase 3 — Pruebas dentro del VPS (cabecera `Host`)
- [x] Las 7 páginas, URL limpias y 404; cookies en la BD nueva; formulario sin captcha rechazado
- [x] **`api/config.php` → 403 sin mostrar nada en un Apache real**: la regla de `.htaccess` que en Hostalia no se puede pedir
- [x] `.htaccess` → 403; `.env`, `package.json` → 404
- [x] Las cabeceras de seguridad del `.htaccess` llegan también al HTML (en PROD, nginx las pierde)
- [x] IP real por `X-Forwarded-For`: la de la oficina ve el login, otra recibe 403
- [x] Envío real por el SMTP de Hostalia desde el contenedor (`SmtpMailer::send` → `true`)
- [x] `php backup/backup-database.php test` por el túnel; el túnel se cierra al terminar

### Fase 4 — DNS, certificado y pruebas desde fuera
- [ ] César crea el registro A `test` → 187.77.170.2
- [ ] `docker compose up -d` (sin `FA_TRAEFIK=false`): certificado de Let's Encrypt
- [ ] `node scripts/verificar-test.mjs`
- [ ] El widget de hCaptcha carga en `https://test.fisiopilatesatlas.es/contacto` sin error de dominio
- [ ] César envía el formulario con hCaptcha, confirma la recepción del correo y entra en el panel

### Fase 5 — TEST viejo y seguimiento
- [x] Pendiente en `control_VPS/CLAUDE.md` para el inventario y la vigilancia
- [x] Fila en la página «TEST al VPS» y en `Migracion TEST al VPS - estado.md`

## 6. Uso diario

```bash
npm run build && node scripts/deploy.mjs   # build + deploy a TEST (VPS)
node scripts/deploy.mjs test api/contacto.php   # solo ese fichero
node scripts/verificar-test.mjs            # comprobar TEST de punta a punta
backup\backup.bat test vX.Y.Z              # copia de la BD de TEST (abre el túnel)
node scripts/tunel-test.mjs                # túnel a la BD (127.0.0.1:33309)
node scripts/config-servidor.mjs test      # regenerar el config.php del VPS
```

Borrar un fichero del servidor de TEST: por SSH, en `/opt/fisiopilates-test/site/`. `deploy.mjs` solo sube.

## 7. Vuelta atrás

- El TEST viejo de Hostalia sigue funcionando con su BD mientras no se borre, pero no se puede desplegar en él
  (FTP 530). Credenciales: `TEST_HOSTALIA_*` del `.env` local. `.env` anterior en `../backups/pre-vps_2026-10-30/`.
- En el VPS: `cd /opt/fisiopilates-test && docker compose down` (el volumen de la BD se conserva).

## 8. Historial

| Fecha | Cambio |
|-------|--------|
| 2026-10-30 | Fases 0 a 3 hechas: TEST montado y probado dentro del VPS. A la espera del DNS |
