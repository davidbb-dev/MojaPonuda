<?php
declare(strict_types=1);

/**
 * Initializes the application database and default data.
 *
 * The app container runs this automatically through entrypoint.sh.
 * It can also be run manually with:
 *   php database/init-db.php
 */

$host    = getenv('DB_HOST')    ?: 'db';
$dbname  = getenv('DB_NAME')    ?: 'pva_db';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';
$user    = getenv('DB_USER');
$pass    = getenv('DB_PASS');

$adminUsername = getenv('ADMIN_USERNAME');
$adminPassword = getenv('ADMIN_PASSWORD');
$adminEmail    = getenv('ADMIN_EMAIL');

if (!$user || !$pass) {
    fwrite(STDERR, "Database credentials are not configured.\n");
    exit(1);
}

if (!$adminUsername || !$adminPassword || !$adminEmail) {
    fwrite(STDERR, "Admin credentials are not configured.\n");
    exit(1);
}

// Sanitize the database name before using it as a backtick-quoted identifier.
$dbname = preg_replace('/[^A-Za-z0-9_]/', '', $dbname);

if ($dbname === '') {
    fwrite(STDERR, "Invalid database name.\n");
    exit(1);
}

try {
    // Connect without selecting a database so it can be created if needed.
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec(
        "CREATE DATABASE IF NOT EXISTS `$dbname`
         CHARACTER SET utf8mb4
         COLLATE utf8mb4_unicode_ci"
    );

    $pdo->exec("USE `$dbname`");
} catch (PDOException $e) {
    fwrite(STDERR, "DB connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

// ---------------------------------------------------------------
// Apply schema.sql.
// ---------------------------------------------------------------
$schema = file_get_contents(__DIR__ . '/schema.sql');

if ($schema === false) {
    fwrite(STDERR, "schema.sql not found\n");
    exit(1);
}

foreach (splitSql($schema) as $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        fwrite(STDERR, "schema.sql failed:\n  " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Schema applied.\n";

// ---------------------------------------------------------------
// Ensure a default superadmin account exists.
// Credentials are provided through environment variables.
// ---------------------------------------------------------------
$admins = (int)$pdo
    ->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin'")
    ->fetchColumn();

if ($admins === 0) {
    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO users (
            name,
            lastname,
            username,
            email,
            password,
            role,
            status
        )
        VALUES (
            'Admin',
            'Admin',
            :username,
            :email,
            :password,
            'superadmin',
            'active'
        )"
    );

    $stmt->execute([
        ':username' => $adminUsername,
        ':email' => $adminEmail,
        ':password' => $hash,
    ]);

    if ($stmt->rowCount() > 0) {
        echo "Default superadmin account created.\n";
    }
}

/**
 * Splits the SQL schema into individual statements.
 *
 * Removes full-line SQL comments and separates statements by semicolons.
 */
function splitSql(string $sql): array
{
    $lines = preg_split('/\r?\n/', $sql);
    $clean = [];

    foreach ($lines as $line) {
        $trim = ltrim($line);

        if ($trim === '' || str_starts_with($trim, '--')) {
            continue;
        }

        $clean[] = $line;
    }

    $joined = implode("\n", $clean);

    $parts = array_map('trim', explode(';', $joined));

    return array_values(
        array_filter($parts, fn($statement) => $statement !== '')
    );
}