/**
 * Script de deploy - Fisiopilates Atlas
 * Uso:
 *   node scripts/deploy.mjs           → TEST (VPS por SSH, https://test.fisiopilatesatlas.es)
 *   node scripts/deploy.mjs prod      → PRODUCCIÓN (FTP) ← REQUIERE confirmación
 *   node scripts/deploy.mjs test file → Sube un archivo específico a TEST
 *   node scripts/deploy.mjs prod file → Sube un archivo específico a PROD ← REQUIERE confirmación
 *   --forzar → sube el api/config.php aunque apunte a otra BD que el del servidor
 *
 * Ejemplos subida parcial:
 *   node scripts/deploy.mjs test index.html
 *   node scripts/deploy.mjs test api/contacto.php admin/index.php
 *
 * Al VPS los ficheros van empaquetados con tar por UNA conexión SSH y se comprueba
 * la SHA-256 de cada uno en el servidor. Solo sube: un fichero que sobre se borra a
 * mano en VPS_DIR/site/.
 */

import { Client } from 'basic-ftp';
import { copyFileSync, cpSync, existsSync, mkdirSync, mkdtempSync, readdirSync, readFileSync, rmSync } from 'fs';
import { execFileSync } from 'child_process';
import { createHash } from 'crypto';
import { tmpdir } from 'os';
import { dirname, join, relative, sep } from 'path';
import { fileURLToPath } from 'url';
import { config } from 'dotenv';
import { destinoFtp, destinoVps, subirConfig } from './config-servidor.mjs';
import { CONTENEDOR_WEB, configVps, ssh } from './vps.mjs';

config();

const __dirname = fileURLToPath(new URL('.', import.meta.url));
const ROOT = join(__dirname, '..');

const isProd = process.argv[2] === 'prod';
const specificFiles = process.argv.slice(3).filter((a) => !a.startsWith('--')); // archivos específicos si los hay
const isPartial = (isProd || process.argv[2] === 'test') && specificFiles.length > 0;

// PHP del servidor: [origen, destino]. api/config.php se genera aparte (config-servidor.mjs)
const PHP = [
  ['php/contacto.php',                     'api/contacto.php'],
  ['php/lib/SmtpMailer.php',               'api/lib/SmtpMailer.php'],
  ['php/admin/index.php',                  'admin/index.php'],
  ['php/admin/cookies.php',                'admin/cookies.php'],
  ['php/admin/ip-check.php',               'admin/ip-check.php'],
  ['php/cookies/log-consent.php',          'api/cookies/log-consent.php'],
  ['php/cookies/CookieConsentService.php', 'api/cookies/CookieConsentService.php'],
];

const sha256De = (fichero) => createHash('sha256').update(readFileSync(fichero)).digest('hex');
const listar = (dir) => readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
  const ruta = join(dir, e.name);
  return e.isDirectory() ? listar(ruta) : [ruta];
});

/** TEST: dist/ + PHP por tar en una sola conexión SSH; el VPS devuelve la SHA-256 de lo extraído. */
async function deployVps() {
  const { dir } = configVps();
  const remoto = `${dir}/site`;
  const distDir = join(ROOT, 'dist');
  if (!existsSync(distDir)) throw new Error('No existe dist/. Ejecuta npm run build primero.');

  // Copia temporal con la misma estructura que el servidor
  const stage = mkdtempSync(join(tmpdir(), 'fisio-deploy-'));
  try {
    cpSync(distDir, stage, { recursive: true });
    for (const [origen, destino] of PHP) {
      mkdirSync(dirname(join(stage, destino)), { recursive: true });
      copyFileSync(join(ROOT, origen), join(stage, destino));
    }

    const todos = listar(stage).map((f) => relative(stage, f).split(sep).join('/'));
    const faltan = specificFiles.filter((f) => !todos.includes(f));
    for (const f of faltan) console.warn(`⚠️  No encontrado: ${f}`);
    const ficheros = isPartial ? specificFiles.filter((f) => todos.includes(f)) : todos;
    if (ficheros.length === 0) throw new Error('nada que subir');

    // En Windows, el tar del sistema (bsdtar): el de Git Bash toma «C:» por un host remoto
    const tarExe = process.platform === 'win32'
      ? join(process.env.SystemRoot || 'C:\\Windows', 'System32', 'tar.exe')
      : 'tar';
    const paquete = execFileSync(tarExe, ['-c', '-f', '-', '-C', stage, ...ficheros], { maxBuffer: 2 ** 31 - 1 });
    console.log(`📦 ${ficheros.length} ficheros, ${(paquete.length / 1048576).toFixed(1)} MB. Subiendo al VPS...`);

    const comando = [
      'set -eo pipefail',
      'export LC_ALL=C.UTF-8',
      `mkdir -p ${remoto}`,
      `cd ${remoto}`,
      "tar -x -v -f - --no-same-owner --no-same-permissions --quoting-style=literal | tr '\\n' '\\0' | xargs -0 -r sha256sum",
    ].join('; ');
    const salida = ssh(`bash -c "${comando}"`, { input: paquete });

    const remotos = {};
    for (const linea of salida.split('\n')) {
      const m = linea.match(/^([0-9a-f]{64}) [ *](.+)$/);
      if (m) remotos[m[2].replace(/^\.\//, '')] = m[1];
    }
    const fallidos = ficheros.filter((f) => remotos[f] !== sha256De(join(stage, f)));
    if (fallidos.length) throw new Error(`no coinciden en el VPS (SHA-256): ${fallidos.slice(0, 20).join(', ')}`);
    console.log(`✅ ${ficheros.length} ficheros subidos, SHA-256 comprobada (${CONTENEDOR_WEB})`);

    // api/config.php generado con las credenciales de TEST (scripts/config-servidor.mjs)
    if (!isPartial) await subirConfig(destinoVps(), 'test', { forzar: process.argv.includes('--forzar') });
  } finally {
    rmSync(stage, { recursive: true, force: true });
  }
}

// TEST va al VPS; el FTP de abajo es solo para PROD
if (!isProd) {
  console.log(`\n🚀 Desplegando a TEST (VPS)${isPartial ? ' (archivos específicos)' : ''}...`);
  try {
    await deployVps();
    console.log('\n🌐 URL: https://test.fisiopilatesatlas.es');
  } catch (err) {
    console.error('❌ Error en el deploy:', err.message);
    process.exit(1);
  }
  process.exit(0);
}

// Configuración de PRODUCCIÓN
const ENV = {
  prod: {
    host: process.env.FTP_HOST_PROD || '40546259.servicio-online.net',
    user: process.env.FTP_USER_PROD || 'user-9702349',
    pass: process.env.FTP_PASS_PROD,
    remoteDir: process.env.FTP_REMOTE_DIR_PROD || '/fisiopilatesatlas.es',
    label: 'PRODUCCIÓN',
    url: 'https://fisiopilatesatlas.es',
    secure: false,
    port: 21,
  },
};

const env = ENV.prod;

// =========================================
// GUARDIA PRODUCCIÓN
// =========================================
console.log('\n⛔  ATENCIÓN: Estás a punto de desplegar a PRODUCCIÓN.');
console.log('   Esto sobreescribirá el sitio en vivo: https://fisiopilatesatlas.es');
console.log('\n   Presiona Ctrl+C en los próximos 10 segundos para cancelar...\n');
await new Promise(resolve => setTimeout(resolve, 10000));

console.log(`\n🚀 Desplegando a ${env.label}${isPartial ? ' (archivos específicos)' : ''}...`);

const client = new Client();
client.ftp.verbose = false;

try {
  await client.access({
    host: env.host,
    user: env.user,
    password: env.pass,
    secure: false,
    port: env.port,
  });

  console.log(`✅ Conectado a ${env.host}`);

  if (isPartial) {
    // ── Deploy parcial: archivos específicos ──
    for (const relPath of specificFiles) {
      const localPath = join(ROOT, 'dist', relPath);
      const remotePath = `${env.remoteDir}/${relPath}`;
      if (!existsSync(localPath)) {
        console.warn(`⚠️  No encontrado: ${localPath}`);
        continue;
      }
      console.log(`  → Subiendo ${relPath}`);
      await client.uploadFrom(localPath, remotePath);
    }
  } else {
    // ── Deploy completo ──
    const distDir = join(ROOT, 'dist');
    if (!existsSync(distDir)) {
      throw new Error('No existe dist/. Ejecuta npm run build primero.');
    }

    console.log('📁 Subiendo archivos estáticos (dist/)...');
    await client.uploadFromDir(distDir, env.remoteDir);

    // PHP backend
    console.log('📁 Subiendo backend PHP...');

    // Asegurar directorios remotos
    try { await client.ensureDir(`${env.remoteDir}/api`); } catch {}
    try { await client.ensureDir(`${env.remoteDir}/api/lib`); } catch {}
    try { await client.ensureDir(`${env.remoteDir}/admin`); } catch {}

    // api/config.php generado con las credenciales de este entorno (scripts/config-servidor.mjs)
    try { await client.ensureDir(`${env.remoteDir}/api/cookies`); } catch {}
    await subirConfig(destinoFtp(client, env.remoteDir), 'prod', { forzar: process.argv.includes('--forzar') });
    for (const [origen, destino] of PHP) {
      await client.uploadFrom(join(ROOT, origen), `${env.remoteDir}/${destino}`);
    }

    // install.php: subir solo si se pasa el flag --install
    if (process.argv.includes('--install')) {
      const installPath = join(ROOT, 'php/install.php');
      if (existsSync(installPath)) {
        await client.uploadFrom(installPath, `${env.remoteDir}/install.php`);
        console.log('   ⚠️  install.php subido → ejecútalo en el navegador y luego bórralo del servidor');
      }
    }
  }

  console.log(`\n✅ Deploy completado en ${env.label}`);
  console.log(`🌐 URL: ${env.url}`);

} catch (err) {
  console.error('❌ Error en el deploy:', err.message);
  process.exit(1);
} finally {
  client.close();
}
