<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'talofood');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database {
    private $host = "localhost";
    private $port = "3307";
    private $db_name = "talofood";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch(PDOException $e) {
            throw new PDOException("Lỗi kết nối database: " . $e->getMessage());
        }
    }
} 