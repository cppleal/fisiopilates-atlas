<?php
/**
 * backup-database.php - Script de backup de base de datos
 * Proyecto: Fisiopilates Atlas
 *
 * Uso desde línea de comandos:
 *   php backup/backup-database.php test v1.0.0    # Backup de TEST para versión
 *   php backup/backup-database.php all v1.0.0     # Backup de ambos para versión
 *   php backup/backup-database.php all            # Backup sin versión (usa timestamp)
 *
 * Genera dos ficheros por entorno:
 *   - estructura.sql  (solo CREATE TABLE)
 *   - completo.sql    (estructura + datos)
 *
 * Estructura de carpetas:
 *   backup/test/v1.0.0/estructura.sql
 *   backup/test/v1.0.0/completo.sql
 *   backup/prod/v1.0.0/estructura.sql
 *   backup/prod/v1.0.0/completo.sql
 */

// Configuración de entornos
$environments = [
    'test' => [
        'host'       => 'PMYSQL168.dns-servicio.com',
        'database'   => '10067489_fisiopilates_TEST',
        'user'       => 'cppleal_fisiopilates',
        'password'   => 'BWcq@khqY2UAct1o',
        'output_dir' => __DIR__ . '/test/'
    ],
    'prod' => [
        'host'       => 'PMYSQL117.dns-servicio.com',
        'database'   => '9702349_fisio',
        'user'       => 'cppleal-fisio',
        'password'   => 'riRkvnuR9KuTed@s',
        'output_dir' => __DIR__ . '/prod/'
    ]
];

// Obtener argumentos
$env     = $argv[1] ?? 'all';
$version = $argv[2] ?? null;

if (!in_array($env, ['test', 'prod', 'all'])) {
    echo "Uso: php backup/backup-database.php [test|prod|all] [version]\n";
    echo "\nEjemplos:\n";
    echo "  php backup/backup-database.php all v1.0.0\n";
    echo "  php backup/backup-database.php test v1.0.0\n";
    echo "  php backup/backup-database.php test\n";
    exit(1);
}

// Si no hay versión, usar timestamp
if (!$version) {
    $version = 'backup_' . date('Ymd_His');
}

// Función para realizar backup
function backupDatabase($config, $envName, $version) {
    $timestamp = date('Y-m-d H:i:s');

    echo "\n========================================\n";
    echo "Backup de $envName\n";
    echo "========================================\n";
    echo "Host: {$config['host']}\n";
    echo "Base de datos: {$config['database']}\n";
    echo "Versión: $version\n";
    echo "Fecha: $timestamp\n\n";

    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);

        echo "Conexión establecida.\n";

        // Obtener lista de tablas
        $tables = [];
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        echo "Tablas encontradas: " . count($tables) . "\n\n";

        // Cabecera SQL
        $header  = "-- ============================================\n";
        $header .= "-- Backup de base de datos: {$config['database']}\n";
        $header .= "-- Entorno: $envName\n";
        $header .= "-- Versión: $version\n";
        $header .= "-- Fecha: $timestamp\n";
        $header .= "-- ============================================\n\n";
        $header .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $header .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $header .= "SET time_zone = '+00:00';\n\n";

        $structureContent = $header;
        $fullContent      = $header;

        foreach ($tables as $table) {
            echo "  Procesando tabla: $table ... ";

            $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $createSQL  = $createStmt['Create Table'] ?? $createStmt['Create View'] ?? '';
            $dropSQL    = "DROP TABLE IF EXISTS `$table`;\n";

            $structureContent .= "-- Tabla: $table\n{$dropSQL}{$createSQL};\n\n";
            $fullContent      .= "-- Tabla: $table\n{$dropSQL}{$createSQL};\n\n";

            $dataResult = $pdo->query("SELECT * FROM `$table`");
            $rows       = $dataResult->fetchAll(PDO::FETCH_ASSOC);

            if (count($rows) > 0) {
                $columns    = array_keys($rows[0]);
                $columnList = '`' . implode('`, `', $columns) . '`';

                $fullContent .= "-- Datos de $table (" . count($rows) . " registros)\n";

                foreach (array_chunk($rows, 100) as $chunk) {
                    $values = [];
                    foreach ($chunk as $row) {
                        $rowValues = [];
                        foreach ($row as $value) {
                            $rowValues[] = ($value === null) ? 'NULL' : $pdo->quote($value);
                        }
                        $values[] = '(' . implode(', ', $rowValues) . ')';
                    }
                    $fullContent .= "INSERT INTO `$table` ($columnList) VALUES\n";
                    $fullContent .= implode(",\n", $values) . ";\n\n";
                }
            }

            echo "OK (" . count($rows) . " registros)\n";
        }

        $footer = "\nSET FOREIGN_KEY_CHECKS=1;\n-- Fin del backup\n";
        $structureContent .= $footer;
        $fullContent      .= $footer;

        $versionDir = $config['output_dir'] . $version . '/';
        if (!is_dir($versionDir)) {
            mkdir($versionDir, 0755, true);
        }

        $structureFile = $versionDir . "estructura.sql";
        $fullFile      = $versionDir . "completo.sql";

        file_put_contents($structureFile, $structureContent);
        file_put_contents($fullFile, $fullContent);

        echo "\n";
        echo "Ficheros generados en: $versionDir\n";
        echo "  - estructura.sql (" . formatBytes(filesize($structureFile)) . ")\n";
        echo "  - completo.sql ("   . formatBytes(filesize($fullFile))      . ")\n";

        return true;

    } catch (PDOException $e) {
        echo "\nERROR: " . $e->getMessage() . "\n";
        return false;
    }
}

function formatBytes($bytes) {
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return number_format($bytes / 1024, 2)    . ' KB';
    return $bytes . ' bytes';
}

// Ejecutar
echo "============================================\n";
echo "BACKUP DE BASE DE DATOS - Fisiopilates Atlas\n";
echo "============================================\n";
echo "Versión: $version\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";

$success = true;

if ($env === 'all' || $env === 'test') {
    if (!backupDatabase($environments['test'], 'TEST', $version)) {
        $success = false;
    }
}

if ($env === 'all' || $env === 'prod') {
    if (!backupDatabase($environments['prod'], 'PRODUCCIÓN', $version)) {
        $success = false;
    }
}

echo "\n============================================\n";
if ($success) {
    echo "Backup completado correctamente.\n";
    echo "Carpetas creadas:\n";
    if ($env === 'all' || $env === 'test') echo "  - backup/test/$version/\n";
    if ($env === 'all' || $env === 'prod') echo "  - backup/prod/$version/\n";
} else {
    echo "Backup completado con errores.\n";
}
echo "============================================\n";

exit($success ? 0 : 1);
