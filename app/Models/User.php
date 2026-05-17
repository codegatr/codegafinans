<?php

declare(strict_types=1);

namespace App\Models;

final class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name, string $email, string $password, float $monthlyIncome): array
    {
        $stmt = db()->prepare('INSERT INTO users (name, email, password, monthly_income) VALUES (:name, :email, :password, :monthly_income)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'monthly_income' => max(0, $monthlyIncome),
        ]);
        return self::find((int) db()->lastInsertId()) ?? [];
    }

    public static function updateProfile(int $id, string $name, float $monthlyIncome): array
    {
        $stmt = db()->prepare('UPDATE users SET name = :name, monthly_income = :monthly_income WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $name !== '' ? $name : 'Codega Kullanici',
            'monthly_income' => max(0, $monthlyIncome),
        ]);
        return self::find($id) ?? [];
    }

    public static function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'monthly_income' => (float) $user['monthly_income'],
        ];
    }
}
