// @ts-check
import { defineConfig } from 'astro/config';
import tailwindcss from '@tailwindcss/vite';

// Dominios de terceros que usa la web:
// - hCaptcha (contacto): pide script, frame, style y connect en hcaptcha.com y *.hcaptcha.com
//   (no fijar subdominios concretos: https://docs.hcaptcha.com/#content-security-policy-settings)
// - Google Maps (iframe de la home): maps.google.com redirige a www.google.com
const HCAPTCHA = ['https://hcaptcha.com', 'https://*.hcaptcha.com'];

// https://astro.build/config
export default defineConfig({
  site: 'https://fisiopilatesatlas.es',
  output: 'static',
  build: {
    format: 'file', // Genera fisioterapia.html en vez de fisioterapia/index.html
  },
  vite: {
    plugins: [tailwindcss()]
  },
  // CSP por hashes: Astro añade a cada página un <meta http-equiv="content-security-policy">
  // con las huellas de sus scripts y estilos en línea. Sin 'unsafe-inline': un script o un
  // style="" metido en el HTML no se aplica. Las directivas que no admite el <meta>
  // (frame-ancestors) van como cabecera en public/.htaccess.
  experimental: {
    csp: {
      algorithm: 'SHA-256',
      directives: [
        "default-src 'self'",
        "img-src 'self' data:",
        "font-src 'self'",
        `connect-src 'self' ${HCAPTCHA.join(' ')}`,
        `frame-src ${HCAPTCHA.join(' ')} https://maps.google.com https://www.google.com`,
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
      ],
      scriptDirective: {
        resources: ["'self'", ...HCAPTCHA],
      },
      styleDirective: {
        resources: ["'self'", ...HCAPTCHA],
      },
    },
  },
});
