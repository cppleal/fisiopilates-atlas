/**
 * Comprobación de la CSP - Fisiopilates Atlas
 * Uso:
 *   node scripts/check-csp.mjs   → se ejecuta solo al final de npm run build
 *
 * Falla (código 1) si algo que la CSP va a bloquear se cuela en el código:
 *   - una página de dist/ sin el <meta> de la CSP, o con el <meta> detrás de un script
 *   - un <script> o <style> en línea cuya huella no está en la CSP
 *   - un atributo style="" o un manejador on*= en el HTML (los hashes no los cubren)
 *   - un manejador on*= o un <script> en línea en el admin PHP (usar public/js/admin.js)
 */

import { createHash } from 'crypto';
import { readdirSync, readFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const errores = [];

function huella(texto) {
  return `'sha256-${createHash('sha256').update(texto).digest('base64')}'`;
}

function listar(dir, ext) {
  return readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
    const ruta = join(dir, e.name);
    if (e.isDirectory()) return listar(ruta, ext);
    return ruta.endsWith(ext) ? [ruta] : [];
  });
}

// ── Páginas generadas por Astro ──
for (const fichero of listar(join(ROOT, 'dist'), '.html')) {
  const nombre = fichero.slice(ROOT.length + 1);
  const html = readFileSync(fichero, 'utf-8');
  const meta = html.match(/<meta http-equiv="content-security-policy" content="([^"]+)"/i);
  if (!meta) {
    errores.push(`${nombre}: no lleva el <meta> de la CSP`);
    continue;
  }
  const csp = meta[1];
  if (/'unsafe-inline'/.test(csp)) errores.push(`${nombre}: la CSP lleva 'unsafe-inline'`);
  if (html.indexOf(meta[0]) > html.indexOf('<script')) {
    errores.push(`${nombre}: el <meta> de la CSP va detrás de un <script>`);
  }
  for (const [, contenido] of html.matchAll(/<script(?![^>]*\bsrc=)[^>]*>([\s\S]*?)<\/script>/g)) {
    if (!csp.includes(huella(contenido))) errores.push(`${nombre}: <script> en línea sin huella en la CSP`);
  }
  for (const [, contenido] of html.matchAll(/<style[^>]*>([\s\S]*?)<\/style>/g)) {
    if (!csp.includes(huella(contenido))) errores.push(`${nombre}: <style> en línea sin huella en la CSP`);
  }
  if (/<[^>]+\sstyle="/.test(html)) errores.push(`${nombre}: atributo style="" (usar clases de Tailwind)`);
  if (/<[^>]+\son[a-z]+="/.test(html)) errores.push(`${nombre}: manejador on*= en el HTML`);
}

// ── Panel de administración PHP ──
for (const fichero of listar(join(ROOT, 'php/admin'), '.php')) {
  const nombre = fichero.slice(ROOT.length + 1);
  const php = readFileSync(fichero, 'utf-8');
  if (/<[^>]+\son[a-z]+="/.test(php)) errores.push(`${nombre}: manejador on*= (usar data-* de public/js/admin.js)`);
  if (/<script(?![^>]*\bsrc=)[^>]*>/.test(php)) errores.push(`${nombre}: <script> en línea (su CSP solo permite ficheros)`);
}

if (errores.length) {
  console.error('❌ CSP: hay cosas que el navegador va a bloquear:');
  for (const e of errores) console.error('   - ' + e);
  process.exit(1);
}
console.log('✅ CSP: todas las páginas llevan su política y no hay código en línea sin huella');
