# Arquitectura — Fisiopilates Atlas

## Stack técnico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Frontend | Astro (static output) | 5.x |
| CSS | Tailwind CSS | 4.x |
| Backend | PHP | 8.x |
| Base de datos | MySQL | 5.7+ / 8.x |
| Servidor web | Apache + mod_rewrite | Hostalia |
| Deploy | FTP (basic-ftp / WinSCP) | Puerto 21 |

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
│   ├── config.php          → Configuración BD, SMTP, hCaptcha
│   ├── contacto.php        → API formulario de contacto
│   ├── install.php         → Script creación tablas (ejecutar 1 vez, luego borrar)
│   ├── lib/
│   │   └── SmtpMailer.php  → Clase envío SMTP nativo
│   ├── admin/
│   │   ├── index.php       → Panel de administración (login + dashboard)
│   │   └── cookies.php     → Vista registros cookie consent
│   └── cookies/
│       ├── log-consent.php         → API endpoint registro consentimiento
│       └── CookieConsentService.php → Servicio RGPD (lógica de negocio)
├── public/
│   ├── .htaccess           → Apache: HTTPS, www→sin www, routing, cache, compresión
│   ├── images/             → Imágenes del sitio (JPG, WebP)
│   ├── css/
│   │   └── cookie-consent.css → Estilos del banner de cookies
│   └── js/
│       └── cookie-consent.js  → Lógica del banner de cookies
├── scripts/
│   └── deploy.mjs          → Deploy FTP alternativo (Node.js)
├── deploy/                 → Scripts WinSCP (.bat) — método principal
│   ├── deploy-local.bat
│   ├── deploy-config.bat   → Credenciales reales (no en git)
│   └── get-prod-images.bat
├── specs/                  → Esta documentación
├── package.json            → v1.0.0
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
- Tel: 691 487 526
- Email: fisiopilates.atlas@gmail.com
- Redes: Facebook, WhatsApp, Twitter (@ClinicaAtlas)
- Dirección: c/Travesía de Alfredo Aleix, 1 — Junto al banco La Caixa · Carabanchel Alto, 28044 Madrid

### `Hero.astro`
- Props: `title`, `subtitle`, `ctaText`, `ctaHref`, `ctaSecondaryText`, `ctaSecondaryHref`, `backgroundImage`
- Fondo imagen con overlay oscuro
- 2 botones CTA

### `SectionTitle.astro`
- Props: `title`, `subtitle`
- Centrado, con línea decorativa color primary

---

## Routing (Astro static + Apache)

Astro genera archivos `.html`. Apache mapea rutas sin extensión:

```
/                   → /index.html
/fisioterapia       → /fisioterapia.html
/pilates            → /pilates.html
/precios            → /precios.html
/contacto           → /contacto.html
/privacidad         → /privacidad.html
/cookies            → /cookies.html
/404                → /404.html
/api/contacto.php   → PHP directo (archivo existente, pasa por -f)
/api/cookies/log-consent.php → PHP directo
/admin/             → /admin/index.php
/admin/cookies.php  → PHP directo
```

Regla clave `.htaccess` (evita loops):
```apache
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
```

---

## Convenciones de código

- Componentes Astro: PascalCase (`Header.astro`)
- PHP: camelCase para métodos, PascalCase para clases
- CSS: Tailwind utilities + variables CSS personalizadas en `global.css`
- Imágenes: preferir WebP (compresión con Sharp, ~80% reducción)
- Sin git en este proyecto (no es repositorio)
