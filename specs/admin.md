# Panel de Administración — Fisiopilates Atlas

## Acceso

| Campo | Valor |
|-------|-------|
| URL TEST | `https://40749769.servicio-online.net/admin/` |
| URL PROD | `https://fisiopilatesatlas.es/admin/` |
| Usuario | `admin` |
| Contraseña por defecto | `atlas2025` (cambiar tras primer acceso) |

> Sesión PHP con cookie de sesión. Requiere HTTPS en producción.

---

## Protección por IP

El acceso al panel está restringido por dirección IP mediante `php/admin/ip-check.php`.

### Comportamiento
| Situación | Acceso |
|-----------|--------|
| Tabla `admin_ips` vacía | Libre (estado inicial, sin restricción) |
| IP en la lista | Permitido |
| IP no en la lista | Bloqueado — HTTP 403 con mensaje y la IP denegada |

### Funcionamiento técnico
- La verificación se ejecuta en **cada request** al panel (antes del login)
- Función `getClientIP()` → lee `$_SERVER['REMOTE_ADDR']`
- Función `checkAdminIP(PDO $pdo)` → consulta tabla `admin_ips`
- Ambas funciones están en `php/admin/ip-check.php`
- Incluido con `require_once` en `index.php` y `cookies.php`

### Activación
La protección se activa automáticamente en cuanto se añade la primera IP a la tabla. Mientras la tabla esté vacía, el acceso es libre (comportamiento de instalación inicial).

---

## Archivos del panel

| Archivo | Descripción |
|---------|-------------|
| `php/admin/index.php` | Login + Dashboard + Gestión IPs + Cambio contraseña |
| `php/admin/cookies.php` | Vista registros cookie consent (RGPD) |
| `php/admin/ip-check.php` | Helper de verificación de IP por request |

---

## `php/admin/index.php`

Archivo monolítico PHP que gestiona:
- Verificación de IP (antes del login)
- Login / logout (sesiones PHP)
- Dashboard principal con tarjetas de estadísticas
- Lista de mensajes de contacto con paginación
- Marcado de mensajes como leídos / eliminación
- Gestión de IPs permitidas
- Cambio de contraseña del admin

### Vista Login
- Formulario usuario + contraseña
- Verificación con `password_verify()` bcrypt
- Errores genéricos (no revela si falla usuario o contraseña)

### Vista Dashboard

**Topbar:**
- Hola, {nombre admin}
- Enlace a Cookies RGPD
- Ver web
- Cerrar sesión

**Tarjetas de resumen:**
| Tarjeta | Dato |
|---------|------|
| Mensajes totales | `COUNT(*)` tabla `contacto` |
| Sin leer | `COUNT(*) WHERE leido=0` |
| Cookies RGPD | Enlace a `/admin/cookies.php` |
| IPs permitidas | `COUNT(*)` tabla `admin_ips` (naranja si vacía) |

**Lista de mensajes:**
- Columnas: Estado, Fecha, Nombre, Email/Tel, Motivo, Mensaje (truncado), Acciones
- Botón "Marcar leído" + "Eliminar" por mensaje
- Mensajes no leídos destacados con fondo teal suave
- Paginación: 20 por página, ordenados por `created_at DESC`

### Sección: Control de acceso por IP

**Tabla de IPs registradas:**
- Muestra IP, descripción, fecha de alta
- Badge "Tu IP actual" sobre la IP de la sesión activa
- Botón "Eliminar" con aviso de advertencia si es la IP actual

**Formulario añadir IP:**
- Campo IP pre-rellenado con la IP actual detectada
- Campo descripción (opcional, ej: "Oficina", "Casa")
- Botón "Añadir IP"
- Validación: `filter_var($ip, FILTER_VALIDATE_IP)`

**Acciones GET:**
- `?action=delete_ip&id=X` → elimina IP, redirige a `#ips`

### Sección: Cambiar contraseña

Formulario con tres campos obligatorios:
1. **Contraseña actual** — verificada con `password_verify()` antes de cambiar
2. **Nueva contraseña** — mínimo 8 caracteres
3. **Confirmar nueva contraseña** — debe coincidir con el campo anterior

---

## `php/admin/cookies.php`

Vista de registros de consentimiento de cookies.

**Includes:** `config.php`, `ip-check.php`, `CookieConsentService.php`

### Funcionalidades

#### Panel de estadísticas (período seleccionable)
Filtros: Hoy | Semana | Mes (defecto) | Año | Todos

| Métrica | Descripción |
|---------|-------------|
| Total interacciones | Todos los registros del período |
| Aceptar todo | `action = 'accept_all'` |
| Solo necesarias | `action = 'reject_all'` |
| Personalizado | `action = 'save_preferences'` |
| % Analíticas | `AVG(analytics) * 100` |
| % Funcionales | `AVG(functional) * 100` |

#### Tabla de registros
Paginación: 25 registros por página

| Columna | Fuente |
|---------|--------|
| Fecha | `created_at` formateada `d/m/Y H:i` |
| Acción | Label en español |
| Analíticas | Sí / No |
| Funcionales | Sí / No |
| IP | `ip_address` |
| Navegador | `user_agent` |
| Página | `page_url` |

#### Exportación CSV
Botón "Descargar CSV" → `cookies_YYYY-MM-DD.csv`
- BOM UTF-8 para compatibilidad Excel
- Filtros activos aplicados a la exportación

---

## Seguridad del panel

- **Protección por IP** en cada request (antes incluso del login)
- Verificación de sesión activa (`$_SESSION['admin_id']`) en todas las vistas
- Redirección a login si no autenticado
- Queries con PDO prepared statements (sin SQLi)
- Cambio de contraseña requiere verificar la contraseña actual
- Sin exposición de errores de BD al usuario final
