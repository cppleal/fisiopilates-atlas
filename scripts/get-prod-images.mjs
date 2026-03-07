/**
 * Descarga imágenes de PROD (Joomla legacy) via FTP (puerto 21)
 * Solo lectura — no modifica nada en PROD
 *
 * Uso:
 *   node scripts/get-prod-images.mjs list      → Lista imágenes en PROD
 *   node scripts/get-prod-images.mjs explore   → Muestra estructura de carpetas
 *   node scripts/get-prod-images.mjs download  → Descarga todo a public/images/
 */

import { Client } from 'basic-ftp';
import { mkdirSync, existsSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { config } from 'dotenv';

config();

const __dirname = fileURLToPath(new URL('.', import.meta.url));
const ROOT = join(__dirname, '..');

const FTP_CONFIG = {
  host: process.env.SFTP_HOST_PROD || '40546259.servicio-online.net',
  port: 21,
  user: process.env.SFTP_USER_PROD || 'user-9702349',
  password: process.env.SFTP_PASS_PROD || '$iMw3E1A7+C@9ba+',
  secure: false,
};

const REMOTE_ROOT = '/fisiopilatesatlas.es';
const LOCAL_DEST  = join(ROOT, 'public/images');
const IMAGE_EXT   = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.ico'];

const isImage = name => IMAGE_EXT.some(ext => name.toLowerCase().endsWith(ext));
const fmtSize = bytes =>
  bytes > 1024 * 1024
    ? `${(bytes / 1024 / 1024).toFixed(1)} MB`
    : `${Math.round(bytes / 1024)} KB`;

// ── Explorar estructura ──────────────────────────────────────────────────────
async function explore(client, remotePath, depth = 0, maxDepth = 3) {
  if (depth > maxDepth) return;
  const prefix = '  '.repeat(depth);
  let items;
  try { items = await client.list(remotePath); } catch { return; }

  for (const item of items) {
    if (item.name.startsWith('.')) continue;
    const full = `${remotePath}/${item.name}`;
    if (item.isDirectory) {
      console.log(`${prefix}📁 ${item.name}/`);
      await explore(client, full, depth + 1, maxDepth);
    } else if (isImage(item.name)) {
      console.log(`${prefix}🖼️  ${item.name} (${fmtSize(item.size)})`);
    }
  }
}

// ── Listar imágenes recursivamente ───────────────────────────────────────────
async function listImages(client, remotePath, depth = 0) {
  const images = [];
  if (depth > 8) return images;
  let items;
  try { items = await client.list(remotePath); } catch { return images; }

  for (const item of items) {
    if (item.name.startsWith('.')) continue;
    const full = `${remotePath}/${item.name}`;
    if (item.isDirectory) {
      const sub = await listImages(client, full, depth + 1);
      images.push(...sub);
    } else if (isImage(item.name)) {
      images.push({ path: full, size: item.size });
    }
  }
  return images;
}

// ── Descargar lista de imágenes ───────────────────────────────────────────────
async function downloadImages(client, images) {
  let downloaded = 0, skipped = 0, errors = 0;

  for (const img of images) {
    const relative  = img.path.replace(REMOTE_ROOT, '');
    const localPath = join(LOCAL_DEST, relative);
    const localDir  = dirname(localPath);

    if (!existsSync(localDir)) mkdirSync(localDir, { recursive: true });

    if (existsSync(localPath)) { skipped++; continue; }

    try {
      await client.downloadTo(localPath, img.path);
      console.log(`  ✅ ${relative} (${fmtSize(img.size)})`);
      downloaded++;
    } catch (err) {
      console.error(`  ❌ ${img.path}: ${err.message}`);
      errors++;
    }
  }
  return { downloaded, skipped, errors };
}

// ── MAIN ─────────────────────────────────────────────────────────────────────
const mode = process.argv[2] || 'list';
const client = new Client();
client.ftp.verbose = false;

try {
  console.log(`\n🔌 Conectando a PROD: ${FTP_CONFIG.host}:${FTP_CONFIG.port}...`);
  await client.access(FTP_CONFIG);
  console.log('✅ Conectado (FTP puerto 21)\n');

  if (mode === 'explore') {
    console.log(`📂 Estructura de ${REMOTE_ROOT} (hasta 3 niveles):\n`);
    await explore(client, REMOTE_ROOT);

  } else if (mode === 'list') {
    console.log(`🔍 Buscando imágenes en ${REMOTE_ROOT}...\n`);
    const images = await listImages(client, REMOTE_ROOT);
    console.log(`\nEncontradas ${images.length} imágenes:\n`);
    for (const img of images) {
      console.log(`  ${img.path} (${fmtSize(img.size)})`);
    }
    console.log(`\nTotal: ${images.length} imágenes`);
    console.log('\nPara descargarlas: node scripts/get-prod-images.mjs download');

  } else if (mode === 'download') {
    if (!existsSync(LOCAL_DEST)) mkdirSync(LOCAL_DEST, { recursive: true });

    console.log(`🔍 Buscando imágenes en ${REMOTE_ROOT}...`);
    const images = await listImages(client, REMOTE_ROOT);
    console.log(`Encontradas: ${images.length}. Descargando a public/images/...\n`);

    const { downloaded, skipped, errors } = await downloadImages(client, images);

    console.log(`\n✅ Descarga completada:`);
    console.log(`   Descargadas:  ${downloaded}`);
    console.log(`   Ya existían:  ${skipped}`);
    console.log(`   Errores:      ${errors}`);
    console.log(`\n📁 Imágenes en: ${LOCAL_DEST}`);

  } else {
    console.log('Uso: node scripts/get-prod-images.mjs [list|download|explore]');
  }

} catch (err) {
  console.error('❌ Error FTP:', err.message);
  process.exit(1);
} finally {
  client.close();
}
