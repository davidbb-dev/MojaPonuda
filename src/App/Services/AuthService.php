<?php
declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Exceptions\InvalidUserInputException;
use App\Exceptions\UserAlreadyExistsException;
use PDO;
use PDOException;

/**
 * Handles user authentication, registration, and account lookup.
 */
class AuthService
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function getUserDataByIdentifier(string $identifier): ?array
    {
        $column = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $stmt = $this->pdo->prepare(
            "SELECT user_id, username, password, role, status 
             FROM users 
             WHERE $column = :identifier 
                AND is_deleted = 0 LIMIT 1"
        );

        $stmt->execute([':identifier' => $identifier]);
        $user_data = $stmt->fetch();

        return $user_data ?: null;
    }

    public function login(string $identifier, string $password): ?array
    {
        $userData = $this->getUserDataByIdentifier($identifier);

        if ($userData === null || !password_verify($password, $userData['password'])) {
            return null;
        }

        return [
            'user_id' => (int)$userData['user_id'],
            'username' => $userData['username'],
            'role' => $userData['role'],
            'status' => $userData['status']
        ];
    }

    public function register(
        string $name,
        string $lastname,
        string $username,
        string $email,
        string $password
    ): int {
        // Password length is checked before creating the password hash.
        if (strlen($password) < 8) {
            throw new InvalidUserInputException(
                'Lozinka mora imati najmanje 8 karaktera.'
            );
        }

        if (strlen($password) > 72) {
            throw new InvalidUserInputException(
                'Lozinka može imati najviše 72 karaktera.'
            );
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (
                name,
                lastname,
                username,
                email,
                password,
                role,
                status
            )
            VALUES (
                :name,
                :lastname,
                :username,
                :email,
                :password,
                :role,
                :status
            )'
        );

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $role = UserRoleEnum::USER->value;
        $status = UserStatusEnum::ACTIVE->value;

        try {
            $stmt->execute([
                ':name' => $name,
                ':lastname' => $lastname,
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':role' => $role,
                ':status' => $status
            ]);
        } catch (PDOException $e) {
            // 23000 for integrity constraint violation (duplicate entry etc.)
            if ($e->getCode() === '23000') {
                if (str_contains($e->getMessage(), 'username')) {
                    throw new UserAlreadyExistsException('Username already exists!');
                }

                if (str_contains($e->getMessage(), 'email')) {
                    throw new UserAlreadyExistsException('Email already exists!');
                }

                throw new UserAlreadyExistsException('User already exists!');
            }

            throw $e;
        }

        return (int)$this->pdo->lastInsertId();
    }
}
