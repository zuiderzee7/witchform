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

        $config = require BASE_PATH . '/config/database.php';

        try {
            $this->pdo = new \PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
                $config['username'],
                $config['password'],
                $config['options']
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
