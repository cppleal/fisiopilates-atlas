# Arquitectura — Fisiopilates Atlas

## Stack técnico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Frontend | Astro (static output) | 5.x |
| CSS | Tailwind CSS | 4.x |
| Backend | PHP | 8.x |
| Base de datos | MySQL | 5.7+ / 8.x |
| Servidor web | Apache + mod_rewrite | PROD en Hostalia; TEST en Docker en el VPS (`traslado-test-vps.md`) |
| Deploy | PROD: FTP (basic-ftp Node.js / WinSCP), puerto 21. TEST: SSH al VPS | |

> El sitio genera HTML estático en `dist/`. PHP solo existe para el formulario de contacto, el panel admin y el registro de cookies.

---

## Estructura del proyecto

```
new_fisio/
├── src/
│   ├── pages/          → Páginas Astro
│   ├── components/     → Componentes reutilizables
│   │   ├── Header.astro
│   │   ├── Footer.astro
│   │   ├── Hero.astro
│   │   └── SectionTitle.astro
│   ├── layouts/
│   │   └── Layout.astro → Layout base (head, header, footer, cookie banner)
│   └── styles/
│       └── global.css  → Variables CSS + Tailwind
├── php/
│   ├── config.template.php → Plantilla del api/config.php (lo genera scripts/config-servidor.mjs por entorno)
│   ├── contacto.php        → API formulario de contacto
│   ├── install.php         → Script creación tablas (ejecutar 1 vez, luego borrar)
│   ├── lib/
│   │   └── SmtpMailer.php  → Clase envío SMTP nativo
│   ├── admin/
│   │   ├── index.php       → Panel admin (login + dashboard + IPs + contraseña)
│   │   ├── cookies.php     → Vista registros cookie consent
│   │   └── ip-check.php    → Protección acceso por IP
│   └── cookies/
│       ├── log-consent.php         → API endpoint registro consentimiento
│       └── CookieConsentService.php → Servicio RGPD (lógica de negocio)
├── public/
│   ├── .htaccess           → Clean URLs, caché, ErrorDocument 404, cabeceras de seguridad
│   ├── images/             → Imágenes del sitio (JPG, WebP)
│   ├── css/
│   │   └── cookie-consent.css
│   └── js/
│       ├── cookie-consent.js
│       └── admin.js        → Acciones del panel admin (sustituye a los onclick=)
├── images/                 → Imágenes originales de alta calidad (no en git)
├── scripts/
│   ├── deploy.mjs          → Deploy (Node.js) — método principal: TEST al VPS, PROD por FTP
│   ├── vps.mjs             → Acceso SSH al VPS (plink)
│   ├── tunel-test.mjs      → Túnel a la BD de TEST
│   ├── verificar-test.mjs  → Comprueba TEST de punta a punta (nunca contra Hostalia)
│   └── check-csp.mjs       → Comprueba la CSP de dist/ y del admin (va dentro de npm run build)
├── deploy/                 → Scripts WinSCP (.bat, solo PROD) y deploy/vps-test/ (Docker de TEST)
│   ├── deploy-local.bat
│   ├── deploy-config.bat   → Credenciales reales (no en git)
│   └── get-prod-images.bat
├── backup/                 → Scripts y volcados de BD
│   ├── backup/             → Scripts de backup PHP
│   ├── test/               → Volcados TEST (no en git)
│   └── prod/               → Volcados PROD (no en git)
├── specs/                  → Esta documentación técnica
├── versiones/              → Changelogs por versión
├── VERSION                 → Versión actual (formato X.Y.Z)
├── package.json
└── astro.config.mjs
```

---

## Paleta de colores

| Token | Hex | Uso |
|-------|-----|-----|
| `--color-primary` | `#1B6B6E` | Color principal (teal profundo) |
| `--color-primary-light` | `#2D8A8E` | Hover, variantes claras |
| `--color-accent` | `#E07B39` | CTA, resaltados (naranja cálido) |
| `--color-bg-light` | `#F4F9F9` | Fondo secciones alternadas |
| `--color-border` | `#d1e7e7` | Bordes tarjetas |
| `--color-text-dark` | `#1a2e2e` | Texto principal |
| `--color-text-gray` | `#5a7a7a` | Texto secundario |

---

## Componentes principales

### `Layout.astro`
- Meta tags SEO (title, description, og:*)
- Google Fonts (Inter)
- Inclusión de `cookie-consent.css` y `cookie-consent.js`
- Header + slot + Footer

### `Header.astro`
- Logo + navegación principal
- Botón "Pedir cita" (CTA accent)
- Responsive: hamburger en móvil

### `Footer.astro`
- 3 columnas: info contacto | navegación | redes sociales
- Tel: 691 487 526 | Email: fisiopilates.atlas@gmail.com
- Dirección: c/Travesía de Alfredo Aleix, 1 — Carabanchel Alto, 28044 Madrid

### `Hero.astro`
- Props: `title`, `subtitle`, `ctaText`, `ctaHref`, `ctaSecondaryText`, `ctaSecondaryHref`, `backgroundImage`, `compact`
- Fondo imagen con overlay oscuro
- Prop `compact` para hero reducido (páginas interiores)

### `SectionTitle.astro`
- Props: `title`, `subtitle`, `centered`
- Con línea decorativa color primary

---

## Routing (Astro static + Apache)

Astro genera archivos `.html`. El `.htaccess` mapea rutas limpias:

```
/                   → /index.html
/fisioterapia       → /fisioterapia.html
/pilates            → /pilates.html
/precios            → /precios.html
/contacto           → /contacto.html
/privacidad         → /privacidad.html
/cookies            → /cookies.html
/404                → /404.html
/api/contacto.php   → PHP directo
/api/cookies/log-consent.php → PHP directo
/admin/             → /admin/index.php
/admin/cookies.php  → PHP directo
```

---

## Content Security Policy

Sin `'unsafe-inline'` en scripts: un script o un manejador `on*=` que alguien consiga meter en el HTML no se ejecuta.

- **Páginas Astro**: `experimental.csp` de Astro (en `astro.config.mjs`). Cada página lleva un `<meta http-equiv="content-security-policy">` con las huellas SHA-256 de sus scripts y estilos. Al ser una web estática no hay nonce.
  - Terceros permitidos: hCaptcha (`hcaptcha.com` y `*.hcaptcha.com` en script, style, frame y connect) y el iframe de Google Maps (`maps.google.com` y `www.google.com` en frame-src).
  - Prohibido en el HTML: atributos `style=""` (usar clases de Tailwind) y manejadores `on*=` (usar `addEventListener` en un `<script>`).
  - Un tercero nuevo (analítica, vídeo, fuentes externas…) hay que añadirlo en `astro.config.mjs` o lo bloqueará el navegador.
- **Admin PHP**: cada `<head>` lleva su propio `<meta>` con `script-src 'self'` (solo ficheros) y `style-src 'self' 'unsafe-inline'` (las vistas usan `<style>` y `style=""`). Las acciones van por atributos `data-*` que interpreta `public/js/admin.js`: `data-confirm`, `data-href`, `data-scroll-to`, `data-fill` + `data-value`, `data-nav-param`.
- **`public/.htaccess`**: cabecera `Content-Security-Policy` solo con lo que el `<meta>` no admite o vale para todo (`frame-ancestors 'self'; object-src 'none'; base-uri 'self'`), más `X-Frame-Options`, `X-Content-Type-Options: nosniff` y `Referrer-Policy: strict-origin-when-cross-origin`. El navegador aplica las dos políticas a la vez, por eso la de la cabecera no lleva `script-src` ni `style-src`.
- **`scripts/check-csp.mjs`** corre al final de `npm run build` y falla si una página no lleva el `<meta>`, si hay un script o estilo en línea sin huella, un `style=""` o un `on*=`, o un `on*=` o `<script>` en línea en el admin.

## Convenciones de código

- Componentes Astro: PascalCase (`Header.astro`)
- PHP: camelCase para métodos, PascalCase para clases
- CSS: Tailwind utilities + variables CSS personalizadas en `global.css`
- Imágenes: preferir WebP donde sea posible
- Commits: `[feature_slug] - descripción en español`
