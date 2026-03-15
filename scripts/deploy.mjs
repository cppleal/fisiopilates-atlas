/**
 * Script de deploy FTP - Fisiopilates Atlas
 * Uso:
 *   node scripts/deploy.mjs           → TEST (FTP puerto 21)
 *   node scripts/deploy.mjs prod      → PRODUCCIÓN ← REQUIERE confirmación
 *   node scripts/deploy.mjs test file → Sube un archivo específico a TEST
 *
 * Ejemplos subida parcial:
 *   node scripts/deploy.mjs test index.html
 *   node scripts/deploy.mjs test api/contacto.php admin/index.php
 */

import { Client } from 'basic-ftp';
import { existsSync, statSync } from 'fs';
import { join } from 'path';
import { fileURLToPath } from 'url';
import { config } from 'dotenv';

config();

const __dirname = fileURLToPath(new URL('.', import.meta.url));
const ROOT = join(__dirname, '..');

const isProd = process.argv[2] === 'prod';
const specificFiles = process.argv.slice(isProd ? 3 : 3); // archivos específicos si los hay
const isPartial = !isProd && process.argv[2] === 'test' && specificFiles.length > 0;

// Configuración de entornos
const ENV = {
  test: {
    host: process.env.FTP_HOST_TEST || '40749769.servicio-online.net',
    user: process.env.FTP_USER_TEST || 'user-10067489',
    pass: process.env.FTP_PASS_TEST || 'C2@4AVSt#MneBHVw',
    remoteDir: process.env.FTP_REMOTE_DIR_TEST || '/httpdocs',
    label: 'TEST',
    url: 'https://40749769.servicio-online.net',
    secure: false,
    port: 21,
  },
  prod: {
    host: process.env.SFTP_HOST_PROD || '40546259.servicio-online.net',
    user: process.env.SFTP_USER_PROD || 'user-9702349',
    pass: process.env.SFTP_PASS_PROD || '',
    remoteDir: process.env.SFTP_REMOTE_DIR_PROD || '/fisiopilatesatlas.es',
    label: 'PRODUCCIÓN',
    url: 'https://fisiopilatesatlas.es',
    secure: false,
    port: 21,
  },
};

const env = isProd ? ENV.prod : ENV.test;

// =========================================
// GUARDIA PRODUCCIÓN
// =========================================
if (isProd) {
  console.log('\n⛔  ATENCIÓN: Estás a punto de desplegar a PRODUCCIÓN.');
  console.log('   Esto sobreescribirá el sitio en vivo: https://fisiopilatesatlas.es');
  console.log('   Actualmente hay una web JOOMLA en producción.');
  console.log('\n   Presiona Ctrl+C en los próximos 10 segundos para cancelar...\n');
  await new Promise(resolve => setTimeout(resolve, 10000));
}

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

    await client.uploadFrom(join(ROOT, 'php/config.php'),                      `${env.remoteDir}/api/config.php`);
    await client.uploadFrom(join(ROOT, 'php/contacto.php'),                    `${env.remoteDir}/api/contacto.php`);
    await client.uploadFrom(join(ROOT, 'php/lib/SmtpMailer.php'),              `${env.remoteDir}/api/lib/SmtpMailer.php`);
    await client.uploadFrom(join(ROOT, 'php/admin/index.php'),                 `${env.remoteDir}/admin/index.php`);
    await client.uploadFrom(join(ROOT, 'php/admin/cookies.php'),               `${env.remoteDir}/admin/cookies.php`);
    await client.uploadFrom(join(ROOT, 'php/admin/ip-check.php'),              `${env.remoteDir}/admin/ip-check.php`);

    // Cookie consent backend (RGPD)
    try { await client.ensureDir(`${env.remoteDir}/api/cookies`); } catch {}
    await client.uploadFrom(join(ROOT, 'php/cookies/log-consent.php'),         `${env.remoteDir}/api/cookies/log-consent.php`);
    await client.uploadFrom(join(ROOT, 'php/cookies/CookieConsentService.php'),`${env.remoteDir}/api/cookies/CookieConsentService.php`);

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
