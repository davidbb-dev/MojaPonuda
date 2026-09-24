<?php
declare(strict_types=1);

namespace App\Database;

use App\Exceptions\CustomDatabaseException;
use PDO;
use PDOException;

/**
 * Manages the application's PDO database connection.
 *
 * Supports configurable connection parameters, lazy connection
 * initialization, and application-specific database exceptions.
 */
class Database
{
    private ?PDO $pdo = null;

    private string $host;
    private string $dbname;
    private string $charset;
    private string $username;
    private string $password;

    public function __construct(
        ?string $host = null,
        ?string $dbname = null,
        ?string $charset = null,
        ?string $username = null,
        ?string $password = null
    ) {
        // Prefer constructor arguments, then environment variables,
        // with sensible defaults for non-sensitive Docker settings.
        $this->host     = $host     ?? (getenv('DB_HOST')    ?: 'db');
        $this->dbname   = $dbname   ?? (getenv('DB_NAME')    ?: 'pva_db');
        $this->charset  = $charset  ?? (getenv('DB_CHARSET') ?: 'utf8mb4');
        $this->username = $username ?? (getenv('DB_USER')    ?: '');
        $this->password = $password ?? (getenv('DB_PASS')    ?: '');
    }

    public function databaseConnection(): PDO
    {
        $dsn = "mysql:host=$this->host;dbname=$this->dbname;charset=$this->charset";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            if ($this->pdo === null) {
                $this->pdo = new PDO(
                    $dsn,
                    $this->username,
                    $this->password,
                    $options
                );

                $this->pdo->exec("SET time_zone = '+00:00'");
            }
        } catch (PDOException $e) {
            throw new CustomDatabaseException(
                'Error while connecting to database!',
                0,
                $e
            );
        }

        return $this->pdo;
    }
}