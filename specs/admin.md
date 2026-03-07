# Panel de Administración — Fisiopilates Atlas

## Acceso

| Campo | Valor |
|-------|-------|
| URL TEST | `https://40749769.servicio-online.net/admin/` |
| URL PROD | `https://fisiopilatesatlas.es/admin/` |
| Usuario | `admin` |
| Contraseña | `atlas2025` |

> Sesión PHP con cookie de sesión. Requiere HTTPS en producción.

---

## Archivo: `php/admin/index.php`

Archivo monolítico PHP que gestiona:
- Login / logout (sesiones PHP)
- Dashboard principal con tarjetas de estadísticas
- Lista de mensajes de contacto
- Marcado de mensajes como leídos

### Vistas dentro de `index.php`

#### Vista Login
- Formulario usuario + contraseña
- Verificación con `password_verify()` bcrypt
- `session_regenerate_id(true)` tras login correcto
- Errores genéricos (no revela si usuario o contraseña es incorrecto)

#### Vista Dashboard
Topbar con navegación:
- **Mensajes** (vista principal)
- **Cookies RGPD** → enlace a `/admin/cookies.php`

**Tarjetas de resumen:**
| Tarjeta | Dato | Enlace |
|---------|------|--------|
| Total mensajes | `COUNT(*)` tabla `contacto` | — |
| No leídos | `COUNT(*) WHERE leido=0` | — |
| Hoy | `COUNT(*) WHERE DATE(created_at)=CURDATE()` | — |
| Cookies RGPD | Total registros `cookie_consent_logs` | `/admin/cookies.php` |

**Lista de mensajes:**
- Columnas: Fecha, Nombre, Email, Teléfono, Motivo, Mensaje (truncado), Estado
- Botón "Marcar leído" (POST a sí mismo con `action=mark_read&id=X`)
- Mensajes no leídos destacados con fondo teal suave
- Ordenados por `created_at DESC`

---

## Archivo: `php/admin/cookies.php`

Vista de registros de consentimiento de cookies.

**Require path:** `require_once __DIR__ . '/../api/cookies/CookieConsentService.php';`

### Funcionalidades

#### Panel de estadísticas (período seleccionable)
Filtros de período: Hoy | Semana | Mes (defecto) | Año | Todos

| Métrica | Descripción |
|---------|-------------|
| Total interacciones | Todos los registros del período |
| Aceptar todo | `action = 'accept_all'` |
| Solo necesarias | `action = 'reject_all'` |
| Personalizado | `action = 'save_preferences'` |
| % Analíticas | `AVG(analytics) * 100` |
| % Funcionales | `AVG(functional) * 100` |

Barras de porcentaje visuales para analíticas y funcionales.

#### Filtros de historial
- Por acción (select)
- Por fecha desde / hasta

#### Tabla de registros
Paginación: 25 registros por página

| Columna | Fuente |
|---------|--------|
| Fecha | `created_at` formateada `d/m/Y H:i` |
| Token sesión | `session_token` (truncado a 20 chars) |
| Acción | Label en español |
| Necesarias | Siempre Sí |
| Analíticas | Sí / No |
| Funcionales | Sí / No |
| IP | `ip_address` completa |
| Navegador | `user_agent` ("Chrome / Windows") |
| Página | `page_url` |
| Versión | `consent_version` |

#### Exportación CSV
Botón "Exportar CSV" → descarga `cookies_rgpd_YYYY-MM-DD.csv`
- BOM UTF-8 para compatibilidad Excel
- Separador punto y coma (`;`)
- Columnas: Fecha, Token sesión, Acción, Necesarias, Analíticas, Funcionales, IP, Página, Versión

---

## Seguridad del panel

- Todas las vistas verifican sesión activa (`$_SESSION['admin_id']`)
- Redirección a login si no autenticado
- Queries con PDO prepared statements
- Sin exposición de errores de BD al usuario
