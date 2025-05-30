<?php
require_once "app/config/config.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

//class connect to db
class DatabaseConnection
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn; //connection

    public function __construct()
    {
        $this->host = HOST;
        $this->db_name = DB_NAME;
        $this->username = USERNAME;
        $this->password = PASSWORD;
    }

    public function getConnection()
    {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
        } catch (PDOException $e) {
            // Log error for debugging
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau.");
        }
    }
}
?>