<?php
namespace Database;

require_once BASE_PATH . '/vendor/autoload.php';
use Dotenv\Dotenv;

class Connection {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dotenv = Dotenv::createImmutable(BASE_PATH . '/');
        $dotenv->safeLoad();

        try {
            $this->pdo = new \PDO(
                "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
                $_ENV['DB_USER'],
                $_ENV['DB_PASSWORD'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (\PDOException $e) {
            throw new \Exception("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}
