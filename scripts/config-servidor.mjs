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
 * scripts/deploy.mjs usa subirConfig() en su despliegue completo (misma conexión).
 *
 * Destino:
 *   TEST → VPS por SSH: VPS_DIR/env/config.php (root:www-data 640), fuera de site/ y
 *          montado en el contenedor como api/config.php. DB_HOST es «db» (la red de
 *          Docker); el DB_HOST_TEST del .env local es el del túnel.
 *   PROD → Hostalia por FTP, una sola conexión por ejecución.
 *
 * Antes de subir guarda el config.php actual del servidor en ../backups/ (fuera
 * del repo) y se para si el nuevo apunta a otra BD, salvo --forzar. Después
 * comprueba la SHA-256 de lo subido. Nunca imprime valores, solo nombres de constante.
 */

import { Client } from 'basic-ftp';
import { execFileSync } from 'child_process';
import { createHash } from 'crypto';
import { mkdirSync, readFileSync, writeFileSync } from 'fs';
import { join, relative, resolve } from 'path';
import { Readable } from 'stream';
import { fileURLToPath } from 'url';
import { config } from 'dotenv';
import { configVps, ssh } from './vps.mjs';

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
const HOST = { test: 'test.fisiopilatesatlas.es', prod: 'fisiopilatesatlas.es' };

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
  // En el VPS la web llega a MySQL por la red interna de Docker (servicio «db»)
  if (entorno === 'test') valores.DB_HOST = 'db';
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

/** Destino en Hostalia, con una conexión FTP ya abierta. */
export function destinoFtp(client, remoteDir) {
  const remoto = `${remoteDir}/api/config.php`;
  const temporal = join(BACKUPS, '.config-bajado.php');
  return {
    nombre: remoto,
    async leer() {
      mkdirSync(BACKUPS, { recursive: true });
      try {
        await client.downloadTo(temporal, remoto);
        return readFileSync(temporal, 'utf8');
      } catch {
        return null;
      } finally {
        writeFileSync(temporal, '');
      }
    },
    escribir: (texto) => client.uploadFrom(Readable.from([texto]), remoto),
  };
}

/** Destino en el VPS. Se escribe en el mismo fichero: el montaje de Docker sigue al inodo. */
export function destinoVps() {
  const { dir } = configVps();
  const remoto = `${dir}/env/config.php`;
  return {
    nombre: `VPS:${remoto}`,
    leer: async () => ssh(`cat ${remoto} 2>/dev/null || true`) || null,
    escribir: async (texto) => {
      ssh(`mkdir -p ${dir}/env && touch ${remoto} && chown root:33 ${remoto} && chmod 640 ${remoto} && cat > ${remoto}`, { input: texto });
    },
  };
}

/**
 * Copia el config.php del servidor a ../backups/, compara y (si no es simulación)
 * sube el nuevo y comprueba su SHA-256.
 */
export async function subirConfig(destino, entorno, { simular = false, forzar = false } = {}) {
  const nuevo = generarConfig(entorno);
  const fecha = new Date().toLocaleDateString('sv-SE');  // AAAA-MM-DD en hora local
  mkdirSync(BACKUPS, { recursive: true });
  const copia = join(BACKUPS, `config-servidor-${entorno}_${fecha}.php`);
  const temporal = join(BACKUPS, `.config-nuevo-${entorno}.php`);

  const previo = await destino.leer();
  if (previo === null) {
    console.log('   No había config.php en el servidor: se crea.');
  } else {
    writeFileSync(copia, previo, { mode: 0o600 });
    console.log(`   Copia del config.php del servidor: ${relative(ROOT, copia)}`);
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

  await destino.escribir(nuevo);
  const subido = await destino.leer();
  if (subido === null || sha256(subido) !== sha256(nuevo)) throw new Error('la SHA-256 del config.php subido no coincide');
  console.log(`   Subido ${destino.nombre} (${entorno.toUpperCase()}, SHA-256 comprobada)`);
}

// ── Uso directo ──────────────────────────────────────────────────────────────
if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const entorno = (process.argv[2] || '').toLowerCase();
  if (!['test', 'prod'].includes(entorno)) {
    console.error('Uso: node scripts/config-servidor.mjs test|prod [--simular] [--forzar]');
    process.exit(1);
  }
  const SUF = entorno.toUpperCase();
  const opciones = {
    simular: process.argv.includes('--simular'),
    forzar: process.argv.includes('--forzar'),
  };
  const client = new Client();
  try {
    if (entorno === 'test') {
      await subirConfig(destinoVps(), entorno, opciones);
      process.exit(0);
    }
    await client.access({
      host: process.env[`FTP_HOST_${SUF}`],
      user: process.env[`FTP_USER_${SUF}`],
      password: process.env[`FTP_PASS_${SUF}`],
      secure: false,
      port: 21,
    });
    console.log(`Conectado a ${process.env[`FTP_HOST_${SUF}`]} (${SUF})`);
    await subirConfig(destinoFtp(client, process.env[`FTP_REMOTE_DIR_${SUF}`]), entorno, opciones);
  } catch (err) {
    console.error('ERROR:', err.message);
    process.exitCode = 1;
  } finally {
    client.close();
  }
}
