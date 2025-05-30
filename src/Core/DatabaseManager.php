<?php

namespace App\Core;

class DatabaseManager {
    private static string $db_host = "localhost"; // Or 127.0.0.1
    private static string $db_user = "root";      // Username for phpMyAdmin
    private static string $db_pass = "Supergodball2540-"; // MariaDB password
    private static string $db_name = "price_calculator"; // Database name

    private static ?\mysqli $connection = null;

    // Private constructor to prevent direct creation of object
    private function __construct() {
    }

    // Private clone method to prevent cloning of the instance
    private function __clone() {
    }

    public static function getConnection(): \mysqli {
        if (self::$connection === null) {
            self::$connection = new \mysqli(self::$db_host, self::$db_user, self::$db_pass, self::$db_name);

            if (self::$connection->connect_error) {
                throw new \RuntimeException("Database connection failed: " . self::$connection->connect_error);
            }

            self::$connection->set_charset("utf8mb4");
        }
        return self::$connection;
    }

    public static function closeConnection(): void {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }
}

?> 