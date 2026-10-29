/**
 * Genera el api/config.php de un servidor con SOLO las credenciales de su entorno
 * — Fisiopilates Atlas
 *
 * El .env local reúne todo lo necesario para trabajar desde el PC (FTP y
 * credenciales de los dos entornos). Al servidor no se sube ningún .env: lo que
 * lee su PHP es api/config.php. Antes era el mismo fichero en TEST y en PROD y
 * llevaba la BD de los dos entornos; ahora se genera a partir de
 * php/config.template.php con las claves CLAVE_TEST o CLAVE_PROD del .env local.
 * Nunca FTP ni datos del otro entorno. Regla «El .env del servidor lleva solo lo
 * que usa el servidor» del CLAUDE.md global; modelo: La Revolución del Arte v2.12.8.
 *
 * Uso:
 *   node scripts/config-servidor.mjs test|prod --simular   baja el del servidor y compara, no sube nada
 *   node scripts/config-servidor.mjs test|prod             sube solo el config.php
 *   node scripts/config-servidor.mjs test|prod --forzar    sube aunque cambie la BD
 * scripts/deploy.mjs usa subirConfig() en su despliegue completo (misma conexión FTP).
 *
 * Antes de subir guarda el config.php actual del servidor en ../backups/ (fuera
 * del repo) y se para si el nuevo apunta a otra BD, salvo --forzar. Después
 * comprueba la SHA-256 de lo subido. Una sola conexión FTP por ejecución. Nunca
 * imprime valores, solo nombres de constante.
 */

import { Client } from 'basic-ftp';
import { execFileSync } from 'child_process';
import { createHash } from 'crypto';
import { mkdirSync, readFileSync, writeFileSync } from 'fs';
import { join, relative, resolve } from 'path';
import { Readable } from 'stream';
import { fileURLToPath } from 'url';
import { config } from 'dotenv';

const ROOT = resolve(fileURLToPath(new URL('..', import.meta.url)));
const BACKUPS = resolve(ROOT, '..', 'backups');
config({ path: join(ROOT, '.env'), quiet: true });

/** Constantes de php/config.template.php que salen del .env (CLAVE_TEST / CLAVE_PROD). */
const CLAVES = [
  'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'APP_SECRET',
  'HCAPTCHA_SITE_KEY', 'HCAPTCHA_SECRET',
  'SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS', 'SMTP_FROM', 'SMTP_FROM_NAME',
];
/** Si cambia alguna, el config.php nuevo apunta a otra BD: hace falta --forzar. */
const CLAVES_BD = ['DB_HOST', 'DB_NAME', 'DB_USER'];
/** Host con el que se evalúa el config.php del servidor (el antiguo elegía la BD por HTTP_HOST). */
const HOST = { test: '40749769.servicio-online.net', prod: 'fisiopilatesatlas.es' };

const sha256 = (texto) => createHash('sha256').update(texto).digest('hex');
const phpCadena = (v) => `'${v.replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;

/** Devuelve el contenido del api/config.php del entorno ('test' o 'prod'). */
export function generarConfig(entorno) {
  const SUF = entorno.toUpperCase();
  const valores = {};
  for (const k of CLAVES) {
    const v = process.env[`${k}_${SUF}`];
    if (!v) throw new Error(`falta ${k}_${SUF} en el .env local`);
    valores[k] = v;
  }
  if (!/^\d+$/.test(valores.SMTP_PORT)) throw new Error(`SMTP_PORT_${SUF} no es un número`);
  // IS_TEST_ENV sale del nombre de la BD: TEST debe llevar «_TEST» y PROD no
  if ((entorno === 'test') !== /_TEST/i.test(valores.DB_NAME)) {
    throw new Error(`DB_NAME_${SUF} no parece de ${SUF}: revisar el .env local`);
  }

  let php = readFileSync(join(ROOT, 'php/config.template.php'), 'utf8');
  php = php.replace(/^<\?php\r?\n\/\*\*[\s\S]*?\*\/\r?\n/, [
    '<?php',
    '/**',
    ` * api/config.php del servidor ${SUF} — Fisiopilates Atlas`,
    ' * Generado por scripts/config-servidor.mjs a partir de php/config.template.php.',
    ' * Solo lleva las credenciales de este entorno. No editar en el servidor.',
    ' */',
    '',
  ].join('\n'));
  php = php.replace(/'\{\{([A-Z_]+)\}\}'|\{\{([A-Z_]+)\}\}/g, (m, cadena, numero) => {
    const k = cadena || numero;
    if (!(k in valores)) throw new Error(`la plantilla pide {{${k}}}, que no está en CLAVES`);
    return cadena ? phpCadena(valores[k]) : valores[k];
  });
  return php;
}

/** Constantes que define un config.php al cargarlo con PHP (como lo vería el servidor). */
function constantesDe(fichero, entorno) {
  const codigo = `$_SERVER['HTTP_HOST'] = ${phpCadena(HOST[entorno])};`
    + ` require ${phpCadena(fichero)};`
    + ` $o = []; foreach (${JSON.stringify(CLAVES)} as $c) $o[$c] = defined($c) ? (string) constant($c) : null;`
    + ' echo json_encode($o);';
  const env = { ...process.env };
  for (const k of ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']) delete env[k];
  return JSON.parse(execFileSync('php', ['-r', codigo], { env, encoding: 'utf8' }));
}

/**
 * Con una conexión FTP ya abierta: copia el api/config.php del servidor a
 * ../backups/, compara y (si no es simulación) sube el nuevo y comprueba su SHA-256.
 */
export async function subirConfig(client, entorno, remoteDir, { simular = false, forzar = false } = {}) {
  const nuevo = generarConfig(entorno);
  const remoto = `${remoteDir}/api/config.php`;
  const fecha = new Date().toLocaleDateString('sv-SE');  // AAAA-MM-DD en hora local
  mkdirSync(BACKUPS, { recursive: true });
  const copia = join(BACKUPS, `config-servidor-${entorno}_${fecha}.php`);
  const temporal = join(BACKUPS, `.config-nuevo-${entorno}.php`);

  let previo = null;
  try {
    await client.downloadTo(copia, remoto);
    previo = readFileSync(copia, 'utf8');
    console.log(`   Copia del config.php del servidor: ${relative(ROOT, copia)}`);
  } catch {
    console.log('   No había api/config.php en el servidor: se crea.');
  }

  if (previo !== null) {
    writeFileSync(temporal, nuevo, { mode: 0o600 });
    let antes, despues;
    try {
      antes = constantesDe(copia, entorno);
      despues = constantesDe(temporal, entorno);
    } finally {
      writeFileSync(temporal, '');
    }
    const distintas = CLAVES.filter((k) => antes[k] !== despues[k]);
    console.log(distintas.length
      ? `   Cambian: ${distintas.join(', ')}`
      : '   Mismos valores que el del servidor (solo desaparecen los del otro entorno).');
    const cambiaBd = distintas.filter((k) => CLAVES_BD.includes(k));
    if (cambiaBd.length && !forzar && !simular) {
      throw new Error(`el config.php nuevo cambia ${cambiaBd.join(', ')}: revisar o usar --forzar`);
    }
    if (sha256(previo) === sha256(nuevo)) console.log('   El del servidor ya es idéntico.');
  }
  if (simular) return;

  await client.uploadFrom(Readable.from([nuevo]), remoto);
  await client.downloadTo(temporal, remoto);
  const subido = readFileSync(temporal, 'utf8');
  writeFileSync(temporal, '');
  if (sha256(subido) !== sha256(nuevo)) throw new Error('la SHA-256 del config.php subido no coincide');
  console.log(`   Subido ${remoto} (${entorno.toUpperCase()}, SHA-256 comprobada)`);
}

// ── Uso directo ──────────────────────────────────────────────────────────────
if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const entorno = (process.argv[2] || '').toLowerCase();
  if (!['test', 'prod'].includes(entorno)) {
    console.error('Uso: node scripts/config-servidor.mjs test|prod [--simular] [--forzar]');
    process.exit(1);
  }
  const SUF = entorno.toUpperCase();
  const client = new Client();
  try {
    await client.access({
      host: process.env[`FTP_HOST_${SUF}`],
      user: process.env[`FTP_USER_${SUF}`],
      password: process.env[`FTP_PASS_${SUF}`],
      secure: false,
      port: 21,
    });
    console.log(`Conectado a ${process.env[`FTP_HOST_${SUF}`]} (${SUF})`);
    await subirConfig(client, entorno, process.env[`FTP_REMOTE_DIR_${SUF}`], {
      simular: process.argv.includes('--simular'),
      forzar: process.argv.includes('--forzar'),
    });
  } catch (err) {
    console.error('ERROR:', err.message);
    process.exitCode = 1;
  } finally {
    client.close();
  }
}
