/**
 * Verificación del TEST del VPS (https://test.fisiopilatesatlas.es)
 *
 * Solo para TEST: el VPS queda fuera de la regla anti-bloqueos de Hostalia.
 * NO usar contra PROD ni contra el TEST antiguo (Hostalia): pide rutas sensibles
 * para comprobar que dan 403/404, y eso Hostalia lo toma por una auditoría.
 *
 * Uso:
 *   node scripts/verificar-test.mjs
 *   node scripts/verificar-test.mjs https://otra-url
 */

const BASE = (process.argv[2] || 'https://test.fisiopilatesatlas.es').replace(/\/$/, '');

const html = (r) => r.status === 200 && /<html/i.test(r.texto);
const json = (r) => {
  try { return JSON.parse(r.texto); } catch { return null; }
};
const cabeceraSeguridad = (r) => /frame-ancestors/.test(r.cabeceras.get('content-security-policy') || '')
  && r.cabeceras.get('x-content-type-options') === 'nosniff';

const pruebas = [
  // [descripción, ruta, comprobación, opciones de fetch]
  ['Portada', '/', html],
  ['URL limpia: fisioterapia', '/fisioterapia', html],
  ['URL limpia: pilates', '/pilates', html],
  ['URL limpia: precios', '/precios', html],
  ['URL limpia: privacidad', '/privacidad', html],
  ['URL limpia: cookies', '/cookies', html],
  ['Contacto con hCaptcha', '/contacto', (r) => html(r) && /h-captcha/.test(r.texto)],
  ['CSP en la página', '/', (r) => /http-equiv="content-security-policy"/i.test(r.texto)],
  ['Cabeceras de seguridad del .htaccess', '/', cabeceraSeguridad],
  ['JS público', '/js/cookie-consent.js', (r) => r.status === 200],
  ['Ruta inexistente → 404', '/no-existe-xyz', (r) => r.status === 404 && /<html/i.test(r.texto)],
  ['Cookies: solo POST', '/api/cookies/log-consent.php', (r) => r.status === 405],
  ['Cookies: registro en la BD', '/api/cookies/log-consent.php', (r) => r.status === 200 && json(r)?.success === true, {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'reject_all', session_token: 'verificar-test' }),
  }],
  ['Formulario: sin captcha no pasa', '/api/contacto.php', (r) => r.status === 302 && /status=captcha/.test(r.cabeceras.get('location') || ''), {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'nombre=x',
  }],
  ['Panel: login o IP no autorizada', '/admin/', (r) => (r.status === 200 && /password/i.test(r.texto)) || (r.status === 403 && /Acceso denegado/.test(r.texto))],
  ['api/config.php bloqueado', '/api/config.php', (r) => r.status === 403 && !/define\(/.test(r.texto)],
  ['.env no accesible', '/.env', (r) => r.status === 403 || r.status === 404],
  ['.htaccess no accesible', '/.htaccess', (r) => r.status === 403],
  ['package.json no accesible', '/package.json', (r) => r.status === 403 || r.status === 404],
  ['robots.txt de TEST', '/robots.txt', (r) => r.status === 200 && /Disallow: \//.test(r.texto)],
  ['Cabecera X-Robots-Tag noindex', '/', (r) => /noindex/.test(r.cabeceras.get('x-robots-tag') || '')],
  ['Sin X-Powered-By', '/api/cookies/log-consent.php', (r) => !r.cabeceras.get('x-powered-by')],
];

let bien = 0;
for (const [desc, ruta, ok, opciones] of pruebas) {
  let r;
  try {
    const res = await fetch(BASE + ruta, { redirect: 'manual', ...opciones });
    r = { status: res.status, cabeceras: res.headers, texto: await res.text() };
  } catch (err) {
    r = { status: 0, cabeceras: new Headers(), texto: '', error: err.message };
  }
  const paso = ok(r);
  if (paso) bien++;
  console.log(`${paso ? 'OK ' : 'MAL'}  ${String(r.status).padEnd(3)}  ${desc}  (${ruta})${r.error ? ' — ' + r.error : ''}`);
}

// http:// → https://
if (BASE.startsWith('https://')) {
  try {
    const res = await fetch(BASE.replace('https://', 'http://') + '/', { redirect: 'manual' });
    const loc = res.headers.get('location') || '';
    const paso = res.status >= 300 && res.status < 400 && loc.startsWith(BASE);
    if (paso) bien++;
    console.log(`${paso ? 'OK ' : 'MAL'}  ${res.status}  http:// redirige a https:// (${loc})`);
  } catch (err) {
    console.log(`MAL  ---  http:// redirige a https:// — ${err.message}`);
  }
}

const total = pruebas.length + (BASE.startsWith('https://') ? 1 : 0);
console.log(`\n${bien}/${total} comprobaciones correctas en ${BASE}`);
process.exit(bien === total ? 0 : 1);
