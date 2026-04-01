<?php

namespace App\Services;

class AuthService
{
    public function __construct(private \PDO $pdo) {}

    /**
     * Attempt to log in with email and password.
     * @return array{id: int, username: string}|null User data on success, null on failure.
     */
    public function attempt(string $email, string $password): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        return ['id' => (int) $user['id'], 'username' => $user['username']];
    }

    /**
     * Register a new user. Returns the new user ID.
     * @throws \RuntimeException on validation/duplicate errors.
     */
    public function register(string $fullName, string $username, string $email, string $password): int
    {
        // Check email uniqueness
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new \RuntimeException('email_exists');
        }

        // Check username uniqueness
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new \RuntimeException('username_exists');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (full_name, username, email, password_hash) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$fullName, $username, $email, $hash]);
            $userId = (int) $this->pdo->lastInsertId();

            $this->pdo->prepare('INSERT INTO user_profiles (user_id) VALUES (?)')->execute([$userId]);

            $this->pdo->commit();
            return $userId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
