<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidUserInputException;
use App\Exceptions\UserAlreadyExistsException;
use PDO;
use PDOException;

/**
 * Handles user retrieval, profile updates, password changes,
 * avatar updates, and user-related authorization checks.
 */
class UserService
{
    public function __construct(
        private PDO $pdo
    ) {}

    /**
     * Returns a user's full profile data by ID.
     */
    public function getById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT user_id, name, lastname, username, email, avatar_path, bio, role, status, created_at
             FROM users WHERE user_id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function isAdmin(int $userId): bool
    {
        $user = $this->getById($userId);
        return $user !== null
            && in_array($user['role'], ['admin', 'superadmin'], true)
            && $user['status'] === 'active';
    }

    /**
     * Returns public profile data and the user's active listing count.
     */
    public function getPublicProfile(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT user_id, username, avatar_path, bio, created_at 
             FROM users 
             WHERE user_id = :id
             LIMIT 1"
        );

        $stmt->execute([':id' => $userId]);

        $user = $stmt->fetch();

        if ($user === false) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM listings
             WHERE user_id = :id 
                AND is_deleted = 0 
                AND status = 'active'"
        );
        $stmt->execute([':id' => $userId]);
        $user['active_listings'] = (int)$stmt->fetchColumn();

        return $user;
    }

    public function updateProfile(
        int $userId,
        string $name,
        string $lastname,
        string $username,
        string $email,
        ?string $bio = null
    ): void {
        $name = trim($name);
        $lastname = trim($lastname);
        $username = trim($username);
        $email = trim(mb_strtolower($email));
        $bio = $bio !== null ? trim($bio) : null;

        $this->assertLength($name, 2, 100, 'Ime');
        $this->assertLength($lastname, 2, 100, 'Prezime');

        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            throw new InvalidUserInputException('Korisničko ime mora imati 3-30 slova/brojeva/donjih crta.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            throw new InvalidUserInputException('Email adresa nije validna!');
        }

        if ($bio !== null && mb_strlen($bio) > 500) {
            throw new InvalidUserInputException('Biografija je predugačka (max 500).');
        }

        try {
            $stmt = $this->pdo->prepare(
                "UPDATE users
                 SET name = :name, lastname = :lastname, username = :username, email = :email, bio = :bio
                 WHERE user_id = :id"
            );
            $stmt->execute([
                ':name' => $name,
                ':lastname' => $lastname,
                ':username' => $username,
                ':email' => $email,
                ':bio' => $bio,
                ':id' => $userId,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                if (str_contains($e->getMessage(), 'username')) {
                    throw new UserAlreadyExistsException('Korisničko ime je već zauzeto!');
                }
                if (str_contains($e->getMessage(), 'email')) {
                    throw new UserAlreadyExistsException('Email je već u upotrebi!');
                }
            }
            throw $e;
        }
    }

    public function changePassword(int $userId, string $current, string $new): void
    {
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE user_id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $hash = $stmt->fetchColumn();

        if ($hash === false || !password_verify($current, (string)$hash)) {
            throw new InvalidUserInputException('Trenutna lozinka nije ispravna.');
        }

        if (mb_strlen($new) < 8) {
            throw new InvalidUserInputException('Nova lozinka mora imati bar 8 karaktera.');
        }

        if (strlen($new) > 72) {
            throw new InvalidUserInputException('Nova lozinka može imati najviše 72 karaktera.');
        }

        $stmt = $this->pdo->prepare("UPDATE users SET password = :p WHERE user_id = :id");
        $stmt->execute([
            ':p' => password_hash($new, PASSWORD_DEFAULT),
            ':id' => $userId
        ]);
    }

    /**
     * Updates the user's avatar path and returns the previous path.
     */
    public function updateAvatar(int $userId, string $webPath): ?string
    {
        $stmt = $this->pdo->prepare('SELECT avatar_path FROM users WHERE user_id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $oldPath = $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("UPDATE users SET avatar_path = :p WHERE user_id = :id");
        $stmt->execute([
            ':p' => $webPath,
            ':id' => $userId
        ]);

        return $oldPath !== false ? $oldPath : null;
    }

    private function assertLength(string $value, int $min, int $max, string $label): void
    {
        $len = mb_strlen($value);
        if ($len < $min) {
            throw new InvalidUserInputException("$label je prekratko (min $min).");
        }
        if ($len > $max) {
            throw new InvalidUserInputException("$label je predugačko (max $max).");
        }
    }
}