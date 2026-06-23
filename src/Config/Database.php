<?php

namespace App\Config;

class Database
{
    private static ?\PDO $instance = null;

    public static function getConnection(): \PDO
    {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '5432';
            $name = getenv('DB_NAME') ?: 'courses_api';
            $user = getenv('DB_USER') or throw new \RuntimeException('Variável de ambiente DB_USER não definida.');
            $pass = getenv('DB_PASS') or throw new \RuntimeException('Variável de ambiente DB_PASS não definida.');

            $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
            self::$instance = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
