# Arquitectura — Fisiopilates Atlas

## Stack técnico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Frontend | Astro (static output) | 5.x |
| CSS | Tailwind CSS | 4.x |
| Backend | PHP | 8.x |
| Base de datos | MySQL | 5.7+ / 8.x |
| Servidor web | Apache + mod_rewrite | Hostalia |
| Deploy | FTP (basic-ftp Node.js / WinSCP) | Puerto 21 |

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
│   ├── config.php          → Configuración BD/SMTP/hCaptcha (auto-detecta entorno)
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
│   ├── .htaccess           → Clean URLs, caché, ErrorDocument 404
│   ├── images/             → Imágenes del sitio (JPG, WebP)
│   ├── css/
│   │   └── cookie-consent.css
│   └── js/
│       └── cookie-consent.js
├── images/                 → Imágenes originales de alta calidad (no en git)
├── scripts/
│   └── deploy.mjs          → Deploy FTP (Node.js) — método principal
├── deploy/                 → Scripts WinSCP (.bat) — método alternativo
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

## Convenciones de código

- Componentes Astro: PascalCase (`Header.astro`)
- PHP: camelCase para métodos, PascalCase para clases
- CSS: Tailwind utilities + variables CSS personalizadas en `global.css`
- Imágenes: preferir WebP donde sea posible
- Commits: `[feature_slug] - descripción en español`
