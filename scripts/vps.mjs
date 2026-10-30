/**
 * Acceso al VPS de Hostinger (TEST desde el 2026-10-30) - Fisiopilates Atlas
 *
 * Cada llamada a ssh() es UNA conexión plink (PuTTY): el comando se ejecuta en el VPS
 * y lo que se pase en `input` le llega por stdin. Credenciales en el .env local:
 * VPS_HOST, VPS_PORT, VPS_USER, VPS_HOSTKEY, VPS_PASS y VPS_DIR (/opt/fisiopilates-test).
 * La contraseña se pasa a plink en un fichero temporal (-pwfile), nunca en la línea de órdenes.
 *
 * El VPS queda fuera de la regla anti-bloqueos de Hostalia.
 */

import { execFileSync } from 'child_process';
import { mkdtempSync, writeFileSync, rmSync } from 'fs';
import { tmpdir } from 'os';
import { join } from 'path';

export const CONTENEDOR_WEB = 'fa-test-web';
export const CONTENEDOR_BD = 'fa-test-db';

export function configVps(env = process.env) {
  const { VPS_HOST, VPS_PORT, VPS_USER, VPS_HOSTKEY, VPS_PASS, VPS_DIR } = env;
  if (!VPS_HOST || !VPS_USER || !VPS_HOSTKEY || !VPS_PASS || !VPS_DIR) {
    throw new Error('faltan VPS_HOST, VPS_USER, VPS_HOSTKEY, VPS_PASS o VPS_DIR en el .env local');
  }
  return { host: VPS_HOST, port: VPS_PORT || '22', user: VPS_USER, hostkey: VPS_HOSTKEY, pass: VPS_PASS, dir: VPS_DIR };
}

/**
 * Ejecuta `comando` en el VPS. Devuelve stdout (Buffer si encoding es null).
 * Si el comando falla, lanza un error con su stderr.
 */
export function ssh(comando, { input, encoding = 'utf8', env } = {}) {
  const vps = configVps(env);
  const carpeta = mkdtempSync(join(tmpdir(), 'fisio-vps-'));
  const pw = join(carpeta, 'pw');
  writeFileSync(pw, vps.pass, { mode: 0o600 });
  try {
    return execFileSync('plink', [
      '-ssh', '-P', String(vps.port), '-pwfile', pw, '-hostkey', vps.hostkey, '-batch',
      `${vps.user}@${vps.host}`, comando,
    ], { input, encoding: encoding || undefined, maxBuffer: 1 << 30, stdio: ['pipe', 'pipe', 'pipe'] });
  } catch (err) {
    const detalle = (err.stderr ? err.stderr.toString() : '').trim();
    throw new Error(`SSH al VPS: ${detalle || err.message}`);
  } finally {
    rmSync(carpeta, { recursive: true, force: true });
  }
}
