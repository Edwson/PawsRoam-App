<?php
// config/database.php must include this exact class
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        // Load environment variables from .env file if it exists
        // This is a simple way to load .env variables without a library for this specific project structure.
        // In a real-world scenario, a library like Dotenv would be used.
        $envPath = __DIR__ . '/../.env'; // Adjusted path to root .env
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) { // Skip comments
                    continue;
                }
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }

        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? 'pawsroam_db';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        try {
            $this->connection = new PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci" // Ensure UTF8MB4
                ]
            );
        } catch (PDOException $e) {
            // In a real application, this would be logged, and a user-friendly error page shown.
            error_log('Database connection failed: ' . $e->getMessage());
            // Avoid dying directly in a class constructor if possible, let the caller handle it
            // For this project, to match original spec closely, we'll keep die but with error logging.
            die('Database connection failed. Please check server logs. Error: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
