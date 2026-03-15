# Backend PHP — Fisiopilates Atlas

## Archivo de configuración: `php/config.php`

Cargado con `require_once` en todos los endpoints PHP.

### Detección automática de entorno

`config.php` detecta el entorno por el hostname del servidor:

```php
$_host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
$_isProd = (strpos($_host, 'fisiopilatesatlas.es') !== false);
```

| Entorno | Host detectado | BD utilizada |
|---------|---------------|--------------|
| PRODUCCIÓN | `fisiopilatesatlas.es` | `PMYSQL117.dns-servicio.com / 9702349_fisio` |
| TEST | cualquier otro | `PMYSQL168.dns-servicio.com / 10067489_fisiopilates_TEST` |

Las variables de entorno (`DB_HOST`, `DB_NAME`, etc.) tienen prioridad sobre la auto-detección si están definidas.

### Constantes definidas

| Constante | TEST | PROD | Descripción |
|-----------|------|------|-------------|
| `DB_HOST` | `PMYSQL168.dns-servicio.com` | `PMYSQL117.dns-servicio.com` | Host MySQL |
| `DB_NAME` | `10067489_fisiopilates_TEST` | `9702349_fisio` | Nombre BD |
| `DB_USER` | `cppleal_fisiopilates` | `cppleal-fisio` | Usuario BD |
| `APP_SECRET` | `atlas_2025_s3cr3t_k3y_*` | igual | Clave sesiones admin |
| `HCAPTCHA_SITE_KEY` | `1d9426de-...` | igual | Clave pública hCaptcha |
| `IS_TEST_ENV` | `true` | `false` | Auto-detectado por hostname |
| `CONTACT_TO` | `cppleal@gmail.com` | `fisiopilates.atlas@gmail.com` | Destinatario contacto |
| `SMTP_HOST` | `smtp.servidor-correo.net` | igual | Servidor SMTP |
| `SMTP_PORT` | `587` | igual | Puerto SMTP (STARTTLS) |
| `SMTP_USER` | `envios@fisiopilatesatlas.es` | igual | Usuario SMTP |

### Función `getDB(): PDO`
- Singleton PDO con `ERRMODE_EXCEPTION`
- Charset `utf8mb4`
- No expone errores en producción (solo con `?debug`)

---

## API: Formulario de contacto

**Archivo:** `php/contacto.php`
**URL desplegada:** `/api/contacto.php`
**Método:** `POST`

### Flujo
1. Verificación hCaptcha (POST a `hcaptcha.com/siteverify`)
2. Validación campos: nombre, email, motivo, mensaje (mínimo 10 chars)
3. Guarda en tabla `contacto` de la BD
4. Envía email al destinatario configurado (`CONTACT_TO`)
5. Devuelve JSON `{ success: true|false, message: "..." }`

### Email en TEST
- Destinatario: `cppleal@gmail.com`
- Asunto: `[TEST] Nuevo mensaje de contacto - {nombre}`

### Email en PROD
- Destinatario: `fisiopilates.atlas@gmail.com`
- Sin prefijo `[TEST]`

---

## API: Registro de consentimiento de cookies

**Archivo:** `php/cookies/log-consent.php`
**URL desplegada:** `/api/cookies/log-consent.php`
**Método:** `POST`

### Payload JSON esperado
```json
{
  "session_token": "uuid-v4-string",
  "action": "accept_all | reject_all | save_preferences | withdraw",
  "analytics": true | false,
  "functional": true | false
}
```

---

## SMTP: `php/lib/SmtpMailer.php`

Implementación SMTP nativa (sin PHPMailer / dependencias externas).
- Soporta STARTTLS (puerto 587)
- Solo se instancia desde `contacto.php`

---

## Esquema de Base de Datos

### Tabla `contacto`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | — |
| `nombre` | VARCHAR(255) NOT NULL | Nombre del remitente |
| `email` | VARCHAR(255) NOT NULL | Email del remitente |
| `telefono` | VARCHAR(50) DEFAULT '' | Teléfono (opcional) |
| `motivo` | VARCHAR(100) NOT NULL | Motivo de contacto |
| `mensaje` | TEXT NOT NULL | Cuerpo del mensaje |
| `ip` | VARCHAR(45) DEFAULT '' | IP del visitante |
| `leido` | TINYINT(1) DEFAULT 0 | 0=no leído, 1=leído |
| `created_at` | TIMESTAMP DEFAULT NOW() | Fecha/hora del mensaje |

### Tabla `admins`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | — |
| `username` | VARCHAR(100) UNIQUE NOT NULL | Login |
| `password` | VARCHAR(255) NOT NULL | Hash bcrypt PHP |
| `nombre` | VARCHAR(255) NOT NULL | Nombre visible |
| `email` | VARCHAR(255) DEFAULT '' | Email del admin |
| `last_login` | TIMESTAMP NULL | Último acceso |
| `created_at` | TIMESTAMP DEFAULT NOW() | Creación |

**Usuario por defecto:** `admin` / `atlas2025` (cambiar tras primer acceso)

### Tabla `admin_ips`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | — |
| `ip_address` | VARCHAR(45) UNIQUE NOT NULL | Dirección IP permitida (IPv4 o IPv6) |
| `descripcion` | VARCHAR(255) DEFAULT '' | Etiqueta descriptiva (ej: "Oficina") |
| `created_at` | TIMESTAMP DEFAULT NOW() | Fecha de alta |

> Si la tabla está vacía, el acceso al admin es libre. En cuanto se añade una IP, solo esa IP (y las que se añadan) pueden acceder.

### Tabla `cookie_consent_logs`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | — |
| `session_token` | VARCHAR(64) NULL | Token UUID de sesión del visitante |
| `action` | VARCHAR(50) NOT NULL | Acción realizada |
| `analytics` | TINYINT(1) DEFAULT 0 | Cookies analíticas aceptadas |
| `functional` | TINYINT(1) DEFAULT 0 | Cookies funcionales aceptadas |
| `necessary` | TINYINT(1) DEFAULT 1 | Siempre 1 (cookies necesarias) |
| `ip_address` | VARCHAR(45) NULL | IP del visitante |
| `user_agent` | TEXT NULL | Navegador / SO |
| `page_url` | VARCHAR(500) NULL | URL donde se dio el consentimiento |
| `consent_version` | VARCHAR(10) NULL | Versión de la política de cookies |
| `created_at` | TIMESTAMP DEFAULT NOW() | Fecha/hora del registro |

---

## Instalación inicial (`php/install.php`)

Script de un único uso que:
1. Crea las 4 tablas si no existen: `contacto`, `admins`, `admin_ips`, `cookie_consent_logs`
2. Registra la IP del instalador como primera IP permitida (`REMOTE_ADDR`)
3. Inserta el admin por defecto (`admin`/`atlas2025`)
4. Muestra confirmación HTML con la IP registrada

**IMPORTANTE:** Eliminar del servidor tras ejecutarlo.

Acceso: `https://dominio/install.php` (se sube con flag `--install` en deploy)

---

## Seguridad

- Consultas parametrizadas (PDO prepared statements) → sin SQLi
- hCaptcha antes de procesar formulario → anti-spam/bot
- Sesión PHP con `session_regenerate_id()` en login admin
- Password con `password_hash()` / `password_verify()` bcrypt
- Protección de acceso al admin por IP (`admin_ips`)
- `.htaccess` con `Options -Indexes` → sin listado de directorios
- Headers CORS restrictivos en endpoints PHP
