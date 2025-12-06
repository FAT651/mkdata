<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $pdo;

    public function __construct() {
        $this->host = "localhost";
        $this->db_name = "infiniti_vtuapi";
        $this->username = "infiniti_vtuapi";
        $this->password = "Vtuapi@1231#";
        $this->connect(); // Establish connection when object is created
    }

    private function connect() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_AUTOCOMMIT => true
                )
            );
        } catch(PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    public function beginTransaction() {
        if (!$this->pdo) {
            $this->connect();
        }
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        if (!$this->pdo) {
            throw new Exception("No active connection");
        }
        return $this->pdo->commit();
    }

    public function rollBack() {
        if (!$this->pdo) {
            throw new Exception("No active connection");
        }
        return $this->pdo->rollBack();
    }

    public function lastInsertId() {
        if (!$this->pdo) {
            throw new Exception("No active connection");
        }
        return $this->pdo->lastInsertId();
    }

    public function inTransaction() {
        if (!$this->pdo) {
            return false;
        }
        return $this->pdo->inTransaction();
    }

    public function query($query, $params = [], $fetchResults = true) {
        if (!$this->pdo) {
            $this->connect();
        }
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            // For SELECT queries, return the results
            if ($fetchResults && stripos($query, 'SELECT') === 0) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // For INSERT, UPDATE, DELETE queries, return true
            return true;
        } catch(PDOException $e) {
            error_log("Database Error in query: " . $query . " with params: " . json_encode($params));
            error_log("Error message: " . $e->getMessage());
            throw new Exception("Database Error: " . $e->getMessage());
        }
    }
}
?>
