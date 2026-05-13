<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $db = null;

    public static function connect(): PDO
    {
        if (self::$db instanceof PDO) {
            return self::$db;
        }

        $host = getenv('DB_HOST') ?: 'db';
        $database = getenv('DB_NAME') ?: 'lab';
        $user = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') ?: 'root';

        self::$db = new PDO(
            "mysql:host={$host};dbname={$database};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        self::initTables();

        return self::$db;
    }

    private static function initTables(): void
    {
        $queries = [
            'CREATE TABLE IF NOT EXISTS authors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS publishers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS books (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                author_id INT NOT NULL,
                publisher_id INT NOT NULL,
                CONSTRAINT fk_books_author
                    FOREIGN KEY (author_id) REFERENCES authors(id)
                    ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT fk_books_publisher
                    FOREIGN KEY (publisher_id) REFERENCES publishers(id)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        ];

        foreach ($queries as $query) {
            self::$db->exec($query);
        }

        self::seedReferenceData();
    }

    private static function seedReferenceData(): void
    {
        $authorCount = (int) self::$db->query('SELECT COUNT(*) FROM authors')->fetchColumn();
        if ($authorCount === 0) {
            self::$db->exec(
                "INSERT INTO authors (name) VALUES ('Taras Shevchenko'), ('Lesya Ukrainka')"
            );
        }

        $publisherCount = (int) self::$db->query('SELECT COUNT(*) FROM publishers')->fetchColumn();
        if ($publisherCount === 0) {
            self::$db->exec(
                "INSERT INTO publishers (name) VALUES ('Vivat'), ('Ranok')"
            );
        }
    }
}
