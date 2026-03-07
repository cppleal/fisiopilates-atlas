# Sistema de Cookies y RGPD — Fisiopilates Atlas

Implementación completa de consentimiento de cookies conforme a RGPD (UE 2016/679) y LSSI-CE.

---

## Arquitectura

```
public/js/cookie-consent.js     → Banner frontend (UI + lógica)
public/css/cookie-consent.css   → Estilos del banner
php/cookies/log-consent.php     → API endpoint (POST)
php/cookies/CookieConsentService.php → Lógica de negocio
php/admin/cookies.php           → Vista admin (registros + estadísticas)
```

---

## Frontend: `cookie-consent.js`

### Funcionamiento
1. Al cargar la página, comprueba si existe cookie `cookie_consent` en el navegador
2. Si no existe → muestra el banner de consentimiento
3. El usuario elige una de las 3 opciones → se llama a la API y se guarda la cookie

### Acciones disponibles
| Acción | Comportamiento |
|--------|---------------|
| **Aceptar todo** | analytics=true, functional=true |
| **Solo necesarias** | analytics=false, functional=false |
| **Personalizar** | Abre panel con checkboxes individuales |

### Cookie del navegador
- Nombre: `cookie_consent`
- Duración: 13 meses
- Valor: JSON con `{ action, analytics, functional, timestamp, version }`

### Session token
- UUID v4 generado en JS (`crypto.randomUUID()`)
- Se guarda en `sessionStorage` para agrupar acciones de una misma visita
- Se envía en cada llamada a la API

### Llamada a la API
```javascript
fetch('/api/cookies/log-consent.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    session_token: sessionToken,
    action: 'accept_all' | 'reject_all' | 'save_preferences' | 'withdraw',
    analytics: true | false,
    functional: true | false
  })
})
```

---

## Backend: `CookieConsentService.php`

### Métodos públicos

#### `logConsent(sessionToken, action, analytics, functional): bool`
Registra una acción en `cookie_consent_logs`.
- Guarda IP completa del visitante (soporta Cloudflare: `HTTP_CF_CONNECTING_IP`)
- Guarda tipo de navegador/SO resumido (no el UA completo)
- Ejecuta purga automática (1% de probabilidad)

#### `getConsentStats(period): array`
Estadísticas para el panel admin.
- Períodos: `today | week | month | year | all`
- Devuelve: total, aceptar_todo, rechazar_todo, personalizado, pct_analytics, pct_functional

#### `getConsentHistory(page, perPage, filters): array`
Historial paginado con filtros opcionales.
- Filtros: `action`, `date_from`, `date_to`
- Paginación: 25 registros/página por defecto
- Devuelve: `{ data, total, page, per_page, total_pages }`

#### `exportToCSV(filters): string`
Exporta registros a CSV (BOM UTF-8, separador `;`).
- Filtros: `date_from`, `date_to`
- Acciones en español ("Aceptar todo", "Solo necesarias", etc.)

### Métodos privados

#### `purgeOldRecords(): void`
- Elimina registros con más de 13 meses (RGPD art. 5.1.e)
- Solo se ejecuta con probabilidad 1% por request (no penaliza rendimiento)

#### `getRawIp(): string`
- Devuelve la IP real del cliente
- Cabeceras comprobadas en orden: `HTTP_CF_CONNECTING_IP` → `HTTP_X_FORWARDED_FOR` → `REMOTE_ADDR`
- Valida con `filter_var(FILTER_VALIDATE_IP)`

#### `getBrowserInfo(): string`
- Devuelve formato "Navegador / SO" (ej: "Chrome / Windows")
- No almacena el user-agent completo (privacidad)

---

## Cumplimiento RGPD / LSSI-CE

| Requisito | Implementación |
|-----------|---------------|
| Consentimiento previo | Banner bloquea cookies analíticas/funcionales hasta aceptación |
| Granularidad | Panel permite aceptar/rechazar por categoría |
| Retirada del consentimiento | Acción `withdraw` disponible |
| Registro del consentimiento | Tabla `cookie_consent_logs` con fecha, acción, token, IP |
| Conservación limitada | Purga automática a 13 meses (art. 5.1.e) |
| Información clara | Política de cookies en `/cookies` enlazada desde el banner |
| IP | Se almacena IP completa (decisión del responsable) |
| User-agent | Solo "Navegador / SO", no UA completo |

### Categorías de cookies declaradas

| Categoría | Siempre activa | Descripción |
|-----------|---------------|-------------|
| **Necesarias** | Sí | Sesión, CSRF, cookie de consentimiento |
| **Analíticas** | No (opt-in) | Comportamiento de visita |
| **Funcionales** | No (opt-in) | Preferencias del usuario |

### Versión de política
Versión actual: `1.0` (campo `consent_version` en registros).
Al cambiar la política de cookies, incrementar versión → los usuarios verán el banner de nuevo.

---

## Flujo completo de un usuario nuevo

```
1. Usuario llega al sitio
2. JS comprueba cookie "cookie_consent" → no existe
3. Se muestra el banner (overlay semitransparente)
4. Usuario elige:
   ├─ "Aceptar todo" → POST /api/cookies/log-consent.php {action:"accept_all", analytics:true, functional:true}
   ├─ "Solo necesarias" → POST {action:"reject_all", analytics:false, functional:false}
   └─ "Personalizar" → abre panel → guarda selección → POST {action:"save_preferences", analytics:X, functional:X}
5. API guarda registro en BD
6. JS guarda cookie "cookie_consent" (13 meses)
7. Banner desaparece
8. En visitas posteriores: cookie existe → no se muestra banner
```
