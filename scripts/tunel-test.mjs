/**
 * Túnel SSH a la BD de TEST del VPS (contenedor fa-test-db) - Fisiopilates Atlas
 *
 * La BD de TEST no tiene puerto público: MySQL solo escucha en el loopback del VPS
 * (127.0.0.1:33309). Este script abre un túnel desde el PC, 127.0.0.1:33309 → VPS
 * 127.0.0.1:33309, para conectar con DB_HOST_TEST/DB_PORT_TEST del .env local.
 *
 * Uso:
 *   node scripts/tunel-test.mjs                    abre el túnel y lo mantiene hasta Ctrl+C
 *   node scripts/tunel-test.mjs --hasta-fin-stdin  lo cierra al cerrarse stdin (backup-database.php)
 */

import { spawn } from 'child_process';
import { mkdtempSync, writeFileSync, rmSync } from 'fs';
import net from 'net';
import { tmpdir } from 'os';
import { join } from 'path';
import { fileURLToPath } from 'url';
import { config } from 'dotenv';
import { configVps } from './vps.mjs';

const ROOT = fileURLToPath(new URL('..', import.meta.url));
config({ path: join(ROOT, '.env'), quiet: true });

const PUERTO_LOCAL = Number(process.env.DB_PORT_TEST) || 33309;
const PUERTO_VPS = 33309;

const puertoAbierto = (puerto) => new Promise((ok) => {
  const s = net.connect(puerto, '127.0.0.1');
  s.once('connect', () => { s.destroy(); ok(true); });
  s.once('error', () => ok(false));
});

/** Abre el túnel (si ya hay uno abierto, lo reutiliza). Devuelve una función para cerrarlo. */
async function abrirTunelTest() {
  if (await puertoAbierto(PUERTO_LOCAL)) return () => {};

  const vps = configVps();
  const carpeta = mkdtempSync(join(tmpdir(), 'fisio-tunel-'));
  const pw = join(carpeta, 'pw');
  writeFileSync(pw, vps.pass, { mode: 0o600 });
  const plink = spawn('plink', [
    '-ssh', '-P', String(vps.port), '-pwfile', pw, '-hostkey', vps.hostkey, '-batch', '-N',
    '-L', `127.0.0.1:${PUERTO_LOCAL}:127.0.0.1:${PUERTO_VPS}`,
    `${vps.user}@${vps.host}`,
  ], { stdio: ['ignore', 'ignore', 'pipe'] });
  let error = '';
  plink.stderr.on('data', (d) => { error += d; });

  const cerrar = () => {
    plink.kill();
    rmSync(carpeta, { recursive: true, force: true });
  };
  for (let i = 0; i < 40; i++) {
    if (plink.exitCode !== null) break;
    if (await puertoAbierto(PUERTO_LOCAL)) return cerrar;
    await new Promise((r) => setTimeout(r, 250));
  }
  cerrar();
  throw new Error(`no se ha podido abrir el túnel a la BD de TEST: ${error.trim() || 'sin respuesta'}`);
}

try {
  const cerrar = await abrirTunelTest();
  console.log(`Túnel abierto: 127.0.0.1:${PUERTO_LOCAL} → BD de TEST del VPS.`);
  const salir = () => { cerrar(); process.exit(0); };
  process.on('SIGINT', salir);
  process.on('SIGTERM', salir);
  if (process.argv.includes('--hasta-fin-stdin')) {
    process.stdin.on('end', salir);
    process.stdin.resume();
  }
  setInterval(() => {}, 1 << 30);
} catch (err) {
  console.error('ERROR:', err.message);
  process.exit(1);
}
