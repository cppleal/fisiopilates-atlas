# Backend PHP — Fisiopilates Atlas

## Archivo de configuración: `php/config.php`

Cargado con `require_once` en todos los endpoints PHP.

### Constantes definidas

| Constante | Valor TEST | Descripción |
|-----------|-----------|-------------|
| `DB_HOST` | `PMYSQL168.dns-servicio.com` | Host MySQL |
| `DB_NAME` | `10067489_fisiopilates_TEST` | Nombre BD |
| `DB_USER` | `cppleal_fisiopilates` | Usuario BD |
| `DB_PASS` | *(en .env)* | Contraseña BD |
| `APP_SECRET` | `atlas_2025_s3cr3t_k3y_*` | Clave sesiones admin |
| `HCAPTCHA_SITE_KEY` | *(en config.php)* | Clave pública hCaptcha |
| `HCAPTCHA_SECRET` | *(en config.php)* | Clave privada hCaptcha |
| `SMTP_HOST` | `smtp.servidor-correo.net` | Servidor SMTP |
| `SMTP_PORT` | `587` | Puerto SMTP (STARTTLS) |
| `SMTP_USER` | `envios@fisiopilatesatlas.es` | Usuario SMTP |
| `SMTP_FROM` | `envios@fisiopilatesatlas.es` | Remitente emails |
| `IS_TEST_ENV` | `true` | Auto-detectado por nombre BD |
| `CONTACT_TO` | `cppleal@gmail.com` (TEST) | Destinatario contacto |

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

### Cabeceras
- `Content-Type: application/json`
- `Access-Control-Allow-Origin` dinámico (soporta preflight OPTIONS)

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

### Acciones válidas
| Action | Descripción |
|--------|-------------|
| `accept_all` | Usuario aceptó todas las cookies |
| `reject_all` | Usuario rechazó cookies opcionales |
| `save_preferences` | Usuario guardó selección personalizada |
| `withdraw` | Usuario retiró consentimiento previamente dado |

### Respuesta
```json
{ "success": true, "message": "Preferencias registradas" }
```

---

## SMTP: `php/lib/SmtpMailer.php`

Implementación SMTP nativa (sin PHPMailer / dependencias externas).
- Soporta STARTTLS (puerto 587)
- Soporte adjuntos
- Cabeceras anti-spam (MIME, Content-Type)
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
| `motivo` | VARCHAR(100) NOT NULL | Motivo de contacto (select) |
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

**Usuario por defecto:** `admin` / `atlas2025` (hash generado con `password_hash()` PHP)

### Tabla `cookie_consent_logs`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | — |
| `session_token` | VARCHAR(255) NULL | Token UUID de sesión del visitante |
| `action` | VARCHAR(50) NOT NULL | Acción realizada |
| `necessary` | TINYINT(1) DEFAULT 1 | Siempre 1 (cookies necesarias) |
| `analytics` | TINYINT(1) DEFAULT 0 | Cookies analíticas aceptadas |
| `functional` | TINYINT(1) DEFAULT 0 | Cookies funcionales aceptadas |
| `ip_address` | VARCHAR(45) NULL | IP completa del visitante |
| `user_agent` | VARCHAR(100) NULL | Navegador / SO resumido (ej: "Chrome / Windows") |
| `page_url` | VARCHAR(500) NULL | URL de la página donde se dio el consentimiento |
| `consent_version` | VARCHAR(10) DEFAULT '1.0' | Versión de la política de cookies |
| `created_at` | TIMESTAMP DEFAULT NOW() | Fecha/hora del registro |

> **Nota RGPD:** Se almacena la IP completa del visitante (decisión del responsable del tratamiento). Purga automática a los 13 meses (art. 5.1.e RGPD). Se registra solo "Chrome / Windows" como user_agent, no el UA completo.

---

## Instalación inicial (`php/install.php`)

Script de un único uso que:
1. Crea las 3 tablas si no existen
2. Inserta el admin por defecto (`admin`/`atlas2025`)
3. Muestra confirmación HTML

**IMPORTANTE:** Eliminar del servidor tras ejecutarlo.

Acceso: `https://dominio/install.php` (se sube con flag `--install` en deploy)

---

## Seguridad

- Consultas parametrizadas (PDO prepared statements) → sin SQLi
- hCaptcha antes de procesar formulario → anti-spam/bot
- Sesión PHP con `session_regenerate_id()` en login admin
- Password con `password_hash()` / `password_verify()` bcrypt
- `.htaccess` deniega acceso a `.env`, `.sql`, `.md`, `.json`, `.log`
- Headers CORS restrictivos (solo orígenes conocidos en producción)
