<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Records user activity in the database and a plain-text access log.
 */
class ActivityLogService
{
    // Plain-text access log on disk (in addition to the DB table).
    private const LOG_FILE = __DIR__ . '/../../storage/logs/access.log';

    public function __construct(
        private PDO $pdo
    ) {}

    public function log(?int $userId, string $action, ?string $ip = null): void
    {
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null);

        // 1) Write to the database table
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO log_activities (user_id, action, ip_address)
                 VALUES (:uid, :action, :ip)"
            );
            $stmt->execute([
                ':uid' => $userId,
                ':action' => $action,
                ':ip' => $ip,
            ]);
        } catch (\Throwable $e) {
            error_log('ActivityLogService error: ' . $e->getMessage());
        }

        // 2) Append a human-readable line to the text file
        $this->writeToFile($userId, $action, $ip);
    }

    /**
     * Append one line to the text log file (creates dir/file if missing).
     */
    private function writeToFile(?int $userId, string $action, ?string $ip): void
    {
        try {
            $dir = dirname(self::LOG_FILE);

            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException('Unable to create log directory.');
            }

            $line = sprintf(
                "[%s] action=%s user_id=%s ip=%s%s",
                date('Y-m-d H:i:s'),
                $action,
                $userId ?? 'guest',
                $ip ?? '-',
                PHP_EOL
            );
            if(file_put_contents(self::LOG_FILE, $line, FILE_APPEND | LOCK_EX) === false){
                throw new \RuntimeException('Unable to write activity log.');
            }

        } catch (\Throwable $e) {
            error_log('ActivityLogService file error: ' . $e->getMessage());
        }
    }

    /**
     * Read the raw text-log content (last $maxLines lines), for display.
     */
    public function readLogFile(int $maxLines = 300): string
    {
        if (!is_file(self::LOG_FILE)) {
            return '';
        }
        $content = file_get_contents(self::LOG_FILE);
        if ($content === false || $content === '') {
            return '';
        }
        $lines = preg_split('/\r?\n/', trim($content));
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, -$maxLines);
        }
        return implode("\n", $lines);
    }

    /**
     * Simple access statistics derived from the text log (count per action).
     */
    public function fileStats(): array
    {
        $stats = ['total' => 0, 'log_in' => 0, 'log_out' => 0, 'register' => 0];
        if (!is_file(self::LOG_FILE)) {
            return $stats;
        }
        $handle = @fopen(self::LOG_FILE, 'r');
        if (!$handle) {
            return $stats;
        }
        while (($line = fgets($handle)) !== false) {
            if (trim($line) === '') {
                continue;
            }
            $stats['total']++;
            foreach (['log_in', 'log_out', 'register'] as $a) {
                if (str_contains($line, 'action=' . $a)) {
                    $stats[$a]++;
                }
            }
        }
        fclose($handle);
        return $stats;
    }
}